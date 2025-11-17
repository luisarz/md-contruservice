<?php

namespace App\Services;

use App\Models\HistoryDte;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use Exception;

class DteFileService
{
    /**
     * Directorio para archivos temporales
     */
    private const TEMP_DIR = 'temp/DTEs';

    /**
     * Genera un archivo JSON temporal desde HistoryDte
     *
     * @param string $codigoGeneracion
     * @return string|null Ruta del archivo temporal o null si falla
     */
    public function generateTempJsonFile(string $codigoGeneracion): ?string
    {
        $historyDte = HistoryDte::where('codigoGeneracion', $codigoGeneracion)->first();

        if (!$historyDte || !$historyDte->dte) {
            return null;
        }

        $tempPath = storage_path('app/' . self::TEMP_DIR . '/' . $codigoGeneracion . '.json');

        // Crear directorio si no existe
        $directory = dirname($tempPath);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        // Guardar JSON temporal
        $jsonContent = json_encode($historyDte->dte, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($tempPath, $jsonContent);

        return $tempPath;
    }

    /**
     * Genera un archivo PDF temporal desde HistoryDte
     *
     * @param string $codigoGeneracion
     * @param bool $isTicket Si es true, genera ticket, si no, PDF completo
     * @param bool $includeQr Si es true, incluye QR code (default), si no, usa imagen vacía
     * @param string|null $qrPath Ruta del QR pre-generado (opcional)
     * @return string|null Ruta del archivo temporal o null si falla
     */
    public function generateTempPdfFile(string $codigoGeneracion, bool $isTicket = false, bool $includeQr = true, ?string $qrPath = null): ?string
    {
        try {
            $historyDte = HistoryDte::where('codigoGeneracion', $codigoGeneracion)->first();

            if (!$historyDte || !$historyDte->dte) {
                \Log::warning("DTE no encontrado para código: {$codigoGeneracion}");
                return null;
            }

            $DTE = $historyDte->dte;
            $tempPath = storage_path('app/' . self::TEMP_DIR . '/' . $codigoGeneracion . '.pdf');

            // Crear directorio si no existe
            $directory = dirname($tempPath);
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            // Preparar datos para el PDF
            $tipoDocumento = $DTE['identificacion']['tipoDte'] ?? 'DESCONOCIDO';

            $tiposDTE = [
                '03' => 'COMPROBANTE DE CREDITO  FISCAL',
                '01' => 'FACTURA',
                '04' => 'NOTA DE REMISION',
                '05' => 'NOTA DE CREDITO',
                '06' => 'NOTA DE DEBITO',
                '07' => 'COMPROBANTE DE RETENCION',
                '08' => 'COMPROBANTE DE LIQUIDACION',
                '11' => 'FACTURA DE EXPORTACION',
                '14' => 'SUJETO EXCLUIDO',
                '15' => 'COMPROBANTE DE DONACION'
            ];

            $tipoDocumento = $tiposDTE[$tipoDocumento] ?? $tipoDocumento;

            // Obtener logo en base64
            $logoBase64 = self::getCompanyLogoBase64();

            $datos = [
                'empresa' => $DTE["emisor"] ?? [],
                'DTE' => $DTE,
                'tipoDocumento' => $tipoDocumento,
                'logo' => $logoBase64,
            ];

            // Generar QR en base64 (solo si se solicita)
            if ($includeQr) {
                // Si se proporcionó un QR pre-generado, usarlo
                if ($qrPath && file_exists($qrPath)) {
                    $qrData = file_get_contents($qrPath);
                    $mimeType = mime_content_type($qrPath);
                    $qr = 'data:' . $mimeType . ';base64,' . base64_encode($qrData);
                    \Log::info("Usando QR pre-generado: {$qrPath}");
                } else {
                    // Generar QR on-demand (método original)
                    $qr = $this->generateQrBase64($DTE);
                }
            } else {
                // Usar imagen transparente para evitar error de división por cero en DomPDF
                $transparentPng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
                $qr = 'data:image/png;base64,' . base64_encode($transparentPng);
            }

            // Generar PDF
            $isLocalhost = in_array(request()->getHost(), ['127.0.0.1', 'localhost']);
            $view = $isTicket ? 'DTE.dte-print-ticket' : 'DTE.dte-print-pdf';

            $pdf = Pdf::loadView($view, compact('datos', 'qr'))
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => !$isLocalhost,
                    'debugKeepTemp' => false,
                    'debugCss' => false,
                    'debugLayout' => false,
                ]);

            if ($isTicket) {
                $pdf->set_paper(array(0, 0, 250, 1000));
            }

            $pdf->save($tempPath);

            // Liberar memoria
            unset($pdf, $DTE, $datos, $qr, $logoBase64);

            return $tempPath;

        } catch (\DivisionByZeroError $e) {
            \Log::error("Error división por cero al generar PDF para {$codigoGeneracion}: " . $e->getMessage());
            return null;
        } catch (\Exception $e) {
            \Log::error("Error al generar PDF para {$codigoGeneracion}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Genera código QR en formato base64 (PNG - más compatible con DomPDF)
     *
     * @param array $DTE
     * @return string QR en formato data URI
     */
    public function generateQrBase64(array $DTE): string
    {
        try {
            $contenidoQR = "https://admin.factura.gob.sv/consultaPublica?ambiente="
                           . env('DTE_AMBIENTE_QR')
                           . "&codGen=" . ($DTE['identificacion']['codigoGeneracion'] ?? '')
                           . "&fechaEmi=" . ($DTE['identificacion']['fecEmi'] ?? '');

            // Validar que tenemos los datos necesarios
            if (empty($DTE['identificacion']['codigoGeneracion'])) {
                throw new \Exception('Código de generación vacío');
            }

            // Usar el mismo método que ReportsController - generar archivo temporal JPG
            $tempQrPath = storage_path('app/' . self::TEMP_DIR . '/qr_' . $DTE['identificacion']['codigoGeneracion'] . '.jpg');

            // Crear directorio si no existe
            $qrDirectory = dirname($tempQrPath);
            if (!file_exists($qrDirectory)) {
                mkdir($qrDirectory, 0755, true);
            }

            // Generar QR y guardar como archivo (igual que ReportsController línea 368)
            QrCode::size(300)->generate($contenidoQR, $tempQrPath);

            // Validar que el archivo se creó correctamente
            if (!file_exists($tempQrPath) || filesize($tempQrPath) < 100) {
                throw new \Exception('QR no se generó correctamente');
            }

            // Leer archivo y convertir a base64
            $qrData = file_get_contents($tempQrPath);
            $mimeType = mime_content_type($tempQrPath);

            // Limpiar archivo temporal
            @unlink($tempQrPath);

            return 'data:' . $mimeType . ';base64,' . base64_encode($qrData);

        } catch (\Exception $e) {
            \Log::warning("Error al generar QR: " . $e->getMessage());

            // Si falla el QR, devolver una imagen transparente de 1x1
            $transparentPng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
            return 'data:image/png;base64,' . base64_encode($transparentPng);
        }
    }

    /**
     * Obtiene el logo en formato base64 desde el Branch del usuario autenticado
     *
     * @return string|null Logo en formato data URI o null si no hay logo
     */
    public static function getCompanyLogoBase64(): ?string
    {
        $logo = auth()->user()->employee->wherehouse->logo ?? null;

        // Extraer la ruta del logo (puede ser array o string)
        $logoRelativePath = is_array($logo) ? ($logo[0] ?? null) : $logo;

        // Convertir logo a base64 para DomPDF (más confiable que rutas físicas)
        if ($logoRelativePath && Storage::disk('public')->exists($logoRelativePath)) {
            $logoFullPath = Storage::disk('public')->path($logoRelativePath);
            $imageData = file_get_contents($logoFullPath);
            $mimeType = mime_content_type($logoFullPath);
            return 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
        } elseif (file_exists(public_path('storage/wherehouses/default-logo.png'))) {
            $defaultLogoPath = public_path('storage/wherehouses/default-logo.png');
            $imageData = file_get_contents($defaultLogoPath);
            return 'data:image/png;base64,' . base64_encode($imageData);
        }

        return null;
    }

    /**
     * Limpia un archivo temporal específico
     *
     * @param string $filePath
     * @return bool
     */
    public function cleanTempFile(string $filePath): bool
    {
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return false;
    }

    /**
     * Limpia todos los archivos temporales más antiguos que X horas
     *
     * @param int $hoursOld Antigüedad en horas (default: 24)
     * @return int Cantidad de archivos eliminados
     */
    public function cleanOldTempFiles(int $hoursOld = 24): int
    {
        $tempDir = storage_path('app/' . self::TEMP_DIR);

        if (!file_exists($tempDir)) {
            return 0;
        }

        $count = 0;
        $cutoffTime = time() - ($hoursOld * 3600);

        $files = glob($tempDir . '/*');

        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoffTime) {
                if (unlink($file)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Genera ambos archivos temporales (JSON y PDF) para una venta
     *
     * @param string $codigoGeneracion
     * @return array ['json' => path, 'pdf' => path] o null si falla
     */
    public function generateTempFilesForEmail(string $codigoGeneracion): ?array
    {
        $jsonPath = $this->generateTempJsonFile($codigoGeneracion);
        $pdfPath = $this->generateTempPdfFile($codigoGeneracion, false);

        if (!$jsonPath || !$pdfPath) {
            // Limpiar si alguno falló
            if ($jsonPath) $this->cleanTempFile($jsonPath);
            if ($pdfPath) $this->cleanTempFile($pdfPath);
            return null;
        }

        return [
            'json' => $jsonPath,
            'pdf' => $pdfPath
        ];
    }

    /**
     * Genera un archivo PDF temporal usando mPDF (para generación masiva)
     *
     * @param string $codigoGeneracion
     * @param bool $isTicket Si es true, genera ticket, si no, PDF completo
     * @param string|null $qrPath Ruta del QR pre-generado (opcional)
     * @return string|null Ruta del archivo temporal o null si falla
     */
    public function generateTempPdfFileWithMpdf(string $codigoGeneracion, bool $isTicket = false, ?string $qrPath = null): ?string
    {
        try {
            $historyDte = HistoryDte::where('codigoGeneracion', $codigoGeneracion)->first();

            if (!$historyDte || !$historyDte->dte) {
                \Log::warning("DTE no encontrado para código: {$codigoGeneracion}");
                return null;
            }

            $DTE = $historyDte->dte;
            $tempPath = storage_path('app/' . self::TEMP_DIR . '/' . $codigoGeneracion . '.pdf');

            // Crear directorio si no existe
            $directory = dirname($tempPath);
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            // Preparar datos para el PDF
            $tipoDocumento = $DTE['identificacion']['tipoDte'] ?? 'DESCONOCIDO';

            $tiposDTE = [
                '03' => 'COMPROBANTE DE CREDITO  FISCAL',
                '01' => 'FACTURA',
                '04' => 'NOTA DE REMISION',
                '05' => 'NOTA DE CREDITO',
                '06' => 'NOTA DE DEBITO',
                '07' => 'COMPROBANTE DE RETENCION',
                '08' => 'COMPROBANTE DE LIQUIDACION',
                '11' => 'FACTURA DE EXPORTACION',
                '14' => 'SUJETO EXCLUIDO',
                '15' => 'COMPROBANTE DE DONACION'
            ];

            $tipoDocumento = $tiposDTE[$tipoDocumento] ?? $tipoDocumento;

            // Obtener logo en base64
            $logoBase64 = self::getCompanyLogoBase64();

            $datos = [
                'empresa' => $DTE["emisor"] ?? [],
                'DTE' => $DTE,
                'tipoDocumento' => $tipoDocumento,
                'logo' => $logoBase64,
            ];

            // Preparar QR
            if ($qrPath && file_exists($qrPath)) {
                // Usar QR pre-generado
                $qrData = file_get_contents($qrPath);
                $mimeType = mime_content_type($qrPath);
                $qr = 'data:' . $mimeType . ';base64,' . base64_encode($qrData);
            } else {
                // Generar QR on-demand
                $qr = $this->generateQrBase64($DTE);
            }

            // Renderizar vista a HTML (usar vista específica para mPDF)
            $view = $isTicket ? 'DTE.dte-print-ticket' : 'DTE.dte-print-pdf-mpdf';
            $html = view($view, compact('datos', 'qr'))->render();

            // Crear directorio temporal de mPDF si no existe
            $mpdfTempDir = storage_path('app/temp/mpdf');
            if (!file_exists($mpdfTempDir)) {
                mkdir($mpdfTempDir, 0755, true);
            }

            // Crear instancia de mPDF con configuración compatible con DomPDF
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => $isTicket ? [95, 300] : 'Letter',
                'margin_left' => $isTicket ? 5 : 15,
                'margin_right' => $isTicket ? 5 : 15,
                'margin_top' => $isTicket ? 5 : 15,
                'margin_bottom' => $isTicket ? 5 : 15,
                'margin_header' => 0,
                'margin_footer' => 0,
                'tempDir' => $mpdfTempDir,
                'default_font_size' => 10,
                'default_font' => 'dejavusans',
                'autoScriptToLang' => true,
                'autoLangToFont' => true,
            ]);

            // Escribir HTML completo (mPDF procesa el HTML completo incluyendo estilos)
            $mpdf->WriteHTML($html);

            // Guardar PDF
            $mpdf->Output($tempPath, \Mpdf\Output\Destination::FILE);

            // Liberar memoria
            unset($mpdf, $DTE, $datos, $qr, $logoBase64, $html);

            return $tempPath;

        } catch (\Exception $e) {
            \Log::error("Error al generar PDF con mPDF para {$codigoGeneracion}: " . $e->getMessage());
            return null;
        }
    }
}
