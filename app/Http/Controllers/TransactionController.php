<?php

namespace App\Http\Controllers;

use App\Models\{Item, StockIn, StockOut, Transaction, Department, Category};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\{DB, Auth};
use Barryvdh\DomPDF\Facade\Pdf;

class TransactionController extends Controller
{
    /**
     * Optimized Index: Pinagsamang History ng In at Out
     */
    public function index()
    {
        // Query the unified Transaction table
        $transactions = Transaction::with([
                'item:id,name,category_id', 
                'item.category:id,name',
                'user:id,name' // Assuming you want the user who recorded it
            ])
            ->latest()
            ->limit(200) // Increased limit since we aren't fetching 100 of each separately
            ->get()
            ->map(fn($record) => [
                // Note: If you added 'ref_no' to your Transaction migration, change $record->id to $record->ref_no
                'id' => $record->id, 
                'raw_id' => strtolower($record->type) . '-' . $record->id,
                'created_at' => $record->created_at,
                'item' => $record->item,
                'type' => $record->type,
                'quantity' => $record->quantity,
                'recorded_by' => $record->user->name ?? 'System',
                
                // Mappings specific to Stock In
                'received_by' => $record->type === 'In' ? $record->personnel_name : null,
                
                // Mappings specific to Stock Out
                'released_to' => $record->type === 'Out' ? $record->personnel_name : null,
                'department' => $record->type === 'Out' ? $record->source_destination : null,
                
                // Dynamic Note Assignment
                'note' => $record->type === 'In' 
                            ? "Supplier: " . ($record->source_destination ?? 'N/A') 
                            : ($record->note ?? 'Release'),
            ]);

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'departments' => Department::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function stockIn()
    {
        return Inertia::render('Transactions/StockIn', [
            'items' => Item::orderBy('name')->get(['id', 'name', 'product_code']),
        ]);
    }

    public function stockOut()
    {
        return Inertia::render('Transactions/StockOut', [
            'items' => Item::where('quantity', '>', 0)->orderBy('name')->get(['id', 'name', 'quantity', 'min_stock', 'product_code']),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Bulk Stock In Logic
     */
    public function store_bulk_in(Request $request)
    {
        $request->validate([
            'date_received' => 'required|date',
            'line_items' => 'required|array|min:1',
            'line_items.*.item_id' => 'required|exists:items,id',
            'line_items.*.quantity' => 'required|numeric|min:0.1',
        ]);

        $refNo = \Carbon\Carbon::parse($request->date_received)->format('Y-m-d');
        $lastId = null;

        DB::transaction(function () use ($request, $refNo, &$lastId) {
            foreach ($request->line_items as $itemData) {
                $record = StockIn::create([
                    'item_id' => $itemData['item_id'],
                    'quantity' => $itemData['quantity'],
                    'supplier_name' => $request->supplier_name,
                    'date_received' => $request->date_received,
                    'received_by' => Auth::user()->name,
                    'ref_no' => $refNo, 
                ]);
                $lastId = $record->id;
                Item::findOrFail($itemData['item_id'])->increment('quantity', $itemData['quantity']);
            }
        });

        return back()->with(['success' => 'Stock In recorded.', 'export_id' => 'in-' . $lastId]);
    }

    /**
     * Bulk Stock Out with Critical Validation (Locking & Low Stock Warning)
     */
    public function store_bulk_out(Request $request)
    {
        $request->validate([
            'department' => 'required',
            'released_to' => 'required|string',
            'date_released' => 'required|date',
            'line_items' => 'required|array|min:1',
            'line_items.*.item_id' => 'required|exists:items,id',
            'line_items.*.quantity' => 'required|numeric|min:0.1',
        ]);

        $refNo = \Carbon\Carbon::parse($request->date_released)->format('Y-m-d');
        $lowStockItems = [];
        $lastId = null;

        try {
            DB::transaction(function () use ($request, $refNo, &$lowStockItems, &$lastId) {
                foreach ($request->line_items as $itemData) {
                    $item = Item::lockForUpdate()->findOrFail($itemData['item_id']);

                    if ($item->quantity < $itemData['quantity']) {
                        throw new \Exception("Insufficient stock for {$item->name}. Current stock: {$item->quantity}");
                    }

                    $record = StockOut::create([
                        'item_id' => $itemData['item_id'],
                        'quantity' => $itemData['quantity'],
                        'department' => $request->department,
                        'date_released' => $request->date_released,
                        'released_by' => Auth::user()->name,
                        'released_to' => $request->released_to,
                        'purpose' => $request->purpose ?? 'Standard Issuance',
                        'ref_no' => $refNo, 
                    ]);
                    
                    $lastId = $record->id;
                    $item->decrement('quantity', $itemData['quantity']);

                    $threshold = ($item->min_stock > 0) ? $item->min_stock : 10;
                    if ($item->refresh()->quantity <= $threshold) {
                        $lowStockItems[] = "{$item->name} ({$item->quantity} left)";
                    }
                }
            });
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        $payload = ['success' => 'Stock Out recorded.', 'export_id' => 'out-' . $lastId];
        if (!empty($lowStockItems)) {
            $payload['warning'] = "Low stock detected: " . implode(', ', $lowStockItems);
        }

        return back()->with($payload);
    }

    /**
     * Helper para sa PDF Generation (DRY approach)
     */
    private function generatePdfReport($transactions, $title, $receiver = null, $date = null)
    {
        if ($transactions->isEmpty()) return null;

        return Pdf::loadView('pdf.bulk_transactions', [
            'transactions' => $transactions,
            'title' => $title,
            'received_by_name' => $receiver,
            'received_by_date' => $date
        ])->setPaper('letter', 'portrait');
    }

    /**
     * Export lahat ng Stock In sa isang partikular na araw
     */
    public function exportDailyIn(Request $request)
    {
        $request->validate(['date' => 'required|date']);
        
        $transactions = StockIn::whereDate('date_received', $request->date)
            ->with(['item.unit'])->get()->map(fn($trx) => (object)[
                'ref_no' => $trx->ref_no,
                'date' => $trx->date_received,
                'total_quantity' => $trx->quantity,
                'item' => $trx->item,
                'combined_remarks' => "Supplier: " . ($trx->supplier_name ?? 'N/A'),
                'received_by' => $trx->received_by
            ]);

        $pdf = $this->generatePdfReport($transactions, "DAILY STOCK IN REPORT");
        return $pdf ? $pdf->stream("Daily-In-{$request->date}.pdf") : back()->with('error', 'No records found.');
    }

    /**
     * Export lahat ng release para sa isang Department sa isang specific date
     */
    public function exportByDepartment(Request $request)
    {
        $request->validate([
            'department' => 'required|string',
            'date' => 'nullable|date' // Added date for more specific bulk export
        ]);
        
        $query = StockOut::where('department', $request->department);
        
        if ($request->date) {
            $query->whereDate('date_released', $request->date);
        }

        $transactions = $query->with(['item.unit'])->get()->map(fn($trx) => (object)[
                'ref_no' => $trx->ref_no,
                'date' => $trx->date_released,
                'total_quantity' => $trx->quantity, 
                'item' => $trx->item,
                'combined_remarks' => $trx->purpose ?? "",
                'released_by' => $trx->released_by,
                'released_to' => $trx->released_to
            ]);

        if ($transactions->isEmpty()) return back()->with('error', 'No records found.');

        $last = $transactions->last();
        $pdf = $this->generatePdfReport($transactions, "ITEM ISSUANCE FORM", $last->released_to, $last->date);
        return $pdf->stream("Dept-{$request->department}.pdf");
    }

    /**
     * Single PDF Export (ginagamit ng Dashboard/History)
     */
    public function exportPdf($id)
    {
        $type = str_starts_with($id, 'in-') ? 'in' : 'out';
        $realId = str_replace(['in-', 'out-'], '', $id);

        if ($type === 'in') {
            $trx = StockIn::with(['item.unit'])->findOrFail($realId);
            $data = collect([(object)[
                'ref_no' => $trx->ref_no,
                'date' => $trx->date_received,
                'total_quantity' => $trx->quantity,
                'item' => $trx->item,
                'received_by' => $trx->received_by,
                'combined_remarks' => "Supplier: " . ($trx->supplier_name ?? 'N/A')
            ]]);
            return $this->generatePdfReport($data, "STOCK-IN SLIP")->stream("In-{$realId}.pdf");
        } else {
            $trx = StockOut::with(['item.unit'])->findOrFail($realId);
            $data = collect([(object)[
                'ref_no' => $trx->ref_no,
                'date' => $trx->date_released,
                'total_quantity' => $trx->quantity,
                'item' => $trx->item,
                'released_by' => $trx->released_by,
                'released_to' => $trx->released_to,
                'combined_remarks' => "Purpose: " . ($trx->purpose ?? "Standard Issuance")
            ]]);
            return $this->generatePdfReport($data, "ITEM ISSUANCE FORM", $trx->released_to, $trx->date_released)->stream("Out-{$realId}.pdf");
        }
    }
}