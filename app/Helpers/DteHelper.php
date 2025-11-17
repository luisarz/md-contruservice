<?php

if (!function_exists('getCondicionOperacionText')) {
    /**
     * Convierte el código de condición de operación a texto legible
     *
     * @param int|string|null $condicion
     * @return string
     */
    function getCondicionOperacionText($condicion): string
    {
        $condicion = (int) ($condicion ?? 1);

        return match($condicion) {
            1 => 'Contado',
            2 => 'Crédito',
            3 => 'Otro',
            default => 'Contado'
        };
    }
}

if (!function_exists('getTipoDocumentoText')) {
    /**
     * Convierte el código de tipo de documento DTE a texto legible
     *
     * @param string|null $tipoDte
     * @return string
     */
    function getTipoDocumentoText(?string $tipoDte): string
    {
        return match($tipoDte) {
            '01' => 'FACTURA',
            '03' => 'COMPROBANTE DE CRÉDITO FISCAL',
            '04' => 'NOTA DE REMISIÓN',
            '05' => 'NOTA DE CRÉDITO',
            '06' => 'NOTA DE DÉBITO',
            '07' => 'COMPROBANTE DE RETENCIÓN',
            '08' => 'COMPROBANTE DE LIQUIDACIÓN',
            '09' => 'DOCUMENTO CONTABLE DE LIQUIDACIÓN',
            '11' => 'FACTURA DE EXPORTACIÓN',
            '14' => 'SUJETO EXCLUIDO',
            '15' => 'COMPROBANTE DE DONACIÓN',
            default => 'DOCUMENTO DESCONOCIDO'
        };
    }
}

if (!function_exists('getEstadoContigenciaText')) {
    /**
     * Retorna el texto de estado de contingencia
     *
     * @param string|null $sello
     * @return string
     */
    function getEstadoContigenciaText(?string $sello): string
    {
        return $sello ? $sello : 'CONTINGENCIA';
    }
}
