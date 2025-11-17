<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class SalesExportFac implements FromCollection, WithHeadings, WithEvents, WithColumnFormatting
{
    protected string $documentType;
    protected Carbon $startDate;
    protected Carbon $endDate;
    protected float $totalGravada = 0;
    protected float $totalDebitoFiscal = 0;
    protected float $totalVenta = 0;

    public function __construct(string $startDate, string $endDate)
    {
        $this->startDate = Carbon::parse($startDate);
        $this->endDate = Carbon::parse($endDate);
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Clase',
            'Tipo',
            'Sello de recepcion',
            'Codigo de generacion',
            'Numero de control',
            'NRC',
            'NIT',
            'DUI',
            'Razon Social',
            'Exenta',
            'No Sujeta',
            'Gravada Local',
            'Débito Fiscal',
            'Retencion 1%',
            'ISR 10%',
            'Total Venta',
            'Estado',
            'fecha MH',
            'Turismo 5%',
        ];


    }

    public function collection(): Collection
    {
        set_time_limit(0);
        $sales = Sale::select(
            'id',
            'operation_date as fecha',
            'sale_total as venta_gravada',
            'net_amount as neto',
            'taxe as iva',
            'sale_total as total',
            'document_type_id',
            'document_internal_number',
            'seller_id',
            'customer_id',
            'operation_condition_id',
            'payment_method_id',
            'sale_status',
            'billing_model',
            'transmision_type',
            'is_dte',
            'is_hacienda_send'

        )
            ->where('is_dte', '1')
            ->whereIn('document_type_id', [1, 3, 5, 11, 14])//1- Fac 3-CCF 5-NC 11-FExportacion 14-Sujeto excluido
            ->whereBetween('operation_date', [$this->startDate, $this->endDate])
            ->orderBy('operation_date', 'asc')
            ->with(['dteProcesado' => function ($query) {
                $query->select('sales_invoice_id', 'num_control', 'selloRecibido', 'codigoGeneracion', 'fhProcesamiento', 'dte')
                    ->whereNotNull('selloRecibido');
            },
                'documenttype', 'customer', 'billingModel', 'salescondition', 'seller'])
            ->get()
            ->map(function ($sale) {
//                dd($sale);
                $persontype = $sale->customer->person_type_id ?? 1; // 1: Natural, 2: Jurídica

                $rawNit = $sale->customer->nit??'';
                $rawDui = $sale->customer->dui??'';

                $cleanNit = str_replace('-', '', $rawNit);
                $formatNit = str_repeat('0', strlen($cleanNit)); // Ejemplo: '00000000000000'
                $cleanDUI = str_replace('-', '', $rawDui);
                $formatDUI = str_repeat('0', strlen($cleanDUI)); // Ejemplo: '00000000000000'
                $nit = ($rawNit === "0000-000000-000-0") ? null
                    : '=TEXT("' . $cleanNit . '","' . $formatNit . '")';
                $dui = ($rawDui === "00000000-0") ? null
                    : '=TEXT("' . $cleanDUI . '","' . $formatDUI . '")';

                $nit_report = '';
                $dui_report = '';

                // Comparar valores sin formato, no fórmulas
                $isSame = $cleanNit === $cleanDUI;

                if ($persontype == 2) { // Jurídica
                    $nit_report = $nit;
                    $dui_report = $isSame ? '' : $dui;
                } else { // Natural
                    $dui_report = $dui;
                    $nit_report = $isSame ? '' : $nit;
                }

                $json = $sale->dteProcesado->dte ?? null;
                $resumen = $json['resumen'] ?? null;
                // Acceder al resumen

                // Extraer los valores que pediste
                $totalGravada = $resumen['totalGravada'];
                $totalGravada_rep= $totalGravada;
                $total = $resumen['totalPagar'];
                $montoTotal = $resumen['montoTotalOperacion'];
                $tributos = $resumen['tributos'];
                $totalIva = $resumen['totalIva'] ?? 0;
                $retencion_1 = $resumen['ivaRete1'] ?? 0;
                $retencion_10 = $resumen['reteRenta'] ?? 0;
                $iva = 0;
                $turismo = 0;





                if (isset($resumen['tributos']) && is_array($resumen['tributos'])) {
                    foreach ($resumen['tributos'] as $tributo) {
                        if (($tributo['codigo'] ?? null) === '20') {
                            $iva = $tributo['valor'] ?? 0;
                        }
                        if (($tributo['codigo'] ?? null) === '59') {
                            $turismo = $tributo['valor'] ?? 0;
                        }
                    }
                }

                if($iva==0){
                    $iva=$resumen['totalIva'] ?? 0;
                }

                if($sale->document_type_id==1){
                    $totalGravada_rep -= $iva;
//                    dd($totalGravada_rep);
                }

                if ($sale->sale_status == "Anulado") {
                    $sale->sale_status = "Invalidado";
                    $totalGravada_rep = 0;
                    $iva = 0;
                    $retencion_1 = 0;
                    $turismo = 0;
                    $retencion_10 = 0;
                }


                return [
                    'fecha' => date('d/m/Y', strtotime($sale->fecha)),
//                    'fecha' => Carbon::parse($sale->operation_date)->format('d/m/Y'),
                    'document_type' => '4',
                    'type' => $sale->documenttype->code,
                    'sello_recepcion' => $sale->dteProcesado->selloRecibido ?? null,
                    'cod_generaicon' => $sale->dteProcesado->codigoGeneracion ?? null,
                    'num_control' => $sale->dteProcesado->num_control ?? null,//DTE
//                    'internal_number' => $sale->id,
//                    'num_inicial' => $sale->dteProcesado->num_control ?? null,
//                    'num_final' => $sale->dteProcesado->num_control ?? null,
                    'nrc' => $sale->customer->nrc ?? null,


//                    'nit' => ($nit == $dui) ? '' : (string)$nit,
//                    'dui' => (string)$dui,
                    'nit' => $nit_report,
                    'dui' => $dui_report,
                    'nombre' => $sale->customer->fullname ?? null,
                    'exentas' => $sale->exentas ?? null,
                    'no_sujetas' => $sale->no_sujetas ?? null,
//                    'venta_gravada' => $sale->venta_gravada,
                    'neto' => $totalGravada_rep ?? 0,
                    'iva' => $iva ?? 0,
                    'retencion_1_percetage' => $retencion_1 ?? 0,
                    'isr_10_percentage' => $retencion_10 ?? 0,
                    'total' =>($totalGravada_rep+$iva)-($retencion_1+$retencion_10) ?? 0,
                    'estado' => strtoupper($sale->sale_status),
                    'fecha_mh' => date('d/m/Y', strtotime($sale->dteProcesado->fhProcesamiento)),
                    'turismo_5' => $turismo ?? 0,
                ];
            });

        return $sales;
    }

    public function columnFormats(): array
    {
        return [
//            'G' => NumberFormat::FORMAT_TEXT,
//            'H' => NumberFormat::FORMAT_TEXT,
//            'I' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Obtener la hoja activa
                $sheet = $event->sheet->getDelegate();

                // Obtener el número de la última fila con datos
                $lastRow = $sheet->getHighestRow();

                // Agregar el footer con los totales
                $footerRow = $lastRow + 1;
                $sheet->setCellValue('A' . $footerRow, 'Totales:');
                $sheet->setCellValue('L' . $footerRow, $this->totalGravada); // Gravada Local
                $sheet->setCellValue('M' . $footerRow, $this->totalDebitoFiscal); // Débito Fiscal
                $sheet->setCellValue('P' . $footerRow, $this->totalVenta); // Total Venta

                // Formatear las celdas del footer
                $sheet->getStyle('A' . $footerRow . ':R' . $footerRow)->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F2F2F2'],
                    ],
                ]);
            },
        ];
    }
}