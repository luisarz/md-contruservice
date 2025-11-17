<?php

namespace App\Filament\Resources\Sales\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Auth;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Exception;
use App\Models\Distrito;
use App\Models\Inventory;
use App\Models\Price;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\RetentionTaxe;
use App\Models\SaleItem;
use App\Models\Tribute;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\ImageEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use App\Models\Sale;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Svg\Tag\Image;
use Symfony\Component\Console\Input\Input;
use Filament\Notifications\Notification;

class SaleItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'saleDetails';
    protected static ?string $title = "Prodúctos agregados";
    protected static ?string $pollingInterval = '1s';


    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('')
                    ->schema([

                        Grid::make(12)
                            ->schema([

                                Section::make('Venta')
                                    ->icon('heroicon-o-user')
                                    ->iconColor('success')
                                    ->compact()
                                    ->schema([


                                        Select::make('inventory_id')
                                            ->label('Producto')
                                            ->searchable()
                                            ->preload(true)
                                            ->live(onBlur: true)
                                            ->columnSpanFull()
                                            ->inlineLabel(false)
                                            ->getSearchResultsUsing(function (string $query, callable $get) {
                                                $whereHouse = Auth::user()->employee->branch_id; // Sucursal del usuario
                                                $aplications = $get('aplications');
                                                if (strlen($query) < 2) {
                                                    return []; // No buscar si el texto es muy corto
                                                }
                                                // Dividir el texto ingresado en palabras clave
//                                                $keywords = explode(' ', $query);
                                                $keywords = $query;

                                                return Inventory::with([
                                                    'product:id,name,sku,bar_code,aplications',
                                                    'prices' => function ($q) {
                                                        $q->where('is_default', 1)->select('id', 'inventory_id', 'price'); // Carga solo el precio predeterminado
                                                    },
                                                ])
                                                    ->select(['inventories.id', 'inventories.branch_id', 'inventories.product_id', 'inventories.stock']) // Selecciona solo las columnas necesarias
                                                    ->join('products', 'inventories.product_id', '=', 'products.id')
                                                    ->where('inventories.branch_id', $whereHouse) // Filtra por sucursal
                                                    ->whereExists(function ($q) {
                                                        $q->selectRaw(1)
                                                            ->from('prices')
                                                            ->whereColumn('prices.inventory_id', 'inventories.id')
                                                            ->where('prices.is_default', 1);
                                                    })
                                                    ->where(function ($q) use ($keywords) {
                                                        $q->where('products.name', 'like', "%$keywords%")
                                                            ->orWhere('products.sku', 'like', "%$keywords%")
                                                            ->orWhere('products.bar_code', 'like', "%$keywords%");
                                                    })
                                                    ->when(!empty($aplications), function ($q) use ($aplications) {
                                                        $q->where('products.aplications', 'like', "%{$aplications}%");
                                                    })
                                                    ->limit(50) // Limita el número de resultados
                                                    ->get()
                                                    ->mapWithKeys(function ($inventory) {
                                                        $price = optional($inventory->prices->first())->price; // Obtén el precio predeterminado
                                                        $displayText = "{$inventory->product->name} - Cod: {$inventory->product->sku} - STOCK: {$inventory->stock} - $ {$price}";
                                                        return [$inventory->id => $displayText];
                                                    });
                                            })
                                            ->getOptionLabelUsing(function ($value) {
                                                $inventory = Inventory::with('product')->find($value);
                                                return $inventory
                                                    ? "{$inventory->product->name} - SKU: {$inventory->product->sku} - Codigo: {$inventory->product->bar_code}"
                                                    : 'Producto no encontrado';
                                            })
                                            ->extraAttributes([
//                                                'class' => 'text-sm text-gray-700 font-semibold bg-gray-100 rounded-md', // Estilo de TailwindCSS
                                            ])
                                            ->required()
                                            ->afterStateUpdated(function (callable $get, callable $set) {
                                                $invetory_id = $get('inventory_id');

                                                $price = Price::with('inventory', 'inventory.product')->where('inventory_id', $invetory_id)->Where('is_default', true)->first();
                                                if ($price && $price->inventory) {
                                                    // Verificar si es un producto con stock
                                                    $isService = $price->inventory->product->is_service ?? false;
                                                    $currentStock = $price->inventory->stock ?? 0;

                                                    // Validar stock solo si no es servicio
                                                    if (!$isService && $currentStock <= 0) {
                                                        Notification::make()
                                                            ->danger()
                                                            ->title('Stock insuficiente')
                                                            ->body("El producto '{$price->inventory->product->name}' no tiene stock disponible (Stock actual: {$currentStock})")
                                                            ->persistent()
                                                            ->send();

                                                        $set('inventory_id', null);
                                                        return;
                                                    }

                                                    $set('price', $price->price);
                                                    $set('quantity', 1);
                                                    $set('discount', 0);
                                                    $set('minprice', $price->inventory->cost_with_taxes);
                                                    $set('current_stock', $currentStock);
                                                    $set('is_service', $isService);

                                                    $this->calculateTotal($get, $set);
                                                } else {
                                                    $set('price', $price->price ?? 0);
                                                    $set('quantity', 1);
                                                    $set('discount', 0);
                                                    $this->calculateTotal($get, $set);
                                                }

//
                                                $images = is_array($price->inventory->product->images ?? null)
                                                    ? $price->inventory->product->images
                                                    : [$price->inventory->product->images ?? null];
                                                // Si no hay imágenes, asignar una imagen por defecto
                                                if (empty($images) || $images[0] === null) {
                                                    $images = ['products\/noimage.jpg']; // Ruta de la imagen por defecto
                                                }
                                                $set('product_image', $images);


                                            }),
                                        TextInput::make('aplications')
                                            ->inlineLabel(false)
//                                            ->columnSpanFull()
                                            ->label('Aplicaciones'),
                                        Select::make('priceList')
                                            ->label('Precios')
                                            ->inlineLabel(false)
                                            ->options(function (callable $get) {
                                                $inventory_id = $get('inventory_id');

                                                if (!$inventory_id) {
                                                    return [];
                                                }

                                                // Fetch price details and format them
                                                $options = Price::where('inventory_id', $inventory_id)
                                                    ->get()
                                                    ->mapWithKeys(function ($price) {
                                                        return [$price->id => "{$price->name} - $: {$price->price}"];
                                                    });

                                                return $options;
                                            })
                                            ->reactive() // Ensure the field is reactive when the value changes
                                            ->afterStateUpdated(function (callable $get, $state, callable $set) {
                                                // This will automatically set the price to the corresponding price field when the select value changes
                                                $price = Price::find($state);
                                                if ($price) {
                                                    $set('price', $price->price ?? 0); // Set the 'price' field with the selected price
                                                    // Call the calculateTotal method after updating the price
                                                    $this->calculateTotal($get, $set);
                                                }
                                            }),








                                        TextInput::make('quantity')
                                            ->label('Cantidad')
                                            ->step(1)
                                            ->numeric()
                                            ->minValue(1)
                                            ->live(onBlur: true)
                                            ->columnSpan(1)
                                            ->required()
                                            ->extraAttributes(['onkeyup' => 'this.dispatchEvent(new Event("input"))'])
                                            ->afterStateUpdated(function (callable $get, callable $set, $state) {
                                                $isService = $get('is_service') ?? false;
                                                $currentStock = $get('current_stock') ?? 0;
                                                $requestedQty = floatval($state ?? 0);
                                                $inventoryId = $get('inventory_id');

                                                // Solo validar stock si no es servicio
                                                if (!$isService && $inventoryId) {
                                                    if ($requestedQty > $currentStock) {
                                                        Notification::make()
                                                            ->danger()
                                                            ->title('Stock insuficiente')
                                                            ->body("Cantidad solicitada ({$requestedQty}) excede el stock disponible ({$currentStock})")
                                                            ->persistent()
                                                            ->send();

                                                        // Establecer cantidad máxima al stock disponible
                                                        $set('quantity', $currentStock > 0 ? $currentStock : 1);
                                                    }
                                                }

                                                $this->calculateTotal($get, $set);
                                            })
                                            ->helperText(fn(callable $get) => $get('is_service')
                                                ? 'Servicio - Sin límite de stock'
                                                : 'Stock disponible: ' . ($get('current_stock') ?? 0)),

                                        TextInput::make('price')
                                            ->label('Precio')
                                            ->step(0.01)
                                            ->numeric()
                                            ->columnSpan(1)
                                            ->required()
//                                            ->readOnly()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (callable $get, callable $set) {
                                                $this->calculateTotal($get, $set);
                                            }),

                                        TextInput::make('discount')
                                            ->label('Descuento')
                                            ->step(0.01)
                                            ->prefix('%')
                                            ->numeric()
                                            ->live(onBlur: true)
                                            ->columnSpan(1)
                                            ->required()
                                            ->debounce(300)
                                            ->afterStateUpdated(function (callable $get, callable $set) {
                                                $this->calculateTotal($get, $set);
                                            }),

                                        TextInput::make('total')
                                            ->label('Total')
                                            ->step(0.01)
                                            ->readOnly()
                                            ->readOnly()
                                            ->columnSpan(1)
                                            ->required(),

//                                        Forms\Components\Toggle::make('is_except')
//                                            ->label('Exento de IVA')
//                                            ->columnSpan(1)
//                                            ->live()
//                                            ->afterStateUpdated(function (callable $get, callable $set) {
//                                                $this->calculateTotal($get, $set);
//                                            }),
                                         Toggle::make('is_tarjet')
                                             ->label('Con tarjeta')
                                             ->columnSpan(1)
                                             ->live(onBlur: true)
                                             ->afterStateUpdated(function (callable $get, callable $set) {
                                                 $price = $get('price'); // Obtener el precio actual
                                                 if ($get('is_tarjet')) {
                                                     $set('price', $price * 1.05);
                                                 } else {
                                                     $set('price', $price * 0.95);
                                                 }
                                                 $this->calculateTotal($get, $set);
                                             }),

                                        TextInput::make('minprice')
                                            ->label('Tributos')
                                            ->hidden(true)
                                            ->columnSpan(3)
                                            ->afterStateUpdated(function (callable $get, callable $set) {

                                            }),

                                        // Campos ocultos para control de stock
                                        TextInput::make('current_stock')
                                            ->hidden()
                                            ->default(0),

                                        TextInput::make('is_service')
                                            ->hidden()
                                            ->default(false),


                                    ])->columnSpan(9)
                                    ->extraAttributes([
                                        'class' => 'bg-blue-100 border border-blue-500 rounded-md p-2',
                                    ])
                                    ->columns(2),


                                Section::make('')
                                    ->compact()
                                    ->schema([
                                        Section::make('')
                                            ->compact()
                                            ->schema([
                                                Textarea::make('description')
                                                    ->label('Descripción')
                                                    ->inlineLabel(false)
                                            ]),
                                        Section::make('')
                                            ->compact()
                                            ->schema([
                                                FileUpload::make('product_image')
                                                    ->label('')
                                                    ->previewable(true)
                                                    ->openable()
                                                    ->storeFiles(false)
                                                    ->deletable(false)
                                                    ->disabled() // Desactiva el campo

                                                    ->image(),
                                            ]),


                                    ])
                                    ->extraAttributes([
//                                        'class' => 'bg-blue-100 border border-blue-500 rounded-md p-2',
                                    ])
                                    ->columnSpan(3)->columns(1),
                            ]),
                    ]),


            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Sales Item')
            ->columns([
                TextColumn::make('inventory.product.name')
                    ->wrap()
                    ->formatStateUsing(fn($record) => $record->inventory->product->name . '</br> ' . $record->description)
                    ->html()
                    ->label('Producto'),

                BooleanColumn::make('inventory.product.is_service')
                    ->label('Producto/Servicio')
                    ->trueIcon('heroicon-o-bug-ant') // Icono cuando `is_service` es true
                    ->falseIcon('heroicon-o-cog-8-tooth') // Icono cuando `is_service` es false

                    ->tooltip(function ($record) {
                        return $record->inventory->product->is_service ? 'Es un servicio' : 'No es un servicio';
                    }),


                TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->columnSpan(1),
                TextColumn::make('price')
                    ->label('Precio')
                    ->formatStateUsing(fn ($state) => number_format($state, 4)) // muestra hasta 4 decimales (ajusta si deseas)
                    ->suffix(' USD')
                    ->columnSpan(1),

                TextColumn::make('discount')
                    ->label('Descuento')
                    ->suffix('%')
                    ->numeric()
                    ->columnSpan(1),
                TextColumn::make('total')
                    ->label('Total')
                    ->summarize(Sum::make()->label('Total')->money('USD', locale: 'en_US'))
                    ->money('USD', locale: 'en_US')
                    ->columnSpan(1),
            ])
            ->headerActions([
                CreateAction::make()
                    ->modalWidth('7xl')
                    ->modalHeading('Agregar Producto a venta')
                    ->label('Agregar Producto')
                    ->before(function (array $data, CreateAction $action) {
                        // Validar stock antes de crear el registro
                        $inventoryId = $data['inventory_id'] ?? null;
                        $quantity = floatval($data['quantity'] ?? 0);

                        if ($inventoryId) {
                            $inventory = Inventory::with('product')->find($inventoryId);

                            if ($inventory) {
                                $isService = $inventory->product->is_service ?? false;
                                $currentStock = $inventory->stock ?? 0;

                                // Validar solo si no es servicio
                                if (!$isService && $quantity > $currentStock) {
                                    Notification::make()
                                        ->danger()
                                        ->title('Stock insuficiente')
                                        ->body("No se puede agregar el producto. Cantidad solicitada ({$quantity}) excede el stock disponible ({$currentStock})")
                                        ->persistent()
                                        ->send();

                                    // Detener la creación del registro
                                    $action->halt();
                                }
                            }
                        }
                    })
                    ->after(function (SaleItem $record, Component $livewire) {
                        $this->updateTotalSale($record);
                        $livewire->dispatch('refreshSale');
                    }),
            ])
            ->recordActions([
                EditAction::make('edit')
                    ->modalWidth('7xl')
                    ->mutateRecordDataUsing(function (array $data, SaleItem $record): array {
                        // Al cargar el formulario de edición, obtener stock actual
                        $inventory = Inventory::with('product')->find($record->inventory_id);
                        if ($inventory) {
                            $data['current_stock'] = $inventory->stock ?? 0;
                            $data['is_service'] = $inventory->product->is_service ?? false;
                        }
                        return $data;
                    })
                    ->before(function (array $data, EditAction $action, SaleItem $record) {
                        // Validar stock antes de actualizar
                        $inventoryId = $data['inventory_id'] ?? null;
                        $newQuantity = floatval($data['quantity'] ?? 0);
                        $oldQuantity = floatval($record->quantity ?? 0);

                        if ($inventoryId) {
                            $inventory = Inventory::with('product')->find($inventoryId);

                            if ($inventory) {
                                $isService = $inventory->product->is_service ?? false;
                                $currentStock = $inventory->stock ?? 0;

                                // Si la cantidad aumentó, validar el incremento
                                if (!$isService && $newQuantity > $oldQuantity) {
                                    $incremento = $newQuantity - $oldQuantity;

                                    if ($incremento > $currentStock) {
                                        Notification::make()
                                            ->danger()
                                            ->title('Stock insuficiente')
                                            ->body("No se puede aumentar la cantidad. Incremento solicitado ({$incremento}) excede el stock disponible ({$currentStock})")
                                            ->persistent()
                                            ->send();

                                        $action->halt();
                                    }
                                }
                            }
                        }
                    })
                    ->after(function (SaleItem $record, Component $livewire) {
                        $this->updateTotalSale($record);
                        $livewire->dispatch('refreshSale');

                    }),
                DeleteAction::make('delete')
                    ->label('Quitar')
                    ->after(function (SaleItem $record, Component $livewire) {
                        $this->updateTotalSale($record);
                        $livewire->dispatch('refreshSale');

                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->after(function (SaleItem $record, Component $livewire) {
                            $selectedRecords = $livewire->getSelectedTableRecords();
                            foreach ($selectedRecords as $record) {
                                $this->updateTotalSale($record);
                            }
                            $livewire->dispatch('refreshSale');
                        }),

                ]),
            ]);
    }

    protected function calculateTotal(callable $get, callable $set)
    {
        try {
            $quantity = ($get('quantity') !== "" && $get('quantity') !== null) ? $get('quantity') : 0;
            $price = ($get('price') !== "" && $get('price') !== null) ? $get('price') : 0;
            $discount = ($get('discount') !== "" && $get('discount') !== null) ? $get('discount') / 100 : 0;

            $is_except = $get('is_except');

            $total = $quantity * $price;

            if ($discount > 0) {
                $total -= $total * $discount;
            }
            if ($is_except) {
                $total -= ($total * 0.13);
            }

            // Formatear precio y total a dos decimales
            $price = round($price, 4);
            $total = round($total, 4);

            $set('price', $price);
            $set('total', $total);
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }


    }

    protected function updateTotalSale(SaleItem $record)
    {
        $idSale = $record->sale_id;
        $sale = Sale::where('id', $idSale)->first();
        $documentType = $sale->document_type_id ?? null;

//        dd($sale);
        if ($sale) {
            try {
                $ivaRate = Tribute::where('code', 20)->value('rate') ?? 0;
                $isrRate = RetentionTaxe::where('code', 22)->value('rate') ?? 0;

                $ivaRate = is_numeric($ivaRate) ? $ivaRate / 100 : 0;
                $isrRate = is_numeric($isrRate) ? $isrRate / 100 : 0;
                $montoTotal = SaleItem::where('sale_id', $sale->id)->sum('total') ?? 0;
//            dd($montoTotal);
                $neto = $ivaRate > 0 ? $montoTotal / (1 + $ivaRate) : $montoTotal;
                $iva = $montoTotal - $neto;
                if($documentType==11 || $documentType==14){
                    $neto=$neto+$iva;
                    $iva = 0;
                }
                $retention = $sale->have_retention ? $neto * 0.1 : 0;
                $sale->net_amount = round($neto, 4);
                $sale->taxe = round($iva, 2);
                $sale->retention = round($retention, 4);
                $sale->sale_total = round($montoTotal - $retention, 4);
                $sale->save();
            } catch (Exception $e) {
                Log::error($e->getMessage());
            }


        }
    }

//    public function isReadOnly(): bool
//    {
//        return false;
//    }


}