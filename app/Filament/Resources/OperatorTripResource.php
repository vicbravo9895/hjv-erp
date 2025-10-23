<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OperatorTripResource\Pages;
use App\Models\Trip;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class OperatorTripResource extends Resource
{
    protected static ?string $model = Trip::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';
    
    protected static ?string $navigationGroup = 'Mis Viajes';
    
    protected static ?string $modelLabel = 'Viaje';
    
    protected static ?string $pluralModelLabel = 'Mis Viajes';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return true; // Force navigation registration for debugging
    }

    public static function form(Form $form): Form
    {
        // Read-only form for operators
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Viaje')
                    ->schema([
                        Forms\Components\TextInput::make('origin')
                            ->label('Origen')
                            ->disabled(),
                        Forms\Components\TextInput::make('destination')
                            ->label('Destino')
                            ->disabled(),
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Fecha de Inicio')
                            ->disabled(),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Fecha de Fin')
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'planned' => 'Planeado',
                                'in_progress' => 'En Progreso',
                                'completed' => 'Completado',
                                'cancelled' => 'Cancelado',
                            ])
                            ->disabled(),
                    ])->columns(2),
                
                Forms\Components\Section::make('Recursos Asignados')
                    ->schema([
                        Forms\Components\TextInput::make('truck.display_name')
                            ->label('Tractocamión')
                            ->disabled(),
                        Forms\Components\TextInput::make('trailer.display_name')
                            ->label('Trailer')
                            ->disabled(),
                        Forms\Components\TextInput::make('operator.name')
                            ->label('Operador')
                            ->disabled(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('display_name')
                    ->label('Viaje')
                    ->searchable(['origin', 'destination'])
                    ->sortable(['origin', 'destination']),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'planned' => 'Planeado',
                        'in_progress' => 'En Progreso',
                        'completed' => 'Completado',
                        'cancelled' => 'Cancelado',
                        default => $state,
                    })
                    ->colors([
                        'secondary' => 'planned',
                        'warning' => 'in_progress',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ]),
                
                Tables\Columns\TextColumn::make('truck.display_name')
                    ->label('Tractocamión')
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('trailer.display_name')
                    ->label('Trailer')
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Fecha Inicio')
                    ->date('d/m/Y')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fecha Fin')
                    ->date('d/m/Y')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('travelExpenses_count')
                    ->label('Gastos')
                    ->counts('travelExpenses')
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('total_expenses')
                    ->label('Total Gastos')
                    ->money('MXN')
                    ->getStateUsing(fn ($record) => $record->travelExpenses()->sum('amount'))
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'planned' => 'Planeado',
                        'in_progress' => 'En Progreso',
                        'completed' => 'Completado',
                        'cancelled' => 'Cancelado',
                    ]),
                
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('start_from')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('start_until')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '>=', $date),
                            )
                            ->when(
                                $data['start_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '<=', $date),
                            );
                    }),
                
                Tables\Filters\Filter::make('active_trips')
                    ->label('Solo Viajes Activos')
                    ->query(fn (Builder $query): Builder => $query->active())
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('add_expense')
                    ->label('Agregar Gasto')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->url(fn ($record) => route('filament.operator.resources.travel-expenses.create', ['trip_id' => $record->id]))
                    ->visible(fn ($record) => $record->isActive()),
            ])
            ->bulkActions([
                // No bulk actions for operators
            ])
            ->defaultSort('start_date', 'desc')
            ->modifyQueryUsing(function (Builder $query) {
                // Temporarily show all trips for debugging
                return $query->limit(20);
            });
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información del Viaje')
                    ->schema([
                        Infolists\Components\TextEntry::make('origin')
                            ->label('Origen'),
                        Infolists\Components\TextEntry::make('destination')
                            ->label('Destino'),
                        Infolists\Components\TextEntry::make('start_date')
                            ->label('Fecha de Inicio')
                            ->date('d/m/Y'),
                        Infolists\Components\TextEntry::make('end_date')
                            ->label('Fecha de Fin')
                            ->date('d/m/Y'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Estado')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'planned' => 'Planeado',
                                'in_progress' => 'En Progreso',
                                'completed' => 'Completado',
                                'cancelled' => 'Cancelado',
                                default => $state,
                            })
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'planned' => 'secondary',
                                'in_progress' => 'warning',
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('completed_at')
                            ->label('Finalizado')
                            ->dateTime('d/m/Y H:i')
                            ->visible(fn ($record) => $record->completed_at),
                    ])->columns(2),

                Infolists\Components\Section::make('Recursos Asignados')
                    ->schema([
                        Infolists\Components\TextEntry::make('truck.display_name')
                            ->label('Tractocamión'),
                        Infolists\Components\TextEntry::make('trailer.display_name')
                            ->label('Trailer')
                            ->placeholder('Sin trailer asignado'),
                        Infolists\Components\TextEntry::make('operator.name')
                            ->label('Operador'),
                    ])->columns(3),

                Infolists\Components\Section::make('Resumen de Gastos')
                    ->schema([
                        Infolists\Components\TextEntry::make('travelExpenses_count')
                            ->label('Total de Gastos')
                            ->getStateUsing(fn ($record) => $record->travelExpenses()->count()),
                        Infolists\Components\TextEntry::make('total_expenses')
                            ->label('Monto Total')
                            ->money('MXN')
                            ->getStateUsing(fn ($record) => $record->travelExpenses()->sum('amount')),
                        Infolists\Components\TextEntry::make('fuel_expenses')
                            ->label('Gastos de Combustible')
                            ->money('MXN')
                            ->getStateUsing(fn ($record) => $record->travelExpenses()->where('expense_type', 'fuel')->sum('amount')),
                        Infolists\Components\TextEntry::make('pending_expenses')
                            ->label('Gastos Pendientes')
                            ->getStateUsing(fn ($record) => $record->travelExpenses()->where('status', 'pending')->count())
                            ->badge()
                            ->color('warning'),
                    ])->columns(4),
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
            'index' => Pages\ListOperatorTrips::route('/'),
            'view' => Pages\ViewOperatorTrip::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Operators cannot create trips
    }

    public static function canEdit($record): bool
    {
        return false; // Operators cannot edit trips
    }

    public static function canDelete($record): bool
    {
        return false; // Operators cannot delete trips
    }

    public static function canViewAny(): bool
    {
        return true; // Temporarily allow all users for debugging
    }

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();
        if ($user && $user->isOperator()) {
            return Trip::where('operator_id', $user->id)->active()->count();
        }
        return null;
    }
}