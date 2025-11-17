<?php

namespace App\Exports;

use App\Models\Purchase;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class PurchaseExporter implements FromCollection,WithHeadings
{
    protected string $documentType;
    protected Carbon $startDate;
    protected Carbon $endDate;
    protected float $totalGravada = 0;
    protected float $totalDebitoFiscal = 0;
    protected float $totalVenta = 0;

    public function __construct(string $documentType, string $startDate, string $endDate)
    {
        $this->startDate = Carbon::parse($startDate);
        $this->endDate = Carbon::parse($endDate);
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Sucursal',
            'Proveedor',
            'NRC',
            'NIT',
            'Tipo Compra',
            'Condicion',
            'DTE',
            'NETO',
            'IVA',
            'Percepcion',
            'Total',
            'Estado',

        ];


    }

    public function collection(): Collection
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        return Purchase::select(
            'id',
            'purchase_date as fecha',
            'wherehouse_id',
            'provider_id',
            'document_type',
            'document_number',
            'net_value as neto',
            'taxe_value as iva',
            'perception_value as percepcion',
            'purchase_total as total',
            'pruchase_condition',
            'status',

        )
            ->where('status', '!=', 'Anulado')
            ->whereBetween('purchase_date', [$this->startDate, $this->endDate])
            ->orderBy('purchase_date', 'asc')
            ->with(['provider', 'wherehouse'])
            ->get()
            ->map(function ($purchase) {
                $rawNit = $purchase->provider->nit ?? '';
                $cleanNit = preg_replace('/[^0-9]/', '', trim($rawNit));
                $isNitZero = ($cleanNit === '' || preg_match('/^0+$/', $cleanNit));
                $formatNit = str_repeat('0', strlen($cleanNit));
                $nit = $isNitZero ? null : '=TEXT("' . $cleanNit . '","' . $formatNit . '")';
                return [
                    'fecha' => date('d/m/Y', strtotime($purchase->fecha)),
                    'Sucursal' => $purchase->wherehouse->name ?? null,
                    'Proveedor' => $purchase->provider->legal_name ?? null,
                    'NRC' => $purchase->provider->nrc ?? null,
                    'NIT' => $nit ?? null,
                    'Tipo Compra' => $purchase->document_type ?? null,
                    'Condicion' => $purchase->pruchase_condition ?? null,
                    'DTE' => $purchase->document_number ?? null,
                    'NETO' => $purchase->neto ?? 0,
                    'IVA' => $purchase->iva ?? 0,
                    'Percepcion' => $purchase->perception ?? 0,
                    'Total' => $purchase->total ?? 0,
                    'Estado' => $purchase->status ?? null,


                ];
            });
    }

}