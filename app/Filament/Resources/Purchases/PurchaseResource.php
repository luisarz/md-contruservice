<?php

namespace App\Filament\Resources\Purchases;

use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Auth;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\Action;
use Log;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\Purchases\RelationManagers\PurchaseItemsRelationManager;
use App\Filament\Resources\Purchases\Pages\ListPurchases;
use App\Filament\Resources\Purchases\Pages\CreatePurchase;
use App\Filament\Resources\Purchases\Pages\EditPurchase;
use App\Filament\Resources\Purchases\Pages\ViewPurchase;
use App\Filament\Resources\Purchases\Pages\AdjustPurchase;
use App\Services\Inventory\KardexService;
use App\Models\Inventory;
use App\Models\Provider;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Tribute;
use App\Services\CacheService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Average;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\BulkAction;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

function updateTotaPurchase(mixed $idItem, array $data): void
{
    $have_perception = $data['have_perception'] ?? false;
    $retentionPorcentage = 1;

    $purchase = Purchase::find($idItem);
    if ($purchase) {
        // Fetch tax rates with default values
        $tax = CacheService::getDefaultTribute();
        $ivaRate = $tax ? $tax->rate : 0;
        $isrRate = 1;//Tribute::where('id', 3)->value('rate') ?? 0;

        $ivaRate /= 100;
        $isrRate /= 100;
        // Calculate total and net amounts
        $montoTotal = PurchaseItem::where('purchase_id', $purchase->id)->sum('total') ?? 0;
        // Calculate tax and retention conditionally
        $iva =$montoTotal * 0.13 ;
        $perception = $have_perception ? $montoTotal * $isrRate : 0;

        // Round and save calculated values
        $purchase->net_value = round($montoTotal, 2);
        $purchase->taxe_value = round($iva, 2);
        $purchase->perception_value = round($perception, 2);
        $purchase->purchase_total = round($montoTotal + $perception+$iva, 2);
        $purchase->save();
    }
}

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;
    protected static ?string $label = 'Compras';
    protected static string | \UnitEnum | null $navigationGroup = 'Inventario';

//    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('')
                    ->schema([
                        Section::make('COMPRA')
//                            ->description('Informacion general de la compra')
                            ->icon('heroicon-o-book-open')
                            ->iconColor('danger')
                            ->compact()
                            ->schema([
                                Select::make('provider_id')
                                    ->relationship('provider', 'comercial_name')
                                    ->label('Proveedor')
                                    ->preload()
                                    ->searchable()
                                    ->required()
                                    ->placeholder('Buscar proveedor...'),
                                Select::make('employee_id')
                                    ->relationship('employee', 'name')
                                    ->label('Empleado')
                                    ->preload()
                                    ->default(fn () => optional(Auth::user()->employee)->id ?? '')
                                    ->searchable()
                                    ->required(),
                                Select::make('wherehouse_id')
                                    ->label('Sucursal')
                                    ->relationship('wherehouse', 'name')
                                    ->default(fn() => Auth::user()->employee->branch_id)
                                    ->preload()
                                    ->required(),
                                DatePicker::make('purchase_date')
                                    ->label('Fecha')
                                    ->inlineLabel()
                                    ->default(today())
                                    ->required()
                                    ->placeholder('Seleccione fecha'),
                                Select::make('document_type')
                                    ->label('Tipo Documento')
                                    ->options([
                                        'Electrónico' => 'Electrónico',
                                        'Físico' => 'Físico',
                                    ])
                                    ->default('Físico')
                                    ->required()
                                    ->reactive() // Makes the select field reactive to detect changes
                                    ->afterStateUpdated(function (callable $set, $state) {
                                        if ($state === 'Electrónico') {
                                            $set('document_number_label', 'DTE');
                                        } else {
                                            $set('document_number_label', 'Número CCF');
                                        }
                                    }),

                                TextInput::make('document_number')
                                    ->label(fn(callable $get) => $get('document_number_label') ?? 'Número CCF') // Default label if not set
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Ingrese número de documento'),


                                Select::make('pruchase_condition')
                                    ->label('Condición')
                                    ->options([
                                        'Contado' => 'Contado',
                                        'Crédito' => 'Crédito',
                                    ])
                                    ->default('Contado')
                                    ->required()
                                    ->live() // Hace el campo reactivo para detectar cambios
                                    ->afterStateUpdated(function (callable $set, $state) {
                                        // Cuando se selecciona "Contado", realiza los siguientes cambios:
                                        if ($state === 'Contado') {
                                            $set('credit_days', null); // Vacia el campo de días de crédito
                                            $set('paid', true); // Marca como pagado
                                            $set('status', 'Finalizado'); // Establece el estado a "Finalizado"
                                        } else {
                                            // Cuando se selecciona "Crédito", realiza los siguientes cambios:
                                            $set('paid', false); // Marca como no pagado
                                            $set('status', 'Procesando'); // Establece el estado a "Procesando"
                                        }
                                    }),

                                TextInput::make('credit_days')
                                    ->label('Días de Crédito')
                                    ->numeric()
                                    ->default(null)
                                    ->visible(fn(callable $get) => $get('pruchase_condition') != 'Contado') // Solo visible cuando se selecciona "Crédito"
                                    ->required(fn(callable $get) => $get('pruchase_condition') != 'Contado'), // Obligatorio solo si "Crédito" es seleccionado

                                Select::make('status')
                                    ->options([
                                        'Procesando' => 'Procesando',
                                        'Finalizado' => 'Finalizado',
                                        'Anulado' => 'Anulado',
                                    ])
                                    ->default('Procesando') // Establece "Procesando" como valor predeterminado
                                    ->required(),


                            ])->columnSpan(3)->columns(2),
                        Section::make('Total')
                            ->compact()
                            ->icon('heroicon-o-currency-dollar')
                            ->iconColor('success')
                            ->schema([
                                Toggle::make('have_perception')
                                    ->label('Percepción')
                                    ->live()
                                    ->required()
                                    ->afterStateUpdated(function ($set, $state, $get, Component $livewire) {
                                        $idItem = $get('id'); // ID del item de venta
                                        $data = [
                                            'have_perception' => $state,
                                        ];
                                        updateTotaPurchase($idItem, $data);
                                        $livewire->dispatch('refreshPurchase');
                                    }),
                                Placeholder::make('net_value')
                                    ->content(function (?Purchase $record) {
                                        return $record ? ($record->net_value ?? 0) : 0;
                                    })
                                    ->inlineLabel()
                                    ->label('Neto'),

                                Placeholder::make('taxe_value')
                                    ->content(function (?Purchase $record) {
                                        return $record ? ($record->taxe_value ?? 0) : 0;
                                    })
                                    ->inlineLabel()
                                    ->label('IVA'),

                                Placeholder::make('perception_value')
                                    ->content(fn(?Purchase $record) => $record->perception_value ?? 0)
                                    ->inlineLabel()
                                    ->label('Percepción:'),

                                Placeholder::make('purchase_total')
                                    ->label('Total')
                                    ->content(fn(?Purchase $record) => new HtmlString('<span style="font-weight: bold; color: red; font-size: 18px;">$ ' . number_format($record->purchase_total ?? 0, 2) . '</span>'))
                                    ->inlineLabel()
                                    ->extraAttributes(['class' => 'p-0 text-lg']) // Tailwind classes for padding and font size
                                    ->columnSpan('full'),
                            ])->
                            columnSpan(1),
                    ])->columns(4),


            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('document_number')
                    ->label('Cod. Generación')
                    ->searchable()
                    ->icon('heroicon-o-document-text')
                    ->copyable()
                    ->copyMessage('Código copiado')
                    ->copyMessageDuration(1500),

                TextColumn::make('provider.comercial_name')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->description(fn (Purchase $record): string => $record->document_type ?? 'N/A'),

                TextColumn::make('employee.name')
                    ->label('Empleado')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('purchase_date')
                    ->label('Fecha')
                    ->date(format: 'd/m/Y')
                    ->sortable()
                    ->description(fn (Purchase $record): string => Carbon::parse($record->purchase_date)->diffForHumans()),

                TextColumn::make('items_count')
                    ->counts('purchaseItems')
                    ->label('Items')
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('net_value')
                    ->label('NETO')
                    ->money('USD', true, 'en_US')
                    ->sortable()
                    ->summarize([
                        Sum::make()
                            ->query(fn (Builder $query) => $query->where('status','!=', 'Anulado'))
                            ->label('Total Neto')
                            ->money('USD', true, 'en_US'),
                    ])
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('taxe_value')
                    ->label('IVA')
                    ->money('USD', true, 'en_US')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('perception_value')
                    ->label('Percepción')
                    ->money('USD', true, 'en_US')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('purchase_total')
                    ->label('Total')
                    ->money('USD', locale: 'en_US')
                    ->sortable()
                    ->weight('bold')
                    ->color(fn (Purchase $record) => match(true) {
                        $record->purchase_total >= 1000 => 'success',
                        $record->purchase_total >= 500 => 'warning',
                        default => 'gray'
                    })
                    ->summarize([
                        Sum::make()
                            ->query(fn (Builder $query) => $query->where('status','!=', 'Anulado'))
                            ->label('Total General')
                            ->money('USD', true, 'en_US'),
                        Average::make()
                            ->query(fn (Builder $query) => $query->where('status','!=', 'Anulado'))
                            ->label('Promedio')
                            ->money('USD', true, 'en_US'),
                    ]),

                TextColumn::make('wherehouse.name')
                    ->label('Sucursal')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                TextColumn::make('document_type')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Documento'),

                TextColumn::make('purchase_condition')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Cond. Compra'),

                TextColumn::make('credit_days')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Crédito')
                    ->placeholder('Contado')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->label('Estado')
                    ->icon(fn (Purchase $record) => match($record->status) {
                        'Procesando' => 'heroicon-o-clock',
                        'Finalizado' => 'heroicon-o-check-circle',
                        'Anulado' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn($record) => match ($record->status) {
                        'Anulado' => 'danger',
                        'Procesando' => 'warning',
                        'Finalizado' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('kardex_generated')
                    ->label('Kardex')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state ? 'Generado' : 'Pendiente')
                    ->icon(fn($state) => $state ? 'heroicon-o-check-badge' : 'heroicon-o-clock')
                    ->color(fn($state) => $state ? 'success' : 'warning')
                    ->sortable(),
                IconColumn::make('have_perception')
                    ->label('Percepción')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->boolean(),



                IconColumn::make('paid')
                    ->label('Pagada')
                    ->toggleable(isToggledHiddenByDefault: true)

                    ->boolean(),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordUrl(function ($record) {
                return self::getUrl('purchase',
                    [
                        'record' => $record->id
                    ]);
            })
            ->modifyQueryUsing(function ($query) {
                $query->with([
                    'provider:id,comercial_name',
                    'employee:id,name',
                    'wherehouse:id,name',
                    'purchaseItems'
                ])
                ->where('process_document_type','=','Compra');
            })
            ->defaultSort('purchase_date', 'desc')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->filters([
                DateRangeFilter::make('purchase_date')
                    ->timePicker24()
                    ->startDate(Carbon::now()->startOfMonth()) // Primer día del mes actual
                    ->endDate(Carbon::now()->endOfDay()) // Hoy al final del día
                    ->label('Fecha de Compra'),

                SelectFilter::make('status')
                    ->options([
                        'Procesando' => 'Procesando',
                        'Finalizado' => 'Finalizado',
                        'Anulado' => 'Anulado',
                    ])
                    ->multiple()
                    ->label('Estado'),

                SelectFilter::make('pruchase_condition')
                    ->options([
                        'Contado' => 'Contado',
                        'Crédito' => 'Crédito',
                    ])
                    ->label('Condición'),

                SelectFilter::make('provider_id')
                    ->relationship('provider', 'comercial_name')
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->label('Proveedor'),

                SelectFilter::make('wherehouse_id')
                    ->relationship('wherehouse', 'name')
                    ->multiple()
                    ->label('Sucursal'),

                SelectFilter::make('kardex_generated')
                    ->options([
                        '1' => 'Generado',
                        '0' => 'Pendiente',
                    ])
                    ->label('Kardex'),

                Filter::make('monto_range')
                    ->form([
                        TextInput::make('monto_min')
                            ->numeric()
                            ->label('Monto mínimo')
                            ->placeholder('0.00'),
                        TextInput::make('monto_max')
                            ->numeric()
                            ->label('Monto máximo')
                            ->placeholder('0.00'),
                    ])
                    ->query(function (EloquentBuilder $query, array $data): EloquentBuilder {
                        return $query
                            ->when(
                                $data['monto_min'],
                                fn (EloquentBuilder $query, $value): EloquentBuilder => $query->where('purchase_total', '>=', $value),
                            )
                            ->when(
                                $data['monto_max'],
                                fn (EloquentBuilder $query, $value): EloquentBuilder => $query->where('purchase_total', '<=', $value),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['monto_min'] ?? null) {
                            $indicators[] = 'Monto mín: $' . number_format($data['monto_min'], 2);
                        }
                        if ($data['monto_max'] ?? null) {
                            $indicators[] = 'Monto máx: $' . number_format($data['monto_max'], 2);
                        }
                        return $indicators;
                    }),
            ])
            ->recordActions([
                // ACCIÓN IMPRIMIR PDF: Siempre visible, se abre en nueva ventana
                Action::make('print_pdf')
                    ->icon('heroicon-o-printer')
                    ->iconButton()
                    ->color('info')
                    ->tooltip('Imprimir PDF de la compra')
                    ->url(fn(Purchase $record) => route('purchase.pdf', $record->id))
                    ->openUrlInNewTab(),

                // ACCIÓN EDITAR: Solo si NO tiene Kardex generado
                    EditAction::make()
                    ->icon('heroicon-o-pencil')
                    ->iconButton()
                    ->tooltip('Editar compra')
                    ->hidden(fn($record) => $record->kardex_generated || $record->status === 'Anulado'),

                // ACCIÓN AJUSTAR: Solo para compras finalizadas con Kardex generado
                Action::make('Ajustar')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->iconButton()
                    ->color('warning')
                    ->tooltip('Ajustar compra finalizada')
                    ->hidden(fn($record) => !$record->kardex_generated || $record->status === 'Anulado')
                    ->url(fn(Purchase $record) => self::getUrl('adjust', ['record' => $record->id])),

                // ACCIÓN ANULAR: Solo disponible para compras con Kardex generado
                Action::make('Anular')
                    ->requiresConfirmation()
                    ->modalHeading('Anular Compra')
                    ->modalDescription('Esta acción revertirá el Kardex y devolverá el stock al inventario. La compra quedará marcada como Anulada.')
                    ->modalSubmitActionLabel('Sí, Anular')
                    ->color('danger')
                    ->icon('heroicon-o-trash')
                    ->iconButton()
                    ->tooltip('Anular compra')
                    ->hidden(fn($record) => !$record->kardex_generated || $record->status === 'Anulado') // Solo visible si tiene Kardex y NO está Anulado
                    ->action(function (Purchase $purchase) {
                    $purchaseItems = PurchaseItem::where('purchase_id', $purchase->id)->get();

                    // VALIDACIÓN: Verificar stock disponible antes de anular
                    foreach ($purchaseItems as $item) {
                        $inventory = Inventory::find($item->inventory_id);

                        if (!$inventory) {
                            Notification::make('error_inventario')
                                ->title('Error de inventario')
                                ->body("No se encontró el inventario para el producto ID {$item->inventory_id}")
                                ->danger()
                                ->send();
                            return;
                        }

                        // Validar que haya stock suficiente para anular
                        if ($inventory->stock < $item->quantity) {
                            Notification::make('stock_insuficiente')
                                ->title('Stock insuficiente')
                                ->body("No se puede anular. El producto '{$inventory->product->name}' solo tiene {$inventory->stock} unidades disponibles, pero se necesitan {$item->quantity} para la anulación.")
                                ->danger()
                                ->send();
                            return;
                        }
                    }

                    // Si todas las validaciones pasan, procesar anulación
                    foreach ($purchaseItems as $item) {
                        $inventory = Inventory::find($item->inventory_id);

                        try {
                            // KardexService registra la anulación y actualiza stock/costo automáticamente
                            app(KardexService::class)->registrarAnulacionCompra($purchase, $item);
                        } catch (\Exception $e) {
                            Log::error("Error al crear Kardex para anulación de compra: {$item->id}", [
                                'error' => $e->getMessage()
                            ]);
                            Notification::make('error_kardex')
                                ->title('Error al anular')
                                ->body("Error al procesar la anulación: {$e->getMessage()}")
                                ->danger()
                                ->send();
                            return;
                        }
                    }

                    // Actualizar estado de la compra
                    $purchase->update(['status' => 'Anulado']);

                    Notification::make('anulacion_exitosa')
                        ->title('Compra anulada exitosamente')
                        ->body('La compra fue anulada de manera exitosa')
                        ->success()
                        ->send();
                }),
            ],position: RecordActionsPosition::BeforeColumns)
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('marcar_finalizado')
                        ->label('Marcar como Finalizado')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Finalizar Compras Seleccionadas')
                        ->modalDescription('¿Está seguro de marcar las compras seleccionadas como finalizadas?')
                        ->action(function (Collection $records) {
                            $updated = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'Procesando') {
                                    $record->update(['status' => 'Finalizado']);
                                    $updated++;
                                }
                            }
                            Notification::make()
                                ->success()
                                ->title('Compras actualizadas')
                                ->body("{$updated} compra(s) marcada(s) como finalizadas")
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    ExportBulkAction::make('Exportar')
                        ->label('Exportar')
                        ->icon('heroicon-o-arrow-down-tray'),

                    BulkAction::make('imprimir_multiple')
                        ->label('Imprimir PDFs')
                        ->icon('heroicon-o-printer')
                        ->color('info')
                        ->action(function (Collection $records) {
                            $ids = $records->pluck('id')->join(',');
                            return redirect()->route('purchases.bulk.pdf', ['ids' => $ids]);
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PurchaseItemsRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPurchases::route('/'),
            'create' => CreatePurchase::route('/create'),
            'edit' => EditPurchase::route('/{record}/edit'),
            'adjust' => AdjustPurchase::route('/{record}/adjust'),
            'purchase' => ViewPurchase::route('/{record}/sale'),
        ];
    }

}
