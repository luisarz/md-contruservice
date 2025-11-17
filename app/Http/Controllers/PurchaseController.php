<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Purchase;
use App\Services\CacheService;
use Barryvdh\DomPDF\Facade\Pdf;
use Luecano\NumeroALetras\NumeroALetras;

class PurchaseController extends Controller
{
    public function getConfiguracion()
    {
        return CacheService::getCompanyConfig();
    }

    public function generarPdf($idCompra)
    {
        // Cargar compra con sus relaciones
        $purchase = Purchase::with([
            'provider',
            'employee',
            'wherehouse',
            'purchaseItems.inventory.product.unitmeasurement'
        ])->findOrFail($idCompra);

        $empresa = $this->getConfiguracion();

        // Convertir monto total a letras
        $formatter = new NumeroALetras();
        $montoLetras = $formatter->toInvoice($purchase->purchase_total, 2, 'DÓLARES');

        $isLocalhost = in_array(request()->getHost(), ['127.0.0.1', 'localhost']);

        $pdf = Pdf::loadView('purchase.purchase-print-pdf', compact('purchase', 'empresa', 'montoLetras'))
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => !$isLocalhost,
            ]);

        return $pdf->stream("Compra-{$purchase->document_number}.pdf");
    }
}
