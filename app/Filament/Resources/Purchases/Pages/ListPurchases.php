<?php

namespace App\Filament\Resources\Purchases\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Actions\CreateAction;
use App\Filament\Resources\Purchases\PurchaseResource;
use App\Models\Purchase;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Enums\IconSize;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListPurchases extends ListRecords
{
    protected static string $resource = PurchaseResource::class;

    public function getTabs(): array
    {
        return [
            'todos' => Tab::make('Todas')
                ->icon('heroicon-o-clipboard-document-list')
                ->badge(Purchase::where('process_document_type', 'Compra')->count()),

            'procesando' => Tab::make('En Proceso')
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Procesando'))
                ->badge(Purchase::where('process_document_type', 'Compra')->where('status', 'Procesando')->count())
                ->badgeColor('warning'),

            'finalizadas' => Tab::make('Finalizadas')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Finalizado'))
                ->badge(Purchase::where('process_document_type', 'Compra')->where('status', 'Finalizado')->count())
                ->badgeColor('success'),

            'credito' => Tab::make('A Crédito')
                ->icon('heroicon-o-banknotes')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('pruchase_condition', 'Crédito')->where('status', '!=', 'Anulado'))
                ->badge(Purchase::where('process_document_type', 'Compra')->where('pruchase_condition', 'Crédito')->where('status', '!=', 'Anulado')->count())
                ->badgeColor('info'),

            'anuladas' => Tab::make('Anuladas')
                ->icon('heroicon-o-x-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Anulado'))
                ->badge(Purchase::where('process_document_type', 'Compra')->where('status', 'Anulado')->count())
                ->badgeColor('danger'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('LibrosIVA')
                ->label('Libro Compras')
                ->tooltip('Generar DTE')
                ->icon('heroicon-o-rocket-launch')
                ->iconSize(IconSize::Large)
                ->requiresConfirmation()
                ->modalHeading('Generar Informe de IVA')
                ->modalDescription('Complete la información para generar el informe de IVA')
                ->modalSubmitActionLabel('Sí, Generar informe')
                ->color('danger')
                ->schema([
                    DatePicker::make('desde')
                        ->inlineLabel(true)
                        ->default(now()->startOfMonth())
                        ->required(),
                    DatePicker::make('hasta')
                        ->inlineLabel(true)
                        ->default(now()->endOfMonth())
                        ->required(),
                    Select::make('process_document_type')
                        ->default('Compra')
                        ->options([
                            'Compra' => 'Compras',
                            'NC' => 'NC',
                        ])
                        ->label('Documentos')

                ])->action(function ($record, array $data) {
                    $startDate = $data['desde']; // Asegurar formato correcto
                    $endDate = $data['hasta'];   // Asegurar formato correcto
                    $documentType = $data['process_document_type'];

                    // Construir la ruta dinámicamente
                    $ruta = '/purchase/iva'; // Base del nombre de la ruta
                    $ruta .= '/' . $documentType;
//                    if ($fileType === 'Libro') {
//                        $ruta .= 'libro/';
//                    } else {
//                        $ruta .= 'csv/';
//                    }
//
//                    if ($documentType === 'fact') {
//                        $ruta .= 'fact';
//                    } else {
//                        $ruta .= 'ccf';
//                    }
                    $ruta .= '/' . $startDate . '/' . $endDate;

                    return Notification::make()
                        ->title('Reporte preparado.')
                        ->body('Haz clic aquí para ver los resultados.')
                        ->actions([
                            Action::make('Ver informe')
                                ->button()
                                ->url($ruta, true) // true = abrir en nueva pestaña
                        ])
                        ->send();

                })
                ->openUrlInNewTab(),
            CreateAction::make(),
        ];
    }
}
