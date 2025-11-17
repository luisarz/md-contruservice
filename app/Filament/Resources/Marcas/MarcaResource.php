<?php

namespace App\Filament\Resources\Marcas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\Marcas\Pages\ListMarcas;
use App\Filament\Resources\MarcaResource\Pages;
use App\Filament\Resources\MarcaResource\RelationManagers;
use App\Models\Marca;
use CharrafiMed\GlobalSearchModal\Customization\Position;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconSize;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MarcaResource extends Resource
{
    protected static ?string $model = Marca::class;

    protected static bool $softDelete = true;
    protected static string | \UnitEnum | null $navigationGroup = "Almacén";
    protected static ?string $label="Marcas";
    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información de Marca de prodúctos')
                    ->schema([
                        TextInput::make('nombre')
                            ->required()
                            ->inlineLabel(false)
                            ->maxLength(255),
                        TextInput::make('descripcion')
                            ->inlineLabel(false)

                            ->required()
                            ->maxLength(255),
                        FileUpload::make('imagen')
                            ->image()
                            ->directory('marcas'),

                        Toggle::make('estado')
                            ->label('Activo')
                            ->default(true)
                            ->required(),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('nombre')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('descripcion')
                    ->searchable(),
                ImageColumn::make('imagen')
                    ->placeholder('Sin imagen')

                    ->circular(),
                IconColumn::make('estado')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()->label('')->iconSize(IconSize::Medium),
                DeleteAction::make()->label('')->iconSize(IconSize::Medium),
            ],position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => ListMarcas::route('/'),
//            'create' => Pages\CreateMarca::route('/create'),
//            'edit' => Pages\EditMarca::route('/{record}/edit'),
        ];
    }
}
