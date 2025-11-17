<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documento Tributario Electrónico</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }

        .header {
            width: 100%;
            text-align: center;
            padding: 10px;
        }

        .footer {
            left: 0;
            width: 100%;
            border: 1px solid black;
            text-align: right;
            font-size: 12px;
        }

        .content {
            padding-bottom: 20px;
        }

        .header img {
            width: 100px;
        }

        .empresa-info, .documento-info, .tabla-productos, .resumen {
            margin: 10px 0;
        }

        .tabla-productos th, .tabla-productos td {
            padding: 5px;
        }

        .tabla-productos th {
            background-color: #f2f2f2;
        }

        .resumen p {
            margin: 5px 0;
            text-align: right;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }
    </style>
</head>
<body>
<!-- Header Empresa -->
<div class="header">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 40%; vertical-align: top; padding: 5px;">
                <table style="width: 100%; text-align: left; border: 1px solid black; border-collapse: collapse;">
                    <tr>
                        <td style="width: 90px; vertical-align: top; padding: 5px;">
                            @if($datos['logo'])
                                <img src="{{ $datos['logo'] }}" alt="Logo Empresa" style="width: 80px;">
                            @endif
                        </td>
                        <td style="vertical-align: top; padding: 5px;">
                            <h3 style="margin: 0 0 5px 0;">{{ $datos['empresa']['nombre'] }}</h3>
                            <p style="font-size: 11px; line-height: 1.3; margin: 0;">
                                NIT: {{ $datos['empresa']['nit'] }}<br>
                                NRC: {{ $datos['empresa']['nrc'] }}
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="font-size: 11px; padding: 5px; line-height: 1.3;">
                            {{ $datos['empresa']['descActividad'] }}<br>
                            {{ $datos['empresa']['direccion']['complemento'] }}<br>
                            Teléfono: {{ $datos['empresa']['telefono'] }}
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 55%; vertical-align: top; text-align: left; border: 1px solid black; font-size: 11px; padding: 10px;">
                <div style="text-align: center; margin-bottom: 10px;">
                    <h3 style="margin: 2px 0;">DOCUMENTO TRIBUTARIO ELECTRÓNICO</h3>
                    <h3 style="margin: 2px 0;">{{ $datos['tipoDocumento'] }}</h3>
                </div>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 2px; width: 45%;">Código generación:</td>
                        <td style="padding: 2px; font-size: 9px;">
                            {{ $datos['DTE']['respuestaHacienda']['codigoGeneracion'] ?? $datos['DTE']['identificacion']['codigoGeneracion'] }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 2px;">Sello de recepción:</td>
                        <td style="padding: 2px; font-size: 9px;">{{ $datos['DTE']['respuestaHacienda']['selloRecibido']??'Contingencia' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 2px;">Número de control:</td>
                        <td style="padding: 2px;">{{ $datos['DTE']['identificacion']['numeroControl'] }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 2px;">Fecha emisión:</td>
                        <td style="padding: 2px;">{{ date('d-m-Y', strtotime($datos['DTE']['identificacion']['fecEmi'])) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 2px;">Hora emisión:</td>
                        <td style="padding: 2px;">{{ $datos['DTE']['identificacion']['horEmi'] }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>

<!-- Contenido principal -->
<div class="content">
    <!-- Info Cliente -->
    <div class="cliente-info" style="margin: 15px 0;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 75%; vertical-align: top; padding: 5px;">
                    <p style="margin: 0; line-height: 1.4;">
                        <strong>Razón Social:</strong> {{ $datos['DTE']['receptor']['nombre']??'' }}<br>
                        <strong>Documento:</strong> {{ $datos['DTE']['receptor']['numDocumento'] ?? '' }}<br>
                        @if(!empty($datos['DTE']['receptor']['nrc']))
                            <strong>NRC:</strong> {{ $datos['DTE']['receptor']['nrc'] }}<br>
                        @endif
                        @if(!empty($datos['DTE']['receptor']['codActividad']))
                            <strong>Actividad:</strong> {{ $datos['DTE']['receptor']['codActividad'] }} - {{ $datos['DTE']['receptor']['descActividad']??'' }}<br>
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
                    </p>
                </td>
                <td style="width: 25%; vertical-align: top; text-align: right; padding: 5px;">
                    <img src="{{ $qr }}" alt="QR Código" style="width: 90px; height: auto;">
                </td>
            </tr>
        </table>
    </div>

    <!-- Tabla Productos -->
    @php
        // Obtener todas las unidades de medida para evitar consultas en el loop
        $unidadesMedidaRaw = \App\Models\UnitMeasurement::pluck('description', 'code')->toArray();
        // Convertir claves a integer para que coincidan con el DTE (que envía int)
        $unidadesMedida = [];
        foreach ($unidadesMedidaRaw as $code => $description) {
            $unidadesMedida[intval(trim($code))] = trim($description);
        }
    @endphp
    <table class="tabla-productos" width="100%" border="1" cellspacing="0" cellpadding="5" style="border-collapse: collapse;">
        <thead>
        <tr style="background-color: #f2f2f2;">
            <th style="padding: 5px; font-size: 9px;">No</th>
            <th style="padding: 5px; font-size: 9px;">Cant</th>
            <th style="padding: 5px; font-size: 9px;">Unidad</th>
            <th style="padding: 5px; font-size: 9px;">Descripción</th>
            <th style="padding: 5px; font-size: 9px;">Precio Unitario</th>
            <th style="padding: 5px; font-size: 9px;">Desc Item</th>
            <th style="padding: 5px; font-size: 9px;">Ventas No Sujetas</th>
            <th style="padding: 5px; font-size: 9px;">Ventas Exentas</th>
            <th style="padding: 5px; font-size: 9px;">Ventas Gravadas</th>
        </tr>
        </thead>
        <tbody>

        @foreach ($datos['DTE']['cuerpo']??$datos['DTE']['cuerpoDocumento'] as $item)
            <tr>
                <td style="padding: 4px; font-size: 9px; text-align: center;">{{ $item['numItem'] }}</td>
                <td style="padding: 4px; font-size: 9px; text-align: center;">{{ $item['cantidad'] }}</td>
                <td style="padding: 4px; font-size: 9px; text-align: center;">{{ $unidadesMedida[$item['uniMedida']] ?? $item['uniMedida'] }}</td>
                <td style="padding: 4px; font-size: 9px; text-align: left;">{{ $item['descripcion'] }}</td>
                <td style="padding: 4px; font-size: 9px; text-align: right;">${{ number_format($item['precioUni'], 2) }}</td>
                <td style="padding: 4px; font-size: 9px; text-align: right;">${{ number_format($item['montoDescu'], 2) }}</td>
                <td style="padding: 4px; font-size: 9px; text-align: right;">${{ number_format($item['ventaNoSuj']??$item['noGravado']??0, 2) }}</td>
                <td style="padding: 4px; font-size: 9px; text-align: right;">${{ number_format($item['ventaExenta']??$item['noGravado']??0, 2) }}</td>
                <td style="padding: 4px; font-size: 9px; text-align: right;">${{ number_format($item['ventaGravada']??$item['compra']??0, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<!-- Footer fijo -->
<div class="footer" style="margin-top: 20px;">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 55%; vertical-align: top; padding: 10px; border-right: 1px solid #ccc;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td colspan="2" style="padding: 5px; font-size: 10px;">
                            <b>VALOR EN LETRAS:</b> {{ $datos["DTE"]['resumen']['totalLetras'] }} DÓLARES
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="background-color: #57595B; color: white; text-align: center; padding: 5px; font-size: 10px;">
                            EXTENSIÓN-INFORMACIÓN ADICIONAL
                        </td>
                    </tr>
                    <tr>
                        @php
                            $ext = $datos['DTE']['extencion'] ?? $datos['DTE']['extension'] ?? null;
                        @endphp
                        <td style="padding: 3px; font-size: 9px;">Entregado por: {{ $ext['nombEntrega']??'S/N' }}</td>
                        <td style="padding: 3px; font-size: 9px;">Recibido por: _____________</td>
                    </tr>
                    <tr>
                        <td style="padding: 3px; font-size: 9px;">N° Documento: {{ $ext['docuEntrega']??'N/A' }}</td>
                        <td style="padding: 3px; font-size: 9px;">N° Documento: _____________</td>
                    </tr>
                    <tr>
                        <td style="padding: 3px; font-size: 9px;">Condición Operación:</td>
                        <td style="padding: 3px; font-size: 9px;">
                            @php
                                $cond = $datos['DTE']['resumen']['condicionOperacion'] ?? 1;
                                echo $cond == 1 ? 'Contado' : ($cond == 2 ? 'Crédito' : 'Otro');
                            @endphp
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding: 3px; font-size: 9px;">Observaciones: {{ $datos['DTE']['observaciones'] ?? '' }}</td>
                    </tr>
                </table>
            </td>
            <td style="width: 43%; vertical-align: top; padding: 10px;">
                <div style="background-color: #57595B; color: white; text-align: center; padding: 5px; font-size: 10px; margin-bottom: 5px;">
                    RESUMEN DE OPERACIÓN
                </div>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 3px; font-size: 9px;">Total No Sujeto:</td>
                        <td style="padding: 3px; font-size: 9px; text-align: right;">${{ number_format($datos['DTE']['resumen']['totalNoSuj']??$datos['DTE']['resumen']['totalNoGravado']??0, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 3px; font-size: 9px;">Total Exento:</td>
                        <td style="padding: 3px; font-size: 9px; text-align: right;">${{ number_format($datos['DTE']['resumen']['totalExenta']??$datos['DTE']['resumen']['totalNoGravado']??0, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 3px; font-size: 9px;">Total Gravadas:</td>
                        <td style="padding: 3px; font-size: 9px; text-align: right;">${{ number_format($datos['DTE']['resumen']['totalGravada']??$datos['DTE']['resumen']['totalCompra'], 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 3px; font-size: 9px;">Subtotal:</td>
                        <td style="padding: 3px; font-size: 9px; text-align: right;">${{ number_format($datos['DTE']['resumen']['subTotal']??$datos['DTE']['resumen']['totalGravada'], 2) }}</td>
                    </tr>
                    @isset($datos['DTE']['resumen']['tributos'])
                        @foreach($datos['DTE']['resumen']['tributos'] as $tributo)
                            <tr>
                                <td style="padding: 3px; font-size: 9px;">{{ $tributo['descripcion'] }}:</td>
                                <td style="padding: 3px; font-size: 9px; text-align: right;">${{ number_format($tributo['valor'], 2) }}</td>
                            </tr>
                        @endforeach
                    @endisset
                    <tr style="background-color: #57595B; color: white;">
                        <td style="padding: 5px; font-size: 10px; font-weight: bold;">
                            TOTAL A PAGAR:
                        </td>
                        <td style="padding: 5px; font-size: 10px; font-weight: bold; text-align: right;">
                            ${{ number_format($datos['DTE']['resumen']['totalPagar']??$datos['DTE']['resumen']['montoTotalOperacion'], 2) }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>

<p style="text-align: center; font-size: 8px; margin-top: 15px; color: #666;">
    Documento generado electrónicamente según normativa de El Salvador
</p>
</body>
</html>
