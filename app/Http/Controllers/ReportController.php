<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    /**
     * Generate and download the inventory report.
     * If the request wants JSON, it returns the raw data instead of a file.
     */
    public function download(Request $request)
    {
        $items = Item::with(['category', 'unit'])->get();

        // API Support: If the client just wants the data in JSON format
        if ($request->wantsJson()) {
            return response()->json([
                'report_name' => 'ALF Inventory Report',
                'generated_at' => date('Y-m-d'),
                'data' => $items->map(function($item) {
                    return [
                        'product_code' => $item->product_code,
                        'name' => $item->name,
                        'category' => $item->category->name ?? 'N/A',
                        'quantity' => $item->quantity,
                        'min_stock' => $item->min_stock,
                        'unit' => $item->unit->name ?? 'N/A',
                        'status' => ($item->quantity <= $item->min_stock) ? 'CRITICAL' : 'HEALTHY'
                    ];
                })
            ]);
        }

        // Default: Web behavior (PDF Download)
        $fileName = 'ALF_Inventory_Report_' . date('Y-m-d') . '.pdf';

        // Convert the logo to Base64 so DomPDF can easily render it
        $logoPath = public_path('images/alf-logo-2022.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        }

        // Load the Blade view and pass the items and logo
        $pdf = Pdf::loadView('reports.inventory', [
            'items' => $items,
            'logoBase64' => $logoBase64
        ]);

        // Optional: Set paper to Landscape if table has many columns
        // $pdf->setPaper('A4', 'landscape');

        return $pdf->download($fileName);
    }
}