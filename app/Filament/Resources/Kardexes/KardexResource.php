<?php

namespace App\Filament\Resources\Kardexes;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Tables\Enums\RecordActionsPosition;
use App\Services\Inventory\KardexService;
use Filament\Notifications\Notification;
use Log;
use App\Filament\Resources\Kardexes\Pages\ListKardexes;
// use App\Filament\Resources\Kardexes\Pages\CreateKardex; // No usado - creación deshabilitada
use App\Filament\Resources\KardexResource\Pages;
use App\Models\Kardex;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Filament\Tables\Grouping\Group;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class KardexResource extends Resource
{
    protected static ?string $model = Kardex::class;

    protected static ?string $label = 'Kardex productos';
    protected static string | \UnitEnum | null $navigationGroup = 'Inventario';

    public static function form(Schema $schema): Schema
    {
        // FORMULARIO DE SOLO LECTURA:
        // Los registros de Kardex no deben crearse ni editarse manualmente.
        // Este formulario solo se usa para la vista de detalles (ViewAction).
        return $schema
            ->components([
                TextInput::make('inventory.product.name')
                    ->label('Producto')
                    ->disabled(),

                DatePicker::make('date')
                    ->label('Fecha')
                    ->disabled(),
                TextInput::make('operation_type')
                    ->label('Tipo de Operación')
                    ->maxLength(255)
                    ->disabled(),
                TextInput::make('document_type')
                    ->label('Tipo de Documento')
                    ->maxLength(255)
                    ->disabled(),
                TextInput::make('document_number')
                    ->label('N° Documento')
                    ->maxLength(255)
                    ->disabled(),
                TextInput::make('entity')
                    ->label('Entidad')
                    ->maxLength(255)
                    ->disabled(),
                TextInput::make('inventory_id')
                    ->label('ID Inventario')
                    ->numeric()
                    ->disabled(),
                TextInput::make('previous_stock')
                    ->label('Stock Anterior')
                    ->numeric()
                    ->disabled(),
                TextInput::make('stock_in')
                    ->label('Entrada')
                    ->numeric()
                    ->disabled(),
                TextInput::make('stock_out')
                    ->label('Salida')
                    ->numeric()
                    ->disabled(),
                TextInput::make('stock_actual')
                    ->label('Stock Actual')
                    ->numeric()
                    ->disabled(),
                TextInput::make('money_in')
                    ->label('Dinero Entrada')
                    ->numeric()
                    ->prefix('$')
                    ->disabled(),
                TextInput::make('money_out')
                    ->label('Dinero Salida')
                    ->numeric()
                    ->prefix('$')
                    ->disabled(),
                TextInput::make('money_actual')
                    ->label('Saldo')
                    ->numeric()
                    ->prefix('$')
                    ->disabled(),
                TextInput::make('purchase_price')
                    ->label('Costo de Compra')
                    ->numeric()
                    ->prefix('$')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {

        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('inventory_id')
                    ->label('ID Inventario')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Oculto cuando está agrupado
                TextColumn::make('date')
                    ->label('Fecha')
                    ->date('d-m-Y')
                    ->sortable(),
                TextColumn::make('document_number')
                    ->label('N°')
                    ->searchable(),
                TextColumn::make('document_type')
                    ->label('Tipo')
                    ->searchable(),
                TextColumn::make('entity')
                    ->label('Razon Social')
                    ->searchable(),
                TextColumn::make('nationality')
                    ->label('Nacionalidad')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('wherehouse.name')
                    ->label('Sucursal')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('inventory.product.name')
                    ->label('Producto')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Oculto cuando está agrupado
                TextColumn::make('inventory.product.unitmeasurement.description')
                    ->label('U. Medida')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('operation_type')
                    ->label('Operación')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),


                TextColumn::make('previous_stock')
                    ->label('S. Anterior')
                    ->numeric()
                    ->formatStateUsing(fn ($state) => number_format($state, 2))
                    ->extraAttributes(['class' => ' color-success bg-success-200'])
                    ->sortable(),
                ColumnGroup::make('DETALLE DE UNIDADES ( CANT)', [
                    TextColumn::make('stock_in')
                        ->label('Entrada')
                        ->numeric()
                        ->color('success')
                        ->formatStateUsing(fn ($state) => number_format($state, 2))
                        ->summarize(Sum::make()
                            ->label('Entrada')
                            ->numeric()
                            ->formatStateUsing(fn ($state) => number_format($state, 2))
                        )
                        ->sortable(),
                    TextColumn::make('stock_out')
                        ->label('Salida')
                        ->numeric()
                        ->color('danger')
                        ->formatStateUsing(fn ($state) => number_format($state, 2))
                        ->summarize(Sum::make()
                            ->label('Salida')
                            ->numeric()
                            ->formatStateUsing(fn ($state) => number_format($state, 2))
                        )
                        ->sortable(),

                    TextColumn::make('stock_actual')
                        ->label('Existencia')
                        ->numeric()
                        ->formatStateUsing(fn ($state) => number_format($state, 2))
                        ->summarize(Sum::make()
                            ->label('Existencia')
                            ->numeric()
                            ->formatStateUsing(fn ($state) => number_format($state, 2))
                            ->suffix(new HtmlString(' U'))
                        )
                        ->sortable(),
                ]),
                TextColumn::make('purchase_price')
                    ->money('USD', locale: 'es_SV')
                    ->label('Costo')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultGroup('inventory_id') // Agrupar por inventario por defecto
            ->groups([
                Group::make('wherehouse.name')
                    ->label('Sucursal')
                    ->collapsible(),
                Group::make('inventory_id')
                    ->label('Inventario')
                    ->collapsible()
                    ->getTitleFromRecordUsing(function ($record) {
                        $productName = $record->inventory->product->name ?? 'N/A';
                        $productSku = $record->inventory->product->sku ?? 'N/A';
                        return "ID: {$record->inventory_id} - {$productName} (SKU: {$productSku})";
                    }),
                Group::make('inventory.product.name')
                    ->label('Producto')
                    ->collapsible()
                    ->titlePrefixedWithLabel(false),
                Group::make('date')
                    ->date()
                    ->label('Fecha Operación')
                    ->collapsible(),
            ])
            ->filters([
                DateRangeFilter::make('date')->timePicker24()
                    ->label('Fecha de operación')
                    ->startDate(Carbon::now()->startOfMonth()) // Primer día del mes actual
                    ->endDate(Carbon::now()->endOfDay()), // Hoy al final del día


                Filter::make('inventory_id')
                    ->label('Inventario ID')
                    ->schema([
                        TextInput::make('inventory_id')
                            ->inlineLabel(false)
                            ->label('Inventario')
                            ->numeric(),
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['inventory_id'])) {
                            $query->where('inventory_id', $data['inventory_id']);
                        }
                    }),

                SelectFilter::make('operation_type')
                    ->label('Tipo de Movimiento')
                    ->options([
                        'Compra' => 'Compra',
                        'Venta' => 'Venta',
                        'Anulacion' => 'Anulación',
                        'Nota de Credito' => 'Nota de Crédito',
                        'Nota de Credito Compra' => 'Nota de Crédito Compra',
                        'Traslado Entrada' => 'Traslado Entrada',
                        'Traslado Salida' => 'Traslado Salida',
                        'Ajuste Entrada' => 'Ajuste Entrada',
                        'Ajuste Salida' => 'Ajuste Salida',
                        'INVENTARIO INICIAL' => 'Inventario Inicial',
                    ])
                    ->multiple()
//                    ->searchable()
                    ->placeholder('Todos los movimientos'),

                Filter::make('stock_negativo')
                    ->label('Inicio stock negativo')
                    ->toggle()
                    ->query(fn ($query) => $query
                        ->where('previous_stock', '>=', 0)
                        ->where('stock_actual', '<', 0)
                    ),

            ],layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->recordActions([
                // ACCIÓN AJUSTAR: Solo para inventarios iniciales
                Action::make('ajustar_inicial')
                    ->label('Ajustar')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->color('warning')
                    ->visible(fn($record) => $record->operation_type === 'INVENTARIO INICIAL')
                    ->requiresConfirmation()
                    ->modalHeading('Ajustar Inventario Inicial')
                    ->modalDescription('Modifica manualmente el stock inicial. Todos los movimientos posteriores se recalcularán automáticamente.')
                    ->modalSubmitActionLabel('Guardar Ajuste')
                    ->fillForm(fn(Kardex $record): array => [
                        'stock_inicial' => $record->stock_actual,
                        'costo_inicial' => $record->purchase_price,
                    ])
                    ->form([
                        Section::make('Ajuste de Inventario Inicial')
                            ->schema([
                                TextInput::make('stock_inicial')
                                    ->label('Stock Inicial (Cantidad)')
                                    ->numeric()
                                    ->required()
                                    ->columnSpanFull()
                                    ->minValue(0)
                                    ->helperText('Ingresa la cantidad correcta del inventario inicial'),

                                TextInput::make('costo_inicial')
                                    ->label('Costo Unitario Inicial')
                                    ->numeric()
                                    ->required()
                                    ->columnSpanFull()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->prefix('$')
                                    ->helperText('Ingresa el costo por unidad del inventario inicial'),

                                TextInput::make('motivo')
                                    ->label('Motivo del Ajuste')
                                    ->columnSpanFull()
                                    ->required()
                                    ->placeholder('Ej: Corrección por conteo físico, Error en registro inicial, etc.')
                            ])->columns(2),
                    ])
                    ->action(function (Kardex $record, array $data) {
                        try {
                            $kardexService = app(KardexService::class);
                            $kardexService->ajustarInventarioInicial($record, $data);

                            Notification::make('ajuste_exitoso')
                                ->title('Inventario inicial ajustado exitosamente')
                                ->body('El stock inicial fue actualizado y los movimientos posteriores fueron recalculados.')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Log::error("Error al ajustar inventario inicial: {$record->id}", [
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);

                            Notification::make('error_ajuste')
                                ->title('Error al ajustar inventario inicial')
                                ->body("Error: {$e->getMessage()}")
                                ->danger()
                                ->send();
                        }
                    }),

                // DeleteAction DESHABILITADO: Los registros de Kardex no deben eliminarse manualmente
                // para mantener la integridad del historial contable. Solo pueden eliminarse
                // cuando se anula la operación origen (venta, compra, etc.)
                // DeleteAction::make('delete')
                //     ->label('')
                //     ->icon('heroicon-o-trash'),
                ViewAction::make()->label(''),
                // EditAction DESHABILITADO: Kardex es solo lectura, se genera automáticamente
                // Tables\Actions\EditAction::make('edit')->label(''),
            ], position: RecordActionsPosition::BeforeCells);
            // NOTA: No hay toolbarActions (export/delete) porque:
            // - Export: Se usa el "Reporte de Movimientos de Inventario" dedicado
            // - Delete: Deshabilitado para mantener integridad del historial
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKardexes::route('/'),
            // CREATE DESHABILITADO: Los registros de Kardex se crean automáticamente
            // mediante KardexService cuando ocurren operaciones (ventas, compras, etc.)
            // 'create' => CreateKardex::route('/create'),
            // EDIT DESHABILITADO: Kardex es solo lectura para mantener integridad contable
            // 'edit' => Pages\EditKardex::route('/{record}/edit'),
        ];
    }
}
