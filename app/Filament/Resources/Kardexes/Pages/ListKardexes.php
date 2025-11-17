<?php

namespace App\Filament\Resources\Kardexes\Pages;

use Maatwebsite\Excel\Excel;
use App\Filament\Resources\Kardexes\KardexResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListKardexes extends ListRecords
{
    protected static string $resource = KardexResource::class;

    /**
     * Optimización CRÍTICA: Eager loading de relaciones anidadas
     * Evita N+1 queries en relaciones de 2 y 3 niveles (inventory.product.unitmeasurement)
     */
    protected function getTableQuery(): ?Builder
    {
        return parent::getTableQuery()
            ->with([
                'wherehouse:id,name',
                'inventory.product:id,name',
                'inventory.product.unitmeasurement:id,description'
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
//            Actions\CreateAction::make(),
            ExportAction::make()
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename(fn ($resource) => $resource::getModelLabel() . '-' . date('Y-m-d'))
                        ->withWriterType(Excel::XLSX)
                        ->withColumns([
//                            Column::make('updated_at'),
                        ])
                ]),
            ];
    }
}
