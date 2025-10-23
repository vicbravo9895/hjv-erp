<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProviderResource\Pages;
use App\Filament\Resources\ProviderResource\RelationManagers;
use App\Models\Provider;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProviderResource extends BaseResource
{
    protected static ?string $model = Provider::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    
    protected static ?string $navigationGroup = 'Finanzas';
    
    protected static ?int $navigationSort = 2;
    
    protected static ?string $modelLabel = 'Proveedor';
    
    protected static ?string $pluralModelLabel = 'Proveedores';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información General')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('service_type')
                            ->label('Tipo de Servicio')
                            ->maxLength(255),
                    ])->columns(2),
                
                Forms\Components\Section::make('Información de Contacto')
                    ->schema([
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
                        Forms\Components\Textarea::make('address')
                            ->label('Dirección')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('service_type')
                    ->label('Tipo de Servicio')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('contact_name')
                    ->label('Contacto')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('expenses_count')
                    ->label('Gastos')
                    ->counts('expenses')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('service_type')
                    ->label('Tipo de Servicio')
                    ->options(function () {
                        return Provider::query()
                            ->whereNotNull('service_type')
                            ->distinct()
                            ->pluck('service_type', 'service_type')
                            ->toArray();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Tables\Actions\DeleteAction $action, Provider $record) {
                        if ($record->expenses()->count() > 0) {
                            $action->cancel();
                            \Filament\Notifications\Notification::make()
                                ->title('No se puede eliminar')
                                ->body('Este proveedor tiene gastos asociados y no puede ser eliminado.')
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (Tables\Actions\DeleteBulkAction $action, $records) {
                            foreach ($records as $record) {
                                if ($record->expenses()->count() > 0) {
                                    $action->cancel();
                                    \Filament\Notifications\Notification::make()
                                        ->title('No se puede eliminar')
                                        ->body('Uno o más proveedores tienen gastos asociados y no pueden ser eliminados.')
                                        ->danger()
                                        ->send();
                                    return;
                                }
                            }
                        }),
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
            'index' => Pages\ListProviders::route('/'),
            'create' => Pages\CreateProvider::route('/create'),
            'edit' => Pages\EditProvider::route('/{record}/edit'),
        ];
    }

    /**
     * Check resource-specific access based on user role
     */
    protected static function checkResourceAccess($user, string $action, ?Model $record = null): bool
    {
        // Provider management access: Super Admin, Administrator, Accountant
        return $user->hasAnyRole(['super_admin', 'administrador', 'contador']);
    }
}
