<?php

namespace App\Filament\Resources\Inventories;

use App\Services\CacheService;
use Auth;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\Layout\Grid;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Exception;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\Inventories\RelationManagers\PricesRelationManager;
use App\Filament\Resources\Inventories\RelationManagers\GroupingInventoryRelationManager;
use App\Filament\Resources\Inventories\Pages\ListInventories;
use App\Filament\Resources\Inventories\Pages\CreateInventory;
use App\Filament\Resources\Inventories\Pages\EditInventory;
use App\Filament\Exports\InventoryExporter;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Tribute;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\ReplicateAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Support\Colors\Color;

class InventoryResource extends Resource
{
    protected static function getWhereHouse(): string
    {
        return Auth::user()->employee->wherehouse->name ?? 'N/A'; // Si no hay valor, usa 'N/A'
    }

    protected static ?string $model = Inventory::class;
    protected static string | \UnitEnum | null $navigationGroup = 'Inventario';
    protected static ?string $label = 'Inventario'; // Singular
    protected static ?string $pluralLabel = "Lista de inventario";
    protected static ?string $badgeColor = 'danger';



//

    public static function form(Schema $schema): Schema
    {
        // Obtener tax desde cache
        $tax = CacheService::getDefaultTribute();
        if (!$tax) {
            $tax = (object)['rate' => 0, 'is_percentage' => false];
        }
        $divider = ($tax->is_percentage) ? 100 : 1;
        $iva = $tax->rate / $divider;
        return $schema
            ->components([
                Section::make()
                    ->compact()
                    ->columns(2)
                    ->schema([
                        Section::make('Informacion del Inventario')
                            ->columns(3)
                            ->compact()
                            ->schema([
                                Select::make('product_id')
                                    ->required()
                                    ->inlineLabel(false)
                                    ->preload()
                                    ->columnSpanFull()
                                    ->relationship('product', 'name')
                                    ->searchable(['name', 'sku'])
                                    ->placeholder('Seleccionar producto')
                                    ->loadingMessage('Cargando productos...')
                                    ->getOptionLabelsUsing(function ($record) {
                                        return "{$record->name} (SKU: {$record->sku})";  // Formato de la etiqueta
                                    }),

                                Select::make('branch_id')
                                    ->label('Sucursal')
                                    ->placeholder('Seleccionar sucursal')
                                    ->relationship('branch', 'name')
                                    ->preload()
                                    ->searchable(['name'])
                                    ->required(),

                                TextInput::make('stock')
                                    ->required()
                                    ->numeric()
                                    ->default(0),
                                Hidden::make('stock_actual')
                                    ->default(0) // Valor predeterminado para nuevos registros
                                    ->afterStateHydrated(function (Hidden $component, $state, $record) {
                                        if ($record) {
                                            $component->state($record->stock);
                                        }
                                    }),

                                TextInput::make('stock_min')
                                    ->label('Stock Minimo')
                                    ->required()
                                    ->numeric()
                                    ->default(0),
                                TextInput::make('stock_max')
                                    ->label('Stock Maximo')
                                    ->required()
                                    ->numeric()
                                    ->default(0),
                                TextInput::make('cost_without_taxes')
                                    ->required()
                                    ->prefix('$')
                                    ->label('C. sin IVA')
                                    ->numeric()
                                    ->inputMode('decimal')
                                    ->hintColor('red')
                                    ->debounce(500) // Espera 500 ms después de que el usuario deje de escribir
                                    ->afterStateUpdated(function ($state, callable $set) use ($iva) {
                                        $costWithoutTaxes = $state ?: 0; // Valor predeterminado en 0 si está vacío
                                        $costWithTaxes = number_format($costWithoutTaxes * $iva, 2,'.',''); // Cálculo del costo con impuestos
                                        $costWithTaxes += $costWithoutTaxes; // Suma el costo sin impuestos
                                        $set('cost_with_taxes',number_format( $costWithTaxes,2,'.','')); // Actualiza el campo
                                    })
                                    ->default(0.00),
                                TextInput::make('cost_with_taxes')
                                    ->label('C. + IVA')
                                    ->required()
                                    ->readOnly()
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0.00),


                            ]),
                        Section::make('Configuración')
                            ->columns(3)
                            ->compact()
                            ->schema([
                                Toggle::make('is_stock_alert')
                                    ->label('Alerta de stock minimo')
                                    ->default(true)
                                    ->required(),
                                Toggle::make('is_expiration_date')
                                    ->label('Tiene vencimiento')
                                    ->default(true)
                                    ->required(),
                                Toggle::make('is_active')
                                    ->default(true)
                                    ->label('Activo')
                                    ->required(),
                            ]) // Fin de la sección de configuración

                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Grid::make()
                    ->columns(1)
                    ->schema([
                        Split::make([
                            Grid::make()
                                ->columns(1)
                                ->schema([
                                    ImageColumn::make('product.images')
                                        ->placeholder('Sin imagen')
                                        ->defaultImageUrl(url('storage/products/noimage.png'))
                                        ->height(100)
                                        ->width(100)
                                        ->url(fn ($record) => $record->product->images ?? url('storage/products/noimage.png'))
                                        ->openUrlInNewTab()
                                        ->extraAttributes([
                                            'class' => 'rounded-lg shadow-sm',
                                            'loading' => 'lazy',
                                        ])
                                ])->grow(false),
                            Stack::make([
                                // ID del inventario (necesario para reportes)
                                TextColumn::make('id')
                                    ->label('ID')
                                    ->copyable()
                                    ->copyableState(fn ($state) => $state)
                                    ->copyMessage('ID copiado al portapapeles')
                                    ->copyMessageDuration(2000)
                                    ->color('gray')
                                    ->size('sm')
                                    ->badge()
                                    ->icon('heroicon-s-hashtag'),

                                // Nombre del producto con badge de estado
                                TextColumn::make('product.name')
                                    ->label('Producto')
                                    ->wrap()
                                    ->weight(FontWeight::Medium)
                                    ->icon('heroicon-s-cube')
                                    ->searchable()
                                    ->sortable()
                                    ->description(fn (Inventory $record): string =>
                                        $record->is_active ? '' : '⚠️ Inactivo'
                                    ),

                                // SKU con código de barras
                                TextColumn::make('product.sku')
                                    ->label('SKU')
                                    ->copyable()
                                    ->copyMessage('SKU copiado')
                                    ->copyMessageDuration(1500)
                                    ->icon('heroicon-s-qr-code')
                                    ->color('gray')
                                    ->searchable()
                                    ->sortable(),

                                // Sucursal
                                TextColumn::make('branch.name')
                                    ->label('Sucursal')
                                    ->icon('heroicon-s-building-office-2')
                                    ->badge()
                                    ->color('info')
                                    ->sortable(),

                                // Stock con indicador de estado
                                TextColumn::make('stock')
                                    ->label('Stock')
                                    ->numeric()
                                    ->badge()
                                    ->getStateUsing(function ($record) {
                                        if ($record->stock <= 0) {
                                            return 'SIN STOCK';
                                        } elseif ($record->stock <= $record->stock_min) {
                                            return number_format($record->stock, 0) . ' ⚠️';
                                        } else {
                                            return number_format($record->stock, 0);
                                        }
                                    })
                                    ->color(function ($record) {
                                        if ($record->stock <= 0) {
                                            return 'danger';
                                        } elseif ($record->stock <= $record->stock_min) {
                                            return 'warning';
                                        } elseif ($record->stock <= $record->stock_max * 0.3) {
                                            return 'info';
                                        } else {
                                            return 'success';
                                        }
                                    })
                                    ->icon(function ($record) {
                                        if ($record->stock <= 0) {
                                            return 'heroicon-o-x-circle';
                                        } elseif ($record->stock <= $record->stock_min) {
                                            return 'heroicon-o-exclamation-triangle';
                                        } else {
                                            return 'heroicon-o-check-circle';
                                        }
                                    })
                                    ->tooltip(function ($record) {
                                        return "Mín: {$record->stock_min} | Máx: {$record->stock_max}";
                                    })
                                    ->sortable(),

                                // Precio de venta
                                TextColumn::make('pricing_info')
                                    ->label('Precio')
                                    ->getStateUsing(function ($record) {
                                        $defaultPrice = collect($record->prices)->firstWhere('is_default', 1);
                                        $price = $defaultPrice ? $defaultPrice['price'] : 0;
                                        return '$' . number_format($price, 2);
                                    })
                                    ->icon('heroicon-s-currency-dollar')
                                    ->weight(FontWeight::Bold)
                                    ->color('success')
                                    ->description(function ($record) {
                                        $defaultPrice = collect($record->prices)->firstWhere('is_default', 1);
                                        $price = $defaultPrice ? $defaultPrice['price'] : 0;
                                        $cost = $record->cost_without_taxes ?? 0;

                                        if ($cost > 0 && $price > 0) {
                                            $margin = (($price - $cost) / $price) * 100;
                                            return 'Margen: ' . number_format($margin, 1) . '%';
                                        }
                                        return '';
                                    }),

                                // Badge de estado activo/inactivo
                                TextColumn::make('is_active')
                                    ->label('Estado')
                                    ->badge()
                                    ->getStateUsing(fn ($record) => $record->is_active ? 'Activo' : 'Inactivo')
                                    ->color(fn ($record) => $record->is_active ? 'success' : 'danger')
                                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-check-badge' : 'heroicon-o-no-symbol'),

                            ])->extraAttributes([
                                'class' => 'space-y-1.5'
                            ])
                                ->grow(),

                        ])


                    ]),

            ])
            ->contentGrid([
                'xs' => 1,  // 1 columna en móvil (< 640px)
                'sm' => 2,  // 2 columnas en pantallas pequeñas (≥ 640px)
                'md' => 2,  // 2 columnas en pantallas medianas (≥ 768px)
                'lg' => 3,  // 3 columnas en pantallas grandes (≥ 1024px)
                'xl' => 3,  // 3 columnas en pantallas extra grandes (≥ 1280px)
                '2xl' => 3, // 3 columnas máximo (≥ 1536px)
            ])
            ->deferLoading()
            ->striped()
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('branch_id')
                    ->relationship('branch', 'name')
                    ->label('Sucursal')
                    ->multiple()
                    ->preload()
                    ->default(Auth::user()->employee->wherehouse->id)
                    ->placeholder('Buscar por sucursal'),
                SelectFilter::make('product.category_id')
                    ->relationship('product.category', 'name')
                    ->label('Categoría')
                    ->searchable()
                    ->preload()
                    ->placeholder('Filtrar por categoría'),
                Filter::make('stock_bajo')
                    ->label('Stock Bajo')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereColumn('stock', '<', 'stock_min')),
                Filter::make('sin_stock')
                    ->label('Sin Stock')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('stock', '<=', 0)),
                Filter::make('stock_critico')
                    ->label('Stock Crítico')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereColumn('stock', '<=', 'stock_min'))
                    ->default(),
            ])->filtersFormColumns(3)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    ReplicateAction::make()
                        ->schema([
                            Select::make('branch_did')
                                ->relationship('branch', 'name')
                                ->label('Sucursal Destino')
                                ->required()
                                ->placeholder('Ingresa el ID de la sucursal'),
                        ])
                        ->beforeReplicaSaved(function (Inventory $record, \Filament\Actions\Action $action, $replica, array $data): void {
                            try {
                                $existencia = Inventory::withTrashed()
                                    ->where('product_id', $record->product_id)
                                    ->where('branch_id', $data['branch_did'])
                                    ->first();
                                if ($existencia) {
                                    // Si el registro está eliminado
                                    if ($existencia->trashed()) {
                                        Notification::make('Inventario Eliminado')
                                            ->title('Replicar Inventario')
                                            ->danger()
                                            ->body('El inventario ya existe en la sucursal destino, pero el estado es eliminado, restarualo para poder replicarlo')
                                            ->send();
                                        $action->halt(); // Detener la acción si el inventario está eliminado
                                    } else {
                                        // Si el registro existe y no está eliminado
                                        Notification::make('Registro Duplicado')
                                            ->danger()
                                            ->body('Ya existe un registro con el producto ' . $record->product->name . ' en la sucursal ' . $record->branch->name . '.')
                                            ->send();
                                        $action->halt(); // Detener la acción si se encuentra un registro duplicado
                                    }
                                }
                            } catch (Exception $e) {
                                $action->halt(); // Detener la acción en caso de error
                            }
                        }),
                    DeleteAction::make(),
                    ForceDeleteAction::make(),
                ])
                    ->link()
                    ->label('Acciones'),
            ])
            ->persistFiltersInSession()
            ->recordUrl(fn () => null)
            ->recordAction(null)
            ->headerActions([

            ])
            ->searchable('product.name', 'product.sku', 'branch.name', 'product.aplications')
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ExportAction::make()
                        ->exporter(InventoryExporter::class)
                        ->formats([
                            ExportFormat::Csv,
                        ])
                        ->formats([
                            ExportFormat::Xlsx,
                        ])
                        // or
                        ->formats([
                            ExportFormat::Xlsx,
                            ExportFormat::Csv,
                        ])

                ]),
            ]);
    }

    public static function getRelations(): array
    {
        $relations = [];



        return [
            PricesRelationManager::class,
            GroupingInventoryRelationManager::class,
        ];
    }




    public static function getPages(): array
    {
        return [
            'index' => ListInventories::route('/'),
            'create' => CreateInventory::route('/create'),
            'edit' => EditInventory::route('/{record}/edit'),
        ];
    }


}
