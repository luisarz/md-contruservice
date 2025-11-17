<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Http\JsonResponse;
use Exception;
use App\Exports\PurchaseExporter;
use App\Exports\SalesExportCCF;
use App\Exports\SalesExportFac;
use App\Models\Sale;
use App\Services\DteFileService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use ZipArchive;
use Illuminate\Support\Facades\Storage;


class ReportsController extends Controller
{
    public function saleReportFact($startDate, $endDate): BinaryFileResponse
    {
        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);




        return Excel::download(
            new SalesExportFac( $startDate, $endDate),
            "ventas-{$startDate->format('Y-m-d')}-{$endDate->format('Y-m-d')}.xlsx"
        );
    }

    public function purchaseReport($documentType,$startDate, $endDate): BinaryFileResponse
    {
        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);



        return Excel::download(
            new PurchaseExporter($documentType,$startDate, $endDate),
            "Compras-{$startDate->format('Y-m-d')}-{$endDate->format('Y-m-d')}.xlsx"
        );
    }

    public function downloadJson($startDate, $endDate): BinaryFileResponse|JsonResponse
    {
        set_time_limit(0);

        // OPTIMIZADO: Usar lazy() en vez de get() para evitar memory exhaustion
        // lazy() carga registros de forma perezosa (lazy loading) sin traer todo a memoria
        $salesQuery = Sale::select('id')
            ->where('is_dte', '1')
            ->whereIn('document_type_id', [1, 3, 5, 11, 14])//1- Fac 3-CCF 5-NC 11-FExportacion 14-Sujeto excluido
            ->whereBetween('operation_date', [$startDate, $endDate])
            ->orderBy('operation_date', 'asc')
            ->with(['dteProcesado' => function ($query) {
                $query->select('sales_invoice_id', 'num_control', 'selloRecibido', 'codigoGeneracion', 'dte')
                    ->whereNotNull('selloRecibido');
            }]);

        // Obtener solo el conteo para validación inicial (query optimizado)
        $totalSales = $salesQuery->count();

        // Validar que haya documentos antes de procesar
        if ($totalSales === 0) {
            return response()->json(['error' => 'No se encontraron documentos DTE en el rango de fechas seleccionado.'], 404);
        }

        // Ahora usar lazy() para iterar sin cargar todo a memoria
        $sales = $salesQuery->lazy(1000); // Procesa en chunks de 1000

        try {
            $dteFileService = new DteFileService();
            $failed = array();
            $tempFiles = array(); // Para limpiar después

            $zipFileName = 'dte_' . $startDate . '-' . $endDate . '.zip';
            $zipPath = storage_path("app/public/{$zipFileName}");
            $zip = new ZipArchive;

            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                foreach ($sales as $sale) {
                    if (!$sale->dteProcesado) {
                        continue;
                    }

                    $codgeneration = $sale->dteProcesado->codigoGeneracion;

                    // Generar archivo temporal desde BD
                    $tempJsonPath = $dteFileService->generateTempJsonFile($codgeneration);

                    if ($tempJsonPath && file_exists($tempJsonPath)) {
                        $zip->addFile($tempJsonPath, "{$codgeneration}.json");
                        $tempFiles[] = $tempJsonPath; // Guardar para limpiar después
                    } else {
                        $failed[] = $codgeneration;
                    }
                }

                if (count($failed) > 0) {
                    $failedList = implode("\n", $failed);
                    $zip->addFromString('README.txt', "No se encontraron datos para los siguientes DTEs:\n{$failedList}");
                }

                $zip->close();

                // Limpiar archivos temporales
                foreach ($tempFiles as $tempFile) {
                    $dteFileService->cleanTempFile($tempFile);
                }
            } else {
                return response()->json(['error' => 'No se pudo crear el archivo ZIP.'], 500);
            }

            return response()->download($zipPath)->deleteFileAfterSend(true);

        } catch (Exception $e) {
            // Limpiar archivos temporales en caso de error
            if (isset($tempFiles)) {
                foreach ($tempFiles as $tempFile) {
                    $dteFileService->cleanTempFile($tempFile);
                }
            }
            return response()->json(['error' => 'Error al descargar el archivo ZIP: ' . $e->getMessage()], 500);
        }
    }

    public function downloadPdf($startDate, $endDate): BinaryFileResponse|JsonResponse
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M'); // Aumentar límite de memoria
        ini_set('max_execution_time', '600'); // 10 minutos

        // Generar ID único para esta descarga basado en usuario y fechas
        // Esto permite que el frontend sepa qué ID consultar
        $downloadId = 'pdf_download_' . auth()->id() . '_' . $startDate . '_' . $endDate;

        // DEBUG: Loggear el downloadId para verificar
        \Log::info('Backend - DownloadId creado:', [
            'downloadId' => $downloadId,
            'userId' => auth()->id(),
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);

        // Inicializar progreso en caché
        \Cache::put($downloadId, [
            'status' => 'processing',
            'progress' => 0,
            'total' => 0,
            'current' => 0,
            'message' => 'Consultando documentos...'
        ], 600); // 10 minutos

        // Verificar que se guardó correctamente
        $cachedData = \Cache::get($downloadId);
        \Log::info('Backend - Cache guardado:', ['cached' => $cachedData ? 'SI' : 'NO', 'data' => $cachedData]);

        // OPTIMIZADO: Construir query sin ejecutar aún
        $salesQuery = Sale::select('id')
            ->where('is_dte', '1')
            ->whereIn('document_type_id', [1, 3, 5, 11, 14])//1- Fac 3-CCF 5-NC 11-FExportacion 14-Sujeto excluido
            ->whereBetween('operation_date', [$startDate, $endDate])
            ->orderBy('operation_date', 'asc')
            ->with(['dteProcesado' => function ($query) {
                $query->select('sales_invoice_id', 'num_control', 'selloRecibido', 'codigoGeneracion', 'dte')
                    ->whereNotNull('selloRecibido');
            }]);

        // Obtener solo el conteo (query optimizado)
        $totalSales = $salesQuery->count();

        // Validar que haya documentos
        if ($totalSales === 0) {
            \Cache::forget($downloadId);
            return response()->json(['error' => 'No se encontraron documentos DTE en el rango de fechas seleccionado.'], 404);
        }

        // Actualizar total en caché
        \Cache::put($downloadId, [
            'status' => 'processing',
            'progress' => 5,
            'total' => $totalSales,
            'current' => 0,
            'message' => "Procesando {$totalSales} documentos..."
        ], 600);

        try {
            // OPTIMIZADO: Usar lazy() para evitar cargar todos los registros a memoria
            $sales = $salesQuery->lazy(500); // Procesa en chunks de 500

            $failed = array();
            $failedCount = 0;
            //Limpiamops los incorrectos
            foreach ($sales as $sale) {
                $codgeneration = $sale->dteProcesado->codigoGeneracion;
                $filePath = storage_path("app/public/DTEs/{$codgeneration}.json");
                if (file_exists($filePath) && filesize($filePath) < 2048) {//Eliminar si pesa menos de 2kb
                    unlink($filePath);
                    $failedCount++;
                    $failed [] = $codgeneration;
                }
            }

            $zipFileName = 'pdf_' . $startDate . '-' . $endDate . '.zip';
            $zipPath = storage_path("app/public/{$zipFileName}");

            // Asegurar que el directorio exista
            $directory = dirname($zipPath);
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            $zip = new ZipArchive;
            $tempFiles = []; // Array para rastrear archivos temporales
            $qrCache = []; // Array para rastrear QR codes pre-generados

            // FASE 1: Pre-generar TODOS los QR codes primero (separar de generación de PDF)
            \Log::info("FASE 1: Iniciando pre-generación de {$totalSales} QR codes");
            \Cache::put($downloadId, [
                'status' => 'processing',
                'progress' => 5,
                'total' => $totalSales,
                'current' => 0,
                'message' => "Pre-generando códigos QR..."
            ], 600);

            $qrTempDir = storage_path('app/temp/qr_cache');
            if (!file_exists($qrTempDir)) {
                mkdir($qrTempDir, 0755, true);
            }

            foreach ($sales as $index => $sale) {
                if (!$sale->dteProcesado) continue;

                $codgeneration = $sale->dteProcesado->codigoGeneracion;
                $DTE = $sale->dteProcesado->dte;

                $contenidoQR = "https://admin.factura.gob.sv/consultaPublica?ambiente="
                    . env('DTE_AMBIENTE_QR')
                    . "&codGen=" . ($DTE['identificacion']['codigoGeneracion'] ?? '')
                    . "&fechaEmi=" . ($DTE['identificacion']['fecEmi'] ?? '');

                $qrPath = $qrTempDir . '/qr_' . $codgeneration . '.jpg';

                try {
                    \SimpleSoftwareIO\QrCode\Facades\QrCode::size(300)->generate($contenidoQR, $qrPath);
                    if (file_exists($qrPath)) {
                        $qrCache[$codgeneration] = $qrPath;
                    }
                } catch (\Exception $e) {
                    \Log::warning("Error generando QR para {$codgeneration}: " . $e->getMessage());
                }

                // Actualizar progreso cada 50 QR
                if (($index + 1) % 50 === 0) {
                    gc_collect_cycles();
                }
            }

            \Log::info("FASE 1 completada: " . count($qrCache) . " QR codes generados");

            // FASE 2: Generar PDFs usando QR codes pre-generados
            \Log::info("FASE 2: Iniciando generación de PDFs");

            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                $added = false;
                $dteFileService = null;
                $current = 0;
                $batchSize = 25; // Procesar en lotes de 25 documentos

                foreach ($sales as $sale) {
                    try {
                        // Verificar si se solicitó cancelación
                        if (\Cache::get($downloadId . '_cancel')) {
                            \Log::info("Proceso cancelado por el usuario en documento {$current}");

                            // Limpiar archivos temporales de PDFs
                            foreach ($tempFiles as $tempFile) {
                                if (file_exists($tempFile)) {
                                    @unlink($tempFile);
                                }
                            }

                            // Limpiar archivos QR temporales
                            foreach ($qrCache as $qrPath) {
                                if (file_exists($qrPath)) {
                                    @unlink($qrPath);
                                }
                            }

                            // Cerrar y eliminar ZIP incompleto
                            $zip->close();
                            if (file_exists($zipPath)) {
                                @unlink($zipPath);
                            }

                            // Limpiar flag de cancelación
                            \Cache::forget($downloadId . '_cancel');

                            return response()->json([
                                'message' => 'Proceso cancelado por el usuario',
                                'status' => 'cancelled'
                            ], 200);
                        }

                        $codgeneration = $sale->dteProcesado->codigoGeneracion;
                        $current++;

                        // Reiniciar servicio cada lote con limpieza agresiva
                        if ($current % $batchSize === 1 || $dteFileService === null) {
                            if ($dteFileService !== null) {
                                // Limpieza agresiva de memoria
                                unset($dteFileService);
                                gc_collect_cycles();

                                // Forzar liberación de memoria caché de GC
                                if (function_exists('gc_mem_caches')) {
                                    gc_mem_caches();
                                }

                                // Pequeña pausa para permitir limpieza completa
                                usleep(100000); // 0.1 segundos

                                \Log::info("Lote completado. Memoria liberada en documento {$current}");
                            }
                            $dteFileService = new \App\Services\DteFileService();
                        }

                        // Actualizar progreso
                        if ($current % 5 === 0 || $current === $totalSales) {
                            $progress = round(($current / $totalSales) * 85) + 10; // 10-95%
                            \Cache::put($downloadId, [
                                'status' => 'processing',
                                'progress' => $progress,
                                'total' => $totalSales,
                                'current' => $current,
                                'message' => "Generando PDF {$current} de {$totalSales}..."
                            ], 600);
                        }

                        // Generar PDF con mPDF usando el QR pre-generado desde caché
                        $qrPathFromCache = $qrCache[$codgeneration] ?? null;
                        $tempPdfPath = $dteFileService->generateTempPdfFileWithMpdf(
                            $codgeneration,
                            false,  // no es ticket
                            $qrPathFromCache  // usar QR pre-generado
                        );

                        if ($tempPdfPath && file_exists($tempPdfPath)) {
                            $zip->addFile($tempPdfPath, "{$codgeneration}.pdf");
                            $tempFiles[] = $tempPdfPath;
                            $added = true;
                        } else {
                            $failed[] = $codgeneration;
                            $failedCount++;
                        }

                    } catch (\Exception $e) {
                        $failed[] = $codgeneration . ' (Error: ' . $e->getMessage() . ')';
                        $failedCount++;
                        \Log::error("Error al generar PDF {$codgeneration}: " . $e->getMessage());
                        continue;
                    }
                }

                if ($failedCount > 0) {
                    $failedList = implode("\n", $failed);
                    $zip->addFromString('README.txt', "No se encontraron archivos JSON para los siguientes archivos:\n{$failedList}");
                }

                $zip->close();

                // Limpiar archivos temporales de PDFs después de cerrar el ZIP
                foreach ($tempFiles as $tempFile) {
                    if (file_exists($tempFile)) {
                        @unlink($tempFile);
                    }
                }

                // Limpiar archivos QR temporales del caché
                \Log::info("Limpiando " . count($qrCache) . " archivos QR temporales");
                foreach ($qrCache as $qrPath) {
                    if (file_exists($qrPath)) {
                        @unlink($qrPath);
                    }
                }

                // Eliminar el directorio de caché QR si está vacío
                $qrTempDir = storage_path('app/temp/qr_cache');
                if (file_exists($qrTempDir) && count(glob($qrTempDir . '/*')) === 0) {
                    @rmdir($qrTempDir);
                }

                // Verificar que el archivo se creó correctamente y tiene contenido
                if (!file_exists($zipPath)) {
                    return response()->json(['error' => 'El archivo ZIP no se generó correctamente.'], 500);
                }

                if (!$added && $failedCount == 0) {
                    // Si no se agregó ningún archivo al ZIP
                    @unlink($zipPath);
                    \Cache::put($downloadId, [
                        'status' => 'error',
                        'progress' => 0,
                        'message' => 'No se encontraron archivos PDF para generar el ZIP.'
                    ], 600);
                    return response()->json(['error' => 'No se encontraron archivos PDF para generar el ZIP.'], 404);
                }

                // Marcar como completado
                \Cache::put($downloadId, [
                    'status' => 'completed',
                    'progress' => 100,
                    'total' => $totalSales,
                    'current' => $totalSales,
                    'message' => '¡Descarga completada!',
                    'download_url' => url("/descargar-zip-pdf/{$zipFileName}")
                ], 600);

                return response()->download($zipPath)->deleteFileAfterSend(true);
            } else {
                \Cache::put($downloadId, [
                    'status' => 'error',
                    'progress' => 0,
                    'message' => 'No se pudo crear el archivo ZIP.'
                ], 600);
                return response()->json(['error' => 'No se pudo crear el archivo ZIP.'], 500);
            }

        } catch (Exception $e) {
            // Limpiar archivos temporales en caso de error
            if (isset($tempFiles)) {
                foreach ($tempFiles as $tempFile) {
                    if (file_exists($tempFile)) {
                        @unlink($tempFile);
                    }
                }
            }

            // Limpiar archivos QR temporales en caso de error
            if (isset($qrCache)) {
                foreach ($qrCache as $qrPath) {
                    if (file_exists($qrPath)) {
                        @unlink($qrPath);
                    }
                }
                $qrTempDir = storage_path('app/temp/qr_cache');
                if (file_exists($qrTempDir) && count(glob($qrTempDir . '/*')) === 0) {
                    @rmdir($qrTempDir);
                }
            }

            // Actualizar caché con error
            \Cache::put($downloadId, [
                'status' => 'error',
                'progress' => 0,
                'message' => 'Error: ' . $e->getMessage()
            ], 600);

            return response()->json(['error' => 'Error al descargar el archivo ZIP: ' . $e->getMessage()], 500);
        }
    }

    function generatePdf($codGeneracion): bool
    {

        $fileName = "/DTEs/{$codGeneracion}.json";

        if (Storage::disk('public')->exists($fileName)) {
            $fileContent = Storage::disk('public')->get($fileName);
            $DTE = json_decode($fileContent, true); // Decodificar JSON en un array asociativo
            $tipoDocumento = $DTE['identificacion']['tipoDte'] ?? 'DESCONOCIDO';
            // Obtener logo en base64
            $logoBase64 = \App\Services\DteFileService::getCompanyLogoBase64();
            $tiposDTE = [
                '03' => 'COMPROBANTE DE CREDITO  FISCAL',
                '01' => 'FACTURA',
                '02' => 'NOTA DE DEBITO',
                '04' => 'NOTA DE CREDITO',
                '05' => 'LIQUIDACION DE FACTURA',
                '06' => 'LIQUIDACION DE FACTURA SIMPLIFICADA',
                '08' => 'COMPROBANTE LIQUIDACION',
                '09' => 'DOCUMENTO CONTABLE DE LIQUIDACION',
                '11' => 'FACTURA DE EXPORTACION',
                '14' => 'SUJETO EXCLUIDO',
                '15' => 'COMPROBANTE DE DONACION'
            ];
            $tipoDocumento = $this->searchInArray($tipoDocumento, $tiposDTE);
            $contenidoQR = "https://admin.factura.gob.sv/consultaPublica?ambiente=" . env('DTE_AMBIENTE_QR') . "&codGen=" . $DTE['identificacion']['codigoGeneracion'] . "&fechaEmi=" . $DTE['identificacion']['fecEmi'];

            $datos = [
                'empresa' => $DTE["emisor"], // O la funci車n correspondiente para cargar datos globales de la empresa.
                'DTE' => $DTE,
                'tipoDocumento' => $tipoDocumento,
                'logo' => $logoBase64,
            ];


            $directory = storage_path('app/public/QR');

            if (!file_exists($directory)) {
                mkdir($directory, 0755, true); // Create the directory with proper permissions
            }
            $path = $directory . '/' . $DTE['identificacion']['codigoGeneracion'] . '.jpg';


            QrCode::size(300)->generate($contenidoQR, $path);

            if (file_exists($path)) {
                $qr = Storage::url("QR/{$DTE['identificacion']['codigoGeneracion']}.jpg");
            } else {
                throw new Exception("Error: El archivo QR no fue guardado correctamente en {$path}");
            }
            $isLocalhost = in_array(request()->getHost(), ['127.0.0.1', 'localhost']);

            $pdf = Pdf::loadView('DTE.dte-print-pdf', compact('datos', 'qr'))
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => !$isLocalhost,
                ]);
            $pathPage = storage_path("app/public/DTEs/{$codGeneracion}.pdf");

            $pdf->save($pathPage);
            return true;
        } else {
            return false;
        }


    }

    /**
     * Endpoint para consultar el progreso de generación de ZIP
     */
    public function checkProgress($downloadId): JsonResponse
    {
        // DEBUG: Loggear cada consulta de progreso
        \Log::info('Consultando progreso para downloadId:', ['downloadId' => $downloadId]);

        $progress = \Cache::get($downloadId);

        \Log::info('Resultado de cache:', ['found' => $progress ? 'SI' : 'NO', 'data' => $progress]);

        if (!$progress) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'No se encontró información de progreso'
            ], 404);
        }

        return response()->json($progress);
    }

    /**
     * Endpoint para cancelar la generación de ZIP
     */
    public function cancelPdfGeneration($downloadId): JsonResponse
    {
        \Log::info('Cancelación solicitada para downloadId:', ['downloadId' => $downloadId]);

        // Establecer flag de cancelación en caché
        \Cache::put($downloadId . '_cancel', true, 600);

        // Actualizar estado a cancelado
        \Cache::put($downloadId, [
            'status' => 'cancelled',
            'progress' => 0,
            'message' => 'Proceso cancelado por el usuario'
        ], 600);

        return response()->json([
            'success' => true,
            'message' => 'Proceso cancelado exitosamente'
        ]);
    }

    function searchInArray($clave, $array)
    {
        if (array_key_exists($clave, $array)) {
            return $array[$clave];
        } else {
            return 'Clave no encontrada';
        }
    }

}
