<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Exception;
use App\Models\Sale;
use App\Services\DteFileService;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendEmailDTE as sendDTEFiles;

class SenEmailDTEController extends Controller
{
    public function SenEmailDTEController($idVenta): JsonResponse
    {
        $sale = Sale::with('customer','wherehouse','wherehouse.company')->find($idVenta);
        if (!$sale) {
            return response()->json([
                'status' => false,
                'message' => 'Venta no encontrada',
            ]);
        }

        $generationCode = $sale->generationCode;

        if (!$generationCode) {
            return response()->json([
                'status' => false,
                'message' => 'No se encontró el código de generación del DTE',
                'body' => 'Esta venta no tiene un DTE generado',
            ]);
        }

        try {
            // Generar archivos temporales usando el servicio
            $dteFileService = new DteFileService();
            $files = $dteFileService->generateTempFilesForEmail($generationCode);

            if (!$files) {
                return response()->json([
                    'status' => false,
                    'message' => 'No se pudieron generar los archivos del DTE',
                    'body' => 'Por favor, verifique que el DTE esté correctamente generado',
                ]);
            }

            // Enviar email con archivos temporales
            Mail::to($sale->customer->email)
                ->send(new sendDTEFiles($files['json'], $files['pdf'], $sale));

            // Limpiar archivos temporales después de enviar
            $dteFileService->cleanTempFile($files['json']);
            $dteFileService->cleanTempFile($files['pdf']);

            return response()->json([
                'status' => true,
                'message' => 'Email enviado exitosamente',
                'body' => 'Correo enviado a ' . $sale->customer->email,
            ]);

        } catch (Exception $e) {
            // Limpiar archivos temporales en caso de error
            if (isset($files)) {
                $dteFileService->cleanTempFile($files['json']);
                $dteFileService->cleanTempFile($files['pdf']);
            }

            return response()->json([
                'status' => false,
                'message' => 'Error al enviar el correo: ' . $e->getMessage(),
                'body' => 'Error al enviar el correo a ' . $sale->customer->email,
            ]);
        }
    }
}
