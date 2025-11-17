<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documento Tributario Electrónico</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10px;
            color: #333;
            padding: 15px;
            line-height: 1.4;
        }

        /* Encabezado principal */
        .header {
            margin-bottom: 15px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        /* Sección Empresa */
        .empresa-box {
            border: 2px solid #2c3e50;
            border-radius: 8px;
            padding: 12px;
            background-color: #f8f9fa;
        }

        .empresa-content {
            width: 100%;
            border-collapse: collapse;
        }

        .empresa-logo {
            width: 100px;
            vertical-align: top;
            padding-right: 12px;
        }

        .empresa-logo img {
            width: 90px;
            height: auto;
        }

        .empresa-datos {
            vertical-align: top;
        }

        .empresa-datos h3 {
            font-size: 14px;
            color: #2c3e50;
            margin-bottom: 6px;
            font-weight: bold;
        }

        .empresa-datos .nit-nrc {
            font-size: 11px;
            margin-bottom: 8px;
            color: #555;
        }

        .empresa-datos .info-adicional {
            font-size: 10px;
            color: #666;
            line-height: 1.5;
        }

        /* Sección Documento */
        .documento-box {
            border: 2px solid #27ae60;
            border-radius: 8px;
            padding: 12px;
            background-color: #f0f9f4;
        }

        .documento-titulo {
            text-align: center;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 2px solid #27ae60;
        }

        .documento-titulo h3 {
            font-size: 12px;
            color: #27ae60;
            margin: 2px 0;
            font-weight: bold;
            text-transform: uppercase;
        }

        .documento-datos {
            width: 100%;
            border-collapse: collapse;
        }

        .documento-datos td {
            padding: 4px 6px;
            font-size: 10px;
        }

        .documento-datos td:first-child {
            font-weight: bold;
            color: #555;
            width: 42%;
        }

        .documento-datos td:last-child {
            color: #333;
            font-size: 9px;
        }

        /* Sección Cliente y QR */
        .cliente-section {
            margin: 15px 0;
            padding: 12px 0;
            border-top: 2px solid #e0e0e0;
            border-bottom: 2px solid #e0e0e0;
            background-color: #fafafa;
        }

        .cliente-table {
            width: 100%;
            border-collapse: collapse;
        }

        .cliente-datos {
            width: 72%;
            vertical-align: top;
            padding: 8px;
        }

        .cliente-titulo {
            font-size: 11px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 8px;
            padding: 5px;
            background-color: #ecf0f1;
            border-left: 4px solid #3498db;
        }

        .cliente-info {
            font-size: 10px;
            line-height: 1.6;
            color: #555;
        }

        .cliente-info strong {
            color: #2c3e50;
        }

        .cliente-qr {
            width: 28%;
            vertical-align: top;
            text-align: center;
            padding: 8px;
        }

        .cliente-qr img {
            width: 110px;
            height: auto;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            padding: 5px;
            background-color: white;
        }

        /* Tabla de Productos */
        .tabla-productos {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 9px;
        }

        .tabla-productos thead th {
            background-color: #34495e;
            color: #ffffff;
            padding: 8px 5px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #2c3e50;
            font-size: 9px;
        }

        .tabla-productos tbody td {
            padding: 6px 5px;
            border: 1px solid #ddd;
            text-align: center;
        }

        .tabla-productos tbody td.descripcion {
            text-align: left;
        }

        .tabla-productos tbody td.numero {
            text-align: right;
        }

        .tabla-productos tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .tabla-productos tbody tr:hover {
            background-color: #e8f4f8;
        }

        /* Footer - Resumen */
        .footer {
            margin-top: 20px;
            border: 2px solid #2c3e50;
            border-radius: 8px;
            overflow: hidden;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .footer-left {
            width: 55%;
            vertical-align: top;
            padding: 12px;
            border-right: 2px solid #e0e0e0;
        }

        .footer-right {
            width: 45%;
            vertical-align: top;
            padding: 12px;
            background-color: #f8f9fa;
        }

        .footer-header {
            background-color: #34495e;
            color: #ffffff;
            padding: 8px;
            text-align: center;
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
        }

        .valor-letras {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 4px;
            padding: 8px;
            margin-bottom: 10px;
            font-size: 10px;
            font-weight: bold;
            color: #856404;
        }

        .info-adicional-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-adicional-table td {
            padding: 4px 6px;
            font-size: 9px;
        }

        .info-adicional-table td:first-child {
            font-weight: bold;
            color: #555;
            width: 45%;
        }

        .totales-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totales-table td {
            padding: 5px 8px;
            font-size: 10px;
            border-bottom: 1px solid #e0e0e0;
        }

        .totales-table td:first-child {
            font-weight: bold;
            color: #555;
            text-align: left;
        }

        .totales-table td:last-child {
            text-align: right;
            color: #333;
        }

        .total-final {
            background-color: #27ae60;
            color: #ffffff !important;
            font-weight: bold;
            font-size: 11px;
        }

        .total-final td {
            color: #ffffff !important;
            border-bottom: none;
            padding: 8px;
        }

        /* Pie de página */
        .documento-footer {
            text-align: center;
            font-size: 8px;
            color: #999;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #e0e0e0;
        }

        /* Utilidades */
        .text-bold {
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- ENCABEZADO -->
    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width: 40%; padding: 10px; border: 2px solid #333; vertical-align: top;">
                    <!-- Logo + Nombre en la misma línea -->
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
                        <tr>
                            @if(!empty($datos['logo']))
                            <td style="width: 100px; vertical-align: middle; padding-right: 12px;">
                                <img src="{{ $datos['logo'] }}" alt="Logo Empresa" style="width: 90px; height: auto;">
                            </td>
                            @endif
                            <td style="vertical-align: middle;">
                                <h3 style="font-size: 14px; margin: 0; color: #2c3e50; font-weight: bold;">{{ $datos['empresa']['nombre'] }}</h3>
                            </td>
                        </tr>
                    </table>

                    <!-- Información adicional debajo -->
                    <div style="font-size: 10px; line-height: 1.5; color: #555; padding-top: 8px; border-top: 1px solid #ddd;">
                        <div style="margin-bottom: 5px;">
                            <strong>NIT:</strong> {{ $datos['empresa']['nit'] }} | <strong>NRC:</strong> {{ $datos['empresa']['nrc'] }}
                        </div>
                        <div style="margin-bottom: 3px;">
                            {{ $datos['empresa']['descActividad'] }}
                        </div>
                        <div style="margin-bottom: 3px;">
                            {{ $datos['empresa']['direccion']['complemento'] }}
                        </div>
                        <div>
                            <strong>Tel:</strong> {{ $datos['empresa']['telefono'] }}
                        </div>
                    </div>
                </td>
                <td style="width: 60%; padding: 10px; border: 2px solid #333; border-left: none; vertical-align: top;">
                    <div style="text-align: center; margin-bottom: 10px; padding-bottom: 8px; border-bottom: 2px solid #27ae60;">
                        <h3 style="font-size: 12px; color: #27ae60; margin: 2px 0; font-weight: bold; text-transform: uppercase;">Documento Tributario Electrónico</h3>
                        <h3 style="font-size: 12px; color: #27ae60; margin: 2px 0; font-weight: bold; text-transform: uppercase;">{{ $datos['tipoDocumento'] }}</h3>
                    </div>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 4px 6px; font-size: 10px; font-weight: bold; color: #555; width: 42%;">Código de generación:</td>
                            <td style="padding: 4px 6px; font-size: 9px; color: #333;">{{ $datos['DTE']['respuestaHacienda']['codigoGeneracion'] ?? $datos['DTE']['identificacion']['codigoGeneracion'] }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 6px; font-size: 10px; font-weight: bold; color: #555;">Sello de recepción:</td>
                            <td style="padding: 4px 6px; font-size: 9px; color: #333;">{{ $datos['DTE']['respuestaHacienda']['selloRecibido'] ?? 'CONTINGENCIA' }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 6px; font-size: 10px; font-weight: bold; color: #555;">Número de control:</td>
                            <td style="padding: 4px 6px; font-size: 9px; color: #333;">{{ $datos['DTE']['identificacion']['numeroControl'] }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 6px; font-size: 10px; font-weight: bold; color: #555;">Fecha de emisión:</td>
                            <td style="padding: 4px 6px; font-size: 9px; color: #333;">{{ date('d/m/Y', strtotime($datos['DTE']['identificacion']['fecEmi'])) }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 6px; font-size: 10px; font-weight: bold; color: #555;">Hora de emisión:</td>
                            <td style="padding: 4px 6px; font-size: 9px; color: #333;">{{ $datos['DTE']['identificacion']['horEmi'] }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <!-- INFORMACIÓN DEL CLIENTE Y QR -->
    <div class="cliente-section">
        <table class="cliente-table">
            <tr>
                <td class="cliente-datos">
                    <div class="cliente-titulo">INFORMACIÓN DEL RECEPTOR</div>
                    <div class="cliente-info">
                        <strong>Razón Social:</strong> {{ $datos['DTE']['receptor']['nombre'] ?? 'N/A' }}<br>
                        <strong>Documento:</strong> {{ $datos['DTE']['receptor']['numDocumento'] ?? 'N/A' }}
                        @if(!empty($datos['DTE']['receptor']['nrc']))
                            | <strong>NRC:</strong> {{ $datos['DTE']['receptor']['nrc'] }}
                        @endif
                        <br>
                        @if(!empty($datos['DTE']['receptor']['codActividad']))
                            <strong>Actividad Económica:</strong> {{ $datos['DTE']['receptor']['codActividad'] }} - {{ $datos['DTE']['receptor']['descActividad'] ?? '' }}<br>
                        @endif
                        @if(!empty($datos['DTE']['receptor']['direccion']['complemento']))
                            <strong>Dirección:</strong> {{ $datos['DTE']['receptor']['direccion']['complemento'] }}<br>
                        @endif
                        @if(!empty($datos['DTE']['receptor']['telefono']))
                            <strong>Teléfono:</strong> {{ $datos['DTE']['receptor']['telefono'] }}
                        @endif
                        @if(!empty($datos['DTE']['receptor']['correo']))
                            | <strong>Correo:</strong> {{ $datos['DTE']['receptor']['correo'] }}
                        @endif
                    </div>
                </td>
                <td class="cliente-qr">
                    <img src="{{ $qr }}" alt="Código QR">
                </td>
            </tr>
        </table>
    </div>

    <!-- TABLA DE PRODUCTOS/SERVICIOS -->
    @php
        // Obtener todas las unidades de medida para evitar consultas en el loop
        $unidadesMedidaRaw = \App\Models\UnitMeasurement::pluck('description', 'code')->toArray();
        // Convertir claves a integer para que coincidan con el DTE (que envía int)
        $unidadesMedida = [];
        foreach ($unidadesMedidaRaw as $code => $description) {
            $unidadesMedida[intval(trim($code))] = trim($description);
        }
    @endphp
    <table class="tabla-productos">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 6%;">Cant.</th>
                <th style="width: 7%;">Unidad</th>
                <th style="width: 33%;">Descripción</th>
                <th style="width: 10%;">P. Unitario</th>
                <th style="width: 10%;">Descuento</th>
                <th style="width: 10%;">No Sujetas</th>
                <th style="width: 10%;">Exentas</th>
                <th style="width: 10%;">Gravadas</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($datos['DTE']['cuerpo'] ?? $datos['DTE']['cuerpoDocumento'] as $item)
            <tr>
                <td>{{ $item['numItem'] }}</td>
                <td>{{ $item['cantidad'] }}</td>
                <td>{{ $unidadesMedida[$item['uniMedida']] ?? $item['uniMedida'] }}</td>
                <td class="descripcion">{{ $item['descripcion'] }}</td>
                <td class="numero">${{ number_format($item['precioUni'], 2) }}</td>
                <td class="numero">${{ number_format($item['montoDescu'], 2) }}</td>
                <td class="numero">${{ number_format($item['ventaNoSuj'] ?? $item['noGravado'] ?? 0, 2) }}</td>
                <td class="numero">${{ number_format($item['ventaExenta'] ?? $item['noGravado'] ?? 0, 2) }}</td>
                <td class="numero">${{ number_format($item['ventaGravada'] ?? $item['compra'] ?? 0, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- FOOTER CON RESUMEN Y TOTALES -->
    <div class="footer">
        <table class="footer-table">
            <tr>
                <td class="footer-left">
                    <div class="valor-letras">
                        <strong>VALOR EN LETRAS:</strong> {{ strtoupper($datos['DTE']['resumen']['totalLetras']) }} DÓLARES
                    </div>

                    <div class="footer-header">EXTENSIÓN - INFORMACIÓN ADICIONAL</div>

                    <table class="info-adicional-table">
                        @php
                            $ext = $datos['DTE']['extencion'] ?? $datos['DTE']['extension'] ?? null;
                        @endphp
                        <tr>
                            <td>Entregado por:</td>
                            <td>{{ $ext['nombEntrega'] ?? 'Sin nombre' }}</td>
                        </tr>
                        <tr>
                            <td>Documento entrega:</td>
                            <td>{{ $ext['docuEntrega'] ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td>Recibido por:</td>
                            <td>_________________________________</td>
                        </tr>
                        <tr>
                            <td>Documento recepción:</td>
                            <td>_________________________________</td>
                        </tr>
                        <tr>
                            <td>Condición de Operación:</td>
                            <td>
                                @php
                                    $condicion = $datos['DTE']['resumen']['condicionOperacion'] ?? 1;
                                    echo $condicion == 1 ? 'Contado' : ($condicion == 2 ? 'Crédito' : 'Otro');
                                @endphp
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="padding-top: 8px;"><strong>Observaciones:</strong></td>
                        </tr>
                        <tr>
                            <td colspan="2">{{ $datos['DTE']['observaciones'] ?? '___________________________________' }}</td>
                        </tr>
                    </table>
                </td>
                <td class="footer-right">
                    <div class="footer-header">RESUMEN DE OPERACIÓN</div>

                    <table class="totales-table">
                        <tr>
                            <td>Total No Sujeto:</td>
                            <td>${{ number_format($datos['DTE']['resumen']['totalNoSuj'] ?? $datos['DTE']['resumen']['totalNoGravado'] ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Total Exento:</td>
                            <td>${{ number_format($datos['DTE']['resumen']['totalExenta'] ?? $datos['DTE']['resumen']['totalNoGravado'] ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Total Gravadas:</td>
                            <td>${{ number_format($datos['DTE']['resumen']['totalGravada'] ?? $datos['DTE']['resumen']['totalCompra'] ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Subtotal:</td>
                            <td>${{ number_format($datos['DTE']['resumen']['subTotal'] ?? $datos['DTE']['resumen']['totalGravada'] ?? 0, 2) }}</td>
                        </tr>
                        @isset($datos['DTE']['resumen']['tributos'])
                            @foreach($datos['DTE']['resumen']['tributos'] as $tributo)
                            <tr>
                                <td>{{ $tributo['descripcion'] }}:</td>
                                <td>${{ number_format($tributo['valor'], 2) }}</td>
                            </tr>
                            @endforeach
                        @endisset
                        <tr class="total-final">
                            <td>TOTAL A PAGAR:</td>
                            <td>${{ number_format($datos['DTE']['resumen']['totalPagar'] ?? $datos['DTE']['resumen']['montoTotalOperacion'] ?? 0, 2) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <!-- PIE DE PÁGINA -->
    <div class="documento-footer">
        Documento generado electrónicamente según normativa de El Salvador | Sistema de Facturación Electrónica
    </div>
</body>
</html>
