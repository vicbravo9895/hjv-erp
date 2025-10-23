<?php

namespace App\Filament\Widgets;

use App\Models\Trip;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentTripsWidget extends BaseWidget
{
    protected static ?string $heading = 'Viajes Recientes';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Trip::query()
                    ->with(['truck', 'trailer', 'operator'])
                    ->latest('start_date')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('display_name')
                    ->label('Viaje')
                    ->searchable(['origin', 'destination'])
                    ->limit(30),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estatus')
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
                    ->limit(20),

                Tables\Columns\TextColumn::make('operator.name')
                    ->label('Operador')
                    ->limit(20),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Fecha Inicio')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_cost')
                    ->label('Costo')
                    ->money('MXN')
                    ->placeholder('$0.00'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Ver')
                    ->icon('heroicon-m-eye')
                    ->url(fn (Trip $record): string => route('filament.admin.resources.trips.edit', $record))
                    ->openUrlInNewTab(false),
            ])
            ->paginated(false);
    }
}