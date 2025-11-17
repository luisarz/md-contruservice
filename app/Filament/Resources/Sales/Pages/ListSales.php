<?php

namespace App\Filament\Resources\Sales\Pages;

use Filament\Notifications\Notification;
use Filament\Actions\CreateAction;
use Filament\Schemas\Components\Tabs\Tab;
use App\Filament\Resources\Sales\SaleResource;
use App\Http\Controllers\DTEController;
use App\Models\CashBoxOpen;
use App\Models\Product;
use App\Models\Sale;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\IconSize;
use Filament\Support\Facades\FilamentView;
use Filament\Tables\Actions\Action;
use Filament\Tables\View\TablesRenderHook;
use Illuminate\Database\Eloquent\Builder;

class ListSales extends ListRecords
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('LibrosIVA')
                ->label('Reporte de Ventas')
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
                    Select::make('documentType')
                        ->default('fact')
                        ->label('Documentos')
                        ->options([
                            'fact' => 'Ventas',
//                            'ccf' => 'CCF',
                        ])
                        ->required(),
                    Select::make('fileType')
                        ->required()
                        ->label('Tipo de archivo')
                        ->default('Libro')
                        ->options([
                            'Libro' => 'Libro',
//                            'Anexo' => 'Anexos',
                        ])
                ])->action(function ($record, array $data) {
                    $startDate = $data['desde']; // Asegurar formato correcto
                    $endDate = $data['hasta'];   // Asegurar formato correcto
                    $documentType = $data['documentType'];
                    $fileType = $data['fileType'];

                    // Construir la ruta dinámicamente
                    $ruta = '/sale/iva/'; // Base del nombre de la ruta

                    if ($fileType === 'Libro') {
                        $ruta .= 'libro/';
                    } else {
                        $ruta .= 'csv/';
                    }

                    if ($documentType === 'fact') {
                        $ruta .= 'fact';
                    } else {
                        $ruta .= 'ccf';
                    }
                    $ruta .= '/' . $startDate . '/' . $endDate;
//                    dd($ruta);

                    return Notification::make()
                        ->title('Reporte preparado.')
                        ->body('Haz clic aquí para ver los resultados.')
                        ->actions([
                            \Filament\Actions\Action::make('Ver informe')
                                ->button()
                                ->url($ruta, true) // true = abrir en nueva pestaña
                        ])
                        ->send();

                })
                ->openUrlInNewTab(),
            Actions\Action::make('download')
                ->label('Descargar DTE')
                ->tooltip('Descargar DTE')
                ->icon('heroicon-o-arrow-down-on-square-stack')
                ->iconSize(IconSize::Large)
                ->requiresConfirmation()
                ->modalHeading('Descargar Archivos')
                ->modalDescription('Complete la información para generar el archivo a descargar')
                ->modalSubmitActionLabel('Sí, Generar Archivo')
                ->color('warning')
                ->schema([
                    DatePicker::make('desde')
                        ->inlineLabel(true)
                        ->default(now()->startOfMonth())
                        ->required(),
                    DatePicker::make('hasta')
                        ->inlineLabel(true)
                        ->default(now()->endOfMonth())
                        ->required(),
                    Select::make('documentType')
                        ->default('json')
                        ->label('Documentos')
                        ->options([
                            'json' => 'JSON',
                            'pdf' => 'PDF',
                        ])
                        ->required(),

                ])->action(function ($record, array $data) {
                    $startDate = $data['desde'];
                    $endDate = $data['hasta'];
                    $documentType = $data['documentType'];

                    // Si es PDF, usar la nueva ventana con loader
                    if ($documentType === 'pdf') {
                        $url = '/reports/download-progress?start=' . $startDate . '&end=' . $endDate;

                        // Abrir ventana popup con el loader
                        $this->js("window.open('{$url}', 'downloadPDF', 'width=600,height=500,scrollbars=no,resizable=yes')");

                        return Notification::make()
                            ->title('Generando archivo ZIP')
                            ->body('Se ha abierto una nueva ventana. El proceso puede tardar varios minutos.')
                            ->info()
                            ->send();
                    } else {
                        // JSON descarga directamente
                        $ruta = '/sale/' . $documentType . '/' . $startDate . '/' . $endDate;

                        return Notification::make()
                            ->title('Reporte preparado.')
                            ->body('Haz clic aquí para descargar el archivo.')
                            ->actions([
                                \Filament\Actions\Action::make('Descargar Archivo')
                                    ->button()
                                    ->url($ruta, true)
                            ])
                            ->send();
                    }
                })
                ->openUrlInNewTab(),

            CreateAction::make()
                ->label('Nueva Venta')
                ->icon('heroicon-o-shopping-cart')
                ->color('success')
                ->visible(function () {
                    $whereHouse = auth()->user()->employee->branch_id ?? null;
                    if ($whereHouse) {
                        $cashBoxOpened = CashBoxOpen::with('cashbox')
                            ->where('status', 'open')
                            ->whereHas('cashbox', function ($query) use ($whereHouse) {
                                $query->where('branch_id', $whereHouse);
                            })
                            ->first();
                        if ($cashBoxOpened) {
                            return true;
                        } else {
                            return false;

                        }

                    }


                }),
        ];
    }

    public function getTabs(): array
    {

        $allCount = Sale::whereIn('sale_status', ['Facturada', 'Finalizado', 'Anulado'])
            ->where('is_invoiced', 1)->count();
        $send = Sale::where('is_dte', 1)->whereIn('sale_status', ['Facturada', 'Finalizado', 'Anulado'])->count();
        $unSend = Sale::whereIn('sale_status', ['Facturada', 'Finalizado', 'Anulado'])
            ->where('is_invoiced', 1)
            ->where('is_dte', 0)->count();


        return [
            "All" => Tab::make()
                ->badge($allCount),
            "Transmitidos" => Tab::make()
                ->badge($send)
                ->label('Enviados')
                ->badgeColor('success')
                ->icon('heroicon-o-rocket-launch')
                ->modifyQueryUsing(fn(Builder $query) => $query->withTrashed()->where('is_dte', 1)),

            "Sin Transmitir" => Tab::make()
                ->label('Sin Transmisión')
                ->badge($unSend)
                ->badgeColor('danger')
                ->icon('heroicon-s-computer-desktop')
                ->modifyQueryUsing(fn(Builder $query) => $query->withTrashed()->where('is_dte', 0)->whereIn('sale_status', ['Facturada', 'Finalizado'])),

        ];
    }
}
