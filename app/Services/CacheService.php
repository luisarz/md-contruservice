<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Tribute;
use Illuminate\Support\Facades\Cache;

class CacheService
{
    /**
     * Obtener la tasa de impuesto por defecto
     * Cache por 1 hora
     */
    public static function getTaxRate(): float
    {
        return Cache::remember('tax_rate', 3600, function () {
            $tribute = Tribute::find(1);
            return $tribute ? ($tribute->rate / 100) : 0.13;
        });
    }

    /**
     * Obtener todo el objeto Tribute por defecto
     * Cache por 1 hora
     */
    public static function getDefaultTribute(): ?Tribute
    {
        return Cache::remember('default_tribute', 3600, function () {
            return Tribute::find(1);
        });
    }

    /**
     * Obtener tasa de un tributo específico por nombre
     * Cache por 1 hora
     * @param string $name Nombre del tributo (ej: 'IVA', 'PERCEPCION')
     * @return float Tasa del tributo (ya dividida por 100)
     */
    public static function getTribute(string $name): float
    {
        return Cache::remember("tribute_{$name}", 3600, function () use ($name) {
            $tribute = Tribute::where('name', $name)
                ->where('is_active', true)
                ->first();

            if (!$tribute) {
                // Valores por defecto si no se encuentra
                return match($name) {
                    'IVA' => 0.13,
                    'PERCEPCION' => 0.01,
                    default => 0
                };
            }

            return $tribute->rate / 100;
        });
    }

    /**
     * Obtener configuración de la compañía
     * Cache por 1 hora
     */
    public static function getCompanyConfig(): ?Company
    {
        return Cache::remember('company_config', 3600, function () {
            return Company::first();
        });
    }

    /**
     * Limpiar todos los caches relacionados con configuración
     */
    public static function clearConfigCache(): void
    {
        Cache::forget('tax_rate');
        Cache::forget('default_tribute');
        Cache::forget('tribute_IVA');
        Cache::forget('tribute_PERCEPCION');
        Cache::forget('company_config');
    }

    /**
     * Obtener el branch_id del usuario autenticado
     * Cache por 1 hora (se limpia al hacer logout)
     */
    public static function getUserBranch(): ?int
    {
        $userId = auth()->id();
        if (!$userId) {
            return null;
        }

        return Cache::remember("user_branch_{$userId}", 3600, function () {
            return auth()->user()?->employee?->branch_id;
        });
    }

    /**
     * Verificar si hay contingencia activa para un warehouse
     * Cache por 5 minutos (más corto porque puede cambiar)
     *
     * @param int|null $warehouseId ID del warehouse, si es null usa el del usuario
     * @return array ['is_active' => bool, 'label' => string, 'color' => string]
     */
    public static function getContingencyStatus(?int $warehouseId = null): array
    {
        $warehouseId = $warehouseId ?? self::getUserBranch();

        if (!$warehouseId) {
            return [
                'is_active' => false,
                'label' => 'Previo Normal',
                'color' => '#52b01e'
            ];
        }

        return Cache::remember("contingency_status_{$warehouseId}", 300, function () use ($warehouseId) {
            $contingency = \App\Models\Contingency::where('warehouse_id', $warehouseId)
                ->where('is_close', 0)
                ->first();

            if ($contingency) {
                return [
                    'is_active' => true,
                    'label' => 'Deferido Contingencia',
                    'color' => 'red'
                ];
            }

            return [
                'is_active' => false,
                'label' => 'Previo Normal',
                'color' => '#52b01e'
            ];
        });
    }

    /**
     * Limpiar cache de contingencia para un warehouse específico
     */
    public static function clearContingencyCache(?int $warehouseId = null): void
    {
        if ($warehouseId) {
            Cache::forget("contingency_status_{$warehouseId}");
        }
    }

    /**
     * Limpiar cache del usuario (al hacer logout)
     */
    public static function clearUserCache(?int $userId = null): void
    {
        $userId = $userId ?? auth()->id();
        if ($userId) {
            Cache::forget("user_branch_{$userId}");
        }
    }

    /**
     * Limpiar cache específico
     */
    public static function clearCache(string $key): void
    {
        Cache::forget($key);
    }
}
