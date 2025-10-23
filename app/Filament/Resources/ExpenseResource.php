<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Filament\Resources\ExpenseResource\RelationManagers;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Provider;
use App\Models\CostCenter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExpenseResource extends BaseResource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    
    protected static ?string $navigationGroup = 'Finanzas';
    
    protected static ?int $navigationSort = 3;
    
    protected static ?string $modelLabel = 'Gasto';
    
    protected static ?string $pluralModelLabel = 'Gastos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Gasto')
                    ->schema([
                        Forms\Components\DatePicker::make('date')
                            ->label('Fecha')
                            ->required()
                            ->default(now()),
                        Forms\Components\TextInput::make('amount')
                            ->label('Monto')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->minValue(0.01),
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
                
                Forms\Components\Section::make('Clasificación')
                    ->schema([
                        Forms\Components\Select::make('category_id')
                            ->label('Categoría')
                            ->required()
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('description')
                                    ->label('Descripción')
                                    ->rows(3),
                            ]),
                        Forms\Components\Select::make('provider_id')
                            ->label('Proveedor')
                            ->required()
                            ->relationship('provider', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('contact_name')
                                    ->label('Nombre de Contacto')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('phone')
                                    ->label('Teléfono')
                                    ->tel()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('service_type')
                                    ->label('Tipo de Servicio')
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('address')
                                    ->label('Dirección')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),
                        Forms\Components\Select::make('cost_center_id')
                            ->label('Centro de Costo')
                            ->required()
                            ->relationship('costCenter', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('description')
                                    ->label('Descripción')
                                    ->rows(3),
                                Forms\Components\TextInput::make('budget')
                                    ->label('Presupuesto')
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.01)
                                    ->minValue(0),
                            ]),
                    ])->columns(3),
                
                Forms\Components\Section::make('Documentación')
                    ->schema([
                        Forms\Components\TextInput::make('receipt_url')
                            ->label('URL del Recibo')
                            ->url()
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto')
                    ->money('MXN')
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 30) {
                            return null;
                        }
                        return $state;
                    }),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Categoría')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('provider.name')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('costCenter.name')
                    ->label('Centro de Costo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('receipt_url')
                    ->label('Recibo')
                    ->boolean()
                    ->trueIcon('heroicon-o-document-text')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('gray'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Categoría')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('provider_id')
                    ->label('Proveedor')
                    ->relationship('provider', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('cost_center_id')
                    ->label('Centro de Costo')
                    ->relationship('costCenter', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('date_to')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['date_to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
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
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }

    /**
     * Check resource-specific access based on user role
     */
    protected static function checkResourceAccess($user, string $action, ?Model $record = null): bool
    {
        // Expense management access: Super Admin, Administrator, Accountant
        return $user->hasAnyRole(['super_admin', 'administrador', 'contador']);
    }
}
