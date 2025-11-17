<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura Electrónica</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }
        .header {
            background-color: #111111;
            color: white;
            text-align: center;
            padding: 20px 10px;
        }
        .header img {
            max-width: 150px;
        }
        .header h1 {
            font-size: 20px;
            margin: 10px 0 5px;
        }
        .header p {
            font-size: 16px;
            margin: 0;
        }
        .body {
            padding: 20px;
            color: #333333;
        }
        .body h2 {
            font-size: 18px;
            color: #d32f2f;
        }
        .body p {
            margin: 10px 0;
            line-height: 1.6;
        }
        .body p strong {
            color: #d32f2f;
        }
        .footer {
            background-color: #f0f0f0;
            padding: 15px 20px;
            font-size: 12px;
            color: #555555;
            text-align: center;
        }
        .footer p {
            margin: 5px 0;
        }
        .footer a {
            color: #d32f2f;
            text-decoration: none;
        }
        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="email-container">
    <!-- Header -->
    <div class="header">
        <img src="{{ $message->embed('storage/'.$sale->wherehouse->logo??'')}}" alt="{{$sale->wherehouse->company->name.' - '.$sale->wherehouse->name}}">
        <h1>FACTURA ELECTRÓNICA</h1>
        <p>Notificación de envío de DTE</p>
    </div>

    <!-- Body -->
    <div class="body">
        <p>Estimado/a <strong>{{ trim(($sale->customer->name ?? '') . ' ' . ($sale->customer->last_name ?? '')) }}</strong>,</p>

        <p>Reciba un cordial saludo de parte de <strong>{{ $sale->wherehouse->company->name ?? '' }} - {{ $sale->wherehouse->name ?? '' }}</strong>.</p>

        <p>Nos complace adjuntar la <strong>factura electrónica</strong> correspondiente a su compra por un monto total de <strong>${{ number_format($sale->sale_total ?? 0, 2) }}</strong>.</p>

        <h2>Información del Documento Tributario</h2>
        <p>
            <strong>Código de Generación:</strong> {{ $sale->generationCode }}<br>
            @if($sale->receiptStamp)
            <strong>Sello de Recepción:</strong> {{ $sale->receiptStamp }}<br>
            @endif
            <strong>Fecha de Emisión:</strong> {{ \Carbon\Carbon::parse($sale->operation_date)->format('d/m/Y') }}<br>
            <strong>Total:</strong> ${{ number_format($sale->sale_total ?? 0, 2) }}
        </p>

        <p>
            Conserve este código de generación, ya que es necesario para realizar cualquier gestión relacionada con este documento ante las autoridades fiscales.
        </p>

        <p>
            Si requiere más información o tiene alguna consulta sobre esta factura, no dude en contactarnos:
        </p>
        <p>
            <strong>Teléfono:</strong> {{ $sale->wherehouse->phone ?? 'No disponible' }}<br>
            <strong>Correo:</strong> <a href="mailto:{{ $sale->wherehouse->email ?? '' }}">{{ $sale->wherehouse->email ?? 'No disponible' }}</a>
        </p>

        <p>Agradecemos su preferencia y confianza.</p>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>
            <strong>Nota importante:</strong> Este es un correo automático, favor no responder directamente.
        </p>
        <p>
            Para atención personalizada, contáctenos:<br>
            <strong>Tel:</strong> {{ $sale->wherehouse->phone ?? 'No disponible' }} |
            <strong>Email:</strong> <a href="mailto:{{ $sale->wherehouse->email ?? '' }}">{{ $sale->wherehouse->email ?? 'No disponible' }}</a>
        </p>
        <p style="margin-top: 10px; font-size: 11px; color: #777;">
            {{ $sale->wherehouse->company->name ?? '' }} - {{ $sale->wherehouse->name ?? '' }}<br>
            Documento generado el {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}
        </p>
    </div>
</div>
</body>
</html>
