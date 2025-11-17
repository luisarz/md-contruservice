<?php

namespace App\Filament\Resources\Categories\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Categories\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;
protected static    string | \BackedEnum | null $navigationIcon='as'; // Corregido el acento en "Categorías"

    /**
     * Optimización: Eager loading de categoría padre
     * Evita N+1 queries al mostrar category.name en la tabla
     */
    protected function getTableQuery(): ?Builder
    {
        return parent::getTableQuery()->with('category');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
