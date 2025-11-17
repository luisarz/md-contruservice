<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compra - {{ $purchase->document_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 10px;
            color: #333;
            line-height: 1.3;
            padding: 15px;
            background: #fff;
        }

        .document-container {
            max-width: 100%;
            margin: 0 auto;
            position: relative;
        }

        /* Header Styles */
        .header-section {
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }

        .company-header {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }

        .company-info {
            display: table-cell;
            width: 65%;
            vertical-align: middle;
        }

        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 2px;
        }

        .company-branch {
            font-size: 11px;
            color: #7f8c8d;
            margin-bottom: 2px;
        }

        .document-title {
            font-size: 13px;
            font-weight: bold;
            color: #e74c3c;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .document-info-box {
            display: table-cell;
            width: 35%;
            vertical-align: middle;
            text-align: right;
        }

        .doc-number {
            font-size: 14px;
            font-weight: bold;
            color: #2c3e50;
            background: #ecf0f1;
            padding: 5px 12px;
            border-radius: 4px;
            display: inline-block;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 9px;
            font-weight: bold;
            margin-top: 3px;
        }

        .status-procesando { background: #f39c12; color: white; }
        .status-finalizado { background: #27ae60; color: white; }
        .status-anulado { background: #e74c3c; color: white; }

        /* Info Grid */
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }

        .info-row {
            display: table-row;
        }

        .info-cell {
            display: table-cell;
            padding: 5px 8px;
            border-bottom: 1px solid #ecf0f1;
            width: 25%;
        }

        .info-row:last-child .info-cell {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #7f8c8d;
            font-size: 8px;
            text-transform: uppercase;
            display: block;
            margin-bottom: 1px;
        }

        .info-value {
            color: #2c3e50;
            font-size: 10px;
            font-weight: 500;
        }

        /* Supplier Section */
        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #2c3e50;
            background: #ecf0f1;
            padding: 5px 10px;
            border-left: 3px solid #3498db;
            margin: 12px 0 6px 0;
        }

        .supplier-box {
            border: 1px solid #ddd;
            border-radius: 3px;
            padding: 8px;
            background: #fafafa;
        }

        .supplier-row {
            display: table;
            width: 100%;
            margin-bottom: 5px;
        }

        .supplier-row:last-child {
            margin-bottom: 0;
        }

        .supplier-field {
            display: table-cell;
            width: 50%;
            padding-right: 8px;
        }

        /* Products Table */
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
            border: 1px solid #2c3e50;
        }

        .products-table thead {
            background: #2c3e50;
            color: white;
        }

        .products-table th {
            padding: 6px 5px;
            text-align: left;
            font-weight: 600;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border: 1px solid #2c3e50;
        }

        .products-table th.text-right {
            text-align: right;
        }

        .products-table tbody tr {
            border-bottom: 1px solid #ddd;
        }

        .products-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .products-table td {
            padding: 5px;
            font-size: 9px;
            color: #2c3e50;
            border: 1px solid #ddd;
        }

        .products-table td.text-right {
            text-align: right;
        }

        .products-table td.text-center {
            text-align: center;
        }

        .product-code {
            font-family: 'Courier New', monospace;
            background: #ecf0f1;
            padding: 1px 4px;
            border-radius: 2px;
            font-size: 8px;
        }

        /* Totals Section */
        .totals-section {
            margin-top: 12px;
        }

        .totals-container {
            display: table;
            width: 100%;
        }

        .totals-left {
            display: table-cell;
            width: 50%;
            padding-right: 10px;
            vertical-align: top;
        }

        .totals-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .amount-words {
            border: 1px solid #ddd;
            border-radius: 3px;
            padding: 8px;
            background: #f8f9fa;
        }

        .amount-words-label {
            font-size: 8px;
            text-transform: uppercase;
            color: #7f8c8d;
            font-weight: 600;
            margin-bottom: 3px;
        }

        .amount-words-value {
            font-size: 9px;
            color: #2c3e50;
            font-weight: 500;
        }

        .totals-box {
            border: 2px solid #2c3e50;
            border-radius: 3px;
            overflow: hidden;
        }

        .total-row {
            display: table;
            width: 100%;
            border-bottom: 1px solid #ecf0f1;
        }

        .total-row:last-child {
            border-bottom: none;
            background: #2c3e50;
        }

        .total-label {
            display: table-cell;
            padding: 5px 8px;
            text-align: right;
            font-weight: 600;
            color: #7f8c8d;
            font-size: 9px;
            width: 60%;
        }

        .total-value {
            display: table-cell;
            padding: 5px 8px;
            text-align: right;
            font-weight: bold;
            color: #2c3e50;
            font-size: 10px;
            width: 40%;
        }

        .total-row:last-child .total-label,
        .total-row:last-child .total-value {
            color: white;
            font-size: 11px;
            padding: 8px;
        }

        /* Anulado Watermark */
        .watermark-anulado {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 120px;
            font-weight: bold;
            color: rgba(231, 76, 60, 0.08);
            z-index: -1;
            pointer-events: none;
            white-space: nowrap;
        }

        .alert-anulado {
            border: 2px solid #e74c3c;
            background: #fadbd8;
            padding: 8px;
            border-radius: 3px;
            margin-top: 12px;
            text-align: center;
        }

        .alert-anulado-text {
            color: #c0392b;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Footer */
        .footer {
            margin-top: 15px;
            padding-top: 8px;
            border-top: 1px solid #ecf0f1;
            text-align: center;
            font-size: 8px;
            color: #95a5a6;
        }

        .print-date {
            font-style: italic;
        }
    </style>
</head>
<body>
    <!-- Watermark si está anulado -->
    @if($purchase->status === 'Anulado')
    <div class="watermark-anulado">ANULADO</div>
    @endif

    <div class="document-container">
        <!-- Header Section -->
        <div class="header-section">
            <div class="company-header">
                <div class="company-info">
                    <div class="company-name">{{ $empresa->name }}</div>
                    <div class="company-branch">{{ $purchase->wherehouse->name }}</div>
                    <div class="document-title">Comprobante de Compra</div>
                </div>
                <div class="document-info-box">
                    <div class="doc-number">{{ $purchase->document_number }}</div>
                    <br>
                    <span class="status-badge status-{{ strtolower($purchase->status) }}">
                        {{ $purchase->status }}
                    </span>
                </div>
            </div>

            <!-- Info Grid -->
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-cell">
                        <span class="info-label">Tipo Documento</span>
                        <span class="info-value">{{ $purchase->document_type }}</span>
                    </div>
                    <div class="info-cell">
                        <span class="info-label">Fecha de Compra</span>
                        <span class="info-value">{{ date('d/m/Y H:i', strtotime($purchase->purchase_date)) }}</span>
                    </div>
                    <div class="info-cell">
                        <span class="info-label">Condición</span>
                        <span class="info-value">{{ $purchase->purchase_condition }}</span>
                    </div>
                    <div class="info-cell">
                        <span class="info-label">Días Crédito</span>
                        <span class="info-value">{{ $purchase->credit_days ?? 'Contado' }}</span>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-cell">
                        <span class="info-label">Responsable</span>
                        <span class="info-value">{{ $purchase->employee->name ?? '' }} {{ $purchase->employee->last_name ?? '' }}</span>
                    </div>
                    <div class="info-cell">
                        <span class="info-label">Kardex</span>
                        <span class="info-value">{{ $purchase->kardex_generated ? ' Generado' : ' Pendiente' }}</span>
                    </div>
                    <div class="info-cell">
                        <span class="info-label">Percepción</span>
                        <span class="info-value">{{ $purchase->have_perception ? 'Si (1%)' : 'No' }}</span>
                    </div>
                    <div class="info-cell">
                        <span class="info-label">Estado Pago</span>
                        <span class="info-value">{{ $purchase->paid ? 'Pagado' : 'Pendiente' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Supplier Section -->
        <div class="section-title">Información del Proveedor</div>
        <div class="supplier-box">
            <div class="supplier-row">
                <div class="supplier-field">
                    <span class="info-label">Nombre / Razón Social</span>
                    <span class="info-value">{{ $purchase->provider->comercial_name ?? $purchase->provider->legal_name ?? 'N/A' }}</span>
                </div>
                <div class="supplier-field">
                    <span class="info-label">NIT</span>
                    <span class="info-value">{{ $purchase->provider->nit ?? 'N/A' }}</span>
                </div>
            </div>
            <div class="supplier-row">
                <div class="supplier-field">
                    <span class="info-label">Dirección</span>
                    <span class="info-value">{{ $purchase->provider->direction ?? 'N/A' }}</span>
                </div>
                <div class="supplier-field">
                    <span class="info-label">Teléfono</span>
                    <span class="info-value">{{ $purchase->provider->phone_one ?? 'N/A' }}</span>
                </div>
            </div>
        </div>

        <!-- Products Section -->
        <div class="section-title">Detalle de Productos</div>
        <table class="products-table">
            <thead>
                <tr>
                    <th style="width: 5%;" class="text-center">#</th>
                    <th style="width: 12%;">Código</th>
                    <th style="width: 35%;">Descripción del Producto</th>
                    <th style="width: 8%;" class="text-center">U.M.</th>
                    <th style="width: 10%;" class="text-right">Cantidad</th>
                    <th style="width: 10%;" class="text-right">Precio Unit.</th>
                    <th style="width: 8%;" class="text-right">Desc.</th>
                    <th style="width: 12%;" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @php $contador = 1; @endphp
                @foreach($purchase->purchaseItems as $item)
                <tr>
                    <td class="text-center">{{ $contador++ }}</td>
                    <td>
                        <span class="product-code">{{ $item->inventory->product->bar_code ?? 'N/A' }}</span>
                    </td>
                    <td>{{ $item->inventory->product->name ?? 'N/A' }}</td>
                    <td class="text-center">{{ $item->inventory->product->unitmeasurement->description ?? 'N/A' }}</td>
                    <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                    <td class="text-right">${{ number_format($item->price, 2) }}</td>
                    <td class="text-right">{{ number_format($item->discount, 2) }}%</td>
                    <td class="text-right"><strong>${{ number_format($item->total, 2) }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals Section -->
        <div class="totals-section">
            <div class="totals-container">
                <div class="totals-left">
                    <div class="amount-words">
                        <div class="amount-words-label">Total en Letras</div>
                        <div class="amount-words-value">{{ $montoLetras }}</div>
                    </div>
                </div>
                <div class="totals-right">
                    <div class="totals-box">
                        <div class="total-row">
                            <div class="total-label">Subtotal:</div>
                            <div class="total-value">${{ number_format($purchase->net_value, 2) }}</div>
                        </div>
                        <div class="total-row">
                            <div class="total-label">IVA (13%):</div>
                            <div class="total-value">${{ number_format($purchase->taxe_value, 2) }}</div>
                        </div>
                        @if($purchase->have_perception)
                        <div class="total-row">
                            <div class="total-label">Percepción (1%):</div>
                            <div class="total-value">${{ number_format($purchase->perception_value, 2) }}</div>
                        </div>
                        @endif
                        <div class="total-row">
                            <div class="total-label">TOTAL A PAGAR:</div>
                            <div class="total-value">${{ number_format($purchase->purchase_total, 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert si está anulado -->
        @if($purchase->status === 'Anulado')
        <div class="alert-anulado">
            <div class="alert-anulado-text">⚠ Este Documento Ha Sido Anulado ⚠</div>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <div class="print-date">Documento generado el {{ date('d/m/Y') }} a las {{ date('H:i:s') }}</div>
            <div>Sistema de Gestión - {{ $empresa->name }}</div>
        </div>
    </div>
</body>
</html>
