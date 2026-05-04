<!DOCTYPE html>
<html>
<head>
    <title>ALF Inventory Report</title>
    <style>
        /* PDF Page Setup */
        @page { 
            size: letter portrait; 
            margin: 30px 40px; 
        }
        
        body { 
            font-family: 'Helvetica', sans-serif; 
            font-size: 10px; 
            color: #333; 
            line-height: 1.2; 
            margin: 0;
            padding: 0;
        }

        /* Header Section */
        .header-table { 
            width: 100%; 
            border-bottom: 2px solid #000; 
            padding-bottom: 8px; 
            margin-bottom: 15px; 
        }

        .report-title { 
            text-align: center; 
            font-size: 13px; 
            font-weight: bold; 
            text-decoration: underline; 
            margin-bottom: 20px; 
            text-transform: uppercase; 
        }

        /* Compact Transaction Containers */
        .transaction-container { 
            margin-bottom: 15px; 
            page-break-inside: avoid; 
        }

        .ref-table { 
            width: 100%; 
            background: #f9f9f9; 
            border: 1px solid #000; 
            margin-bottom: -1px; 
        }

        .ref-table td { 
            padding: 4px 10px; 
            font-weight: bold; 
            text-transform: uppercase; 
            font-size: 8.5px; 
        }

        /* Main Data Table */
        .main-table { 
            width: 100%; 
            border-collapse: collapse; 
            border: 1px solid #000; 
        }

        .main-table th { 
            text-align: left; 
            padding: 4px 6px; 
            border: 1px solid #000; 
            background: #ececec; 
            text-transform: uppercase; 
            font-size: 8px; 
        }

        .main-table td { 
            padding: 4px 6px; 
            border: 1px solid #000; 
            vertical-align: middle; 
            font-size: 9px; 
        }

        /* Signatories */
        .footer-section { 
            width: 100%; 
            margin-top: 40px; 
            page-break-inside: avoid; 
            border-collapse: collapse;
        }

        .sig-box { 
            width: 45%; 
            vertical-align: top; 
        }

        .sig-subtext { 
            font-size: 8px; 
            color: #555; 
            margin-bottom: 2px; 
        }
    </style>
</head>
<body>
    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="text-align: center; vertical-align: middle;">
                <div style="display: inline-block; text-align: left;">
                    <div style="display: inline-block; vertical-align: middle; margin-right: 12px;">
                        <!-- If public_path fails in DomPDF, swap this back to {{ $logoBase64 }} -->
                        <img src="{{ public_path('images/alf-logo-2022.png') }}" style="height: 50px; display: block;">
                    </div>
                    
                    <div style="display: inline-block; vertical-align: middle; text-align: center;">
                        <div style="font-size: 16px; font-weight: bold; color: #551359; line-height: 1.1;">
                            ARELLANO LAW FOUNDATION
                        </div>
                        <div style="font-size: 8.5px; color: #333;">
                            Taft Avenue Corner Menlo Street, Pasay City
                        </div>
                        <div style="font-size: 8.5px; color: #333;">
                            Tel No. 404-3089 to 93
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="report-title">OFFICIAL INVENTORY REPORT</div>

    <div class="transaction-container">
        <!-- Info Banner (Adapted from your ref-table) -->
        <table class="ref-table">
            <tr>
                <td width="50%">DATE GENERATED: <span style="color: #551359;">{{ now()->format('F j, Y') }}</span></td>
            </tr>
        </table>

        <!-- Main Inventory Data -->
        <table class="main-table">
            <thead>
                <tr>
                    <th width="5%" style="text-align: center;">#</th>
                    <th width="15%">Product Code</th>
                    <th width="30%">Item Name</th>
                    <th width="15%">Category</th>
                    <th width="10%" style="text-align: center;">Stock</th>
                    <th width="10%" style="text-align: center;">Min</th>
                    <th width="5%" style="text-align: center;">Unit</th>
                    <th width="10%" style="text-align: center;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    @php
                        $isCritical = $item->quantity <= $item->min_stock;
                        $status = $isCritical ? 'CRITICAL' : 'HEALTHY';
                        $statusColor = $isCritical ? '#d9534f' : '#5cb85c'; /* Bootstrap Red/Green */
                    @endphp
                    <tr>
                        <td style="text-align: center; color: #777;">{{ $loop->iteration }}</td>
                        <td>{{ $item->product_code }}</td>
                        <td><strong>{{ $item->name }}</strong></td>
                        <td>{{ $item->category->name ?? 'N/A' }}</td>
                        <td style="text-align: center; font-weight: bold;">{{ $item->quantity }}</td>
                        <td style="text-align: center;">{{ $item->min_stock }}</td>
                        <td style="text-align: center;">{{ $item->unit->name ?? 'N/A' }}</td>
                        <td style="text-align: center; font-weight: bold; color: {{ $statusColor }};">
                            {{ $status }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 20px; color: #999; font-style: italic;">
                            No items found in inventory.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Signatories -->
    <table class="footer-section">
        <tr>
            <td class="sig-box" style="vertical-align: bottom;">
                <div class="sig-subtext" style="margin-bottom: 25px;">Prepared By:</div>
                
                <div style="border-bottom: 1.5px solid #000; padding-bottom: 2px; height: 18px; text-align: center;">
                    <span style="font-weight: bold; text-transform: uppercase; font-size: 10px;">
                        {{ auth()->user()->name ?? 'INVENTORY CUSTODIAN' }}
                    </span>
                </div>
                
                <div class="sig-subtext" style="margin-top: 5px; font-size: 8px; text-align: center;">
                    Property Office Representative
                </div>
            </td>

            <td style="width: 10%;"></td>

            <td class="sig-box" style="vertical-align: bottom;">
                <!-- Changed 'Received By' to 'Noted By' to better fit an Inventory Report -->
                <div class="sig-subtext" style="margin-bottom: 25px;">Noted By:</div>
                
                <div style="border-bottom: 1.5px solid #000; padding-bottom: 2px; height: 18px; text-align: center;">
                    <span style="font-weight: bold; text-transform: uppercase; font-size: 10px;">
                        &nbsp;
                    </span>
                </div>
                
                <div class="sig-subtext" style="margin-top: 5px; font-size: 8px; text-align: center;">
                    (School Head / Property Custodian)
                </div>
            </td>
        </tr>
    </table>
</body>
</html>