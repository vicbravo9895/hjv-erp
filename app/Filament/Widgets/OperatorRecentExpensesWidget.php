<?php

namespace App\Filament\Widgets;

use App\Models\TravelExpense;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class OperatorRecentExpensesWidget extends BaseWidget
{
    protected static ?string $heading = 'Gastos Recientes';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                TravelExpense::query()
                    ->where('operator_id', Auth::id())
                    ->latest('date')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('trip.display_name')
                    ->label('Viaje')
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 30) {
                            return null;
                        }
                        return $state;
                    }),
                
                Tables\Columns\TextColumn::make('expense_type_display')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Combustible' => 'warning',
                        'Peajes' => 'info',
                        'Alimentación' => 'success',
                        'Hospedaje' => 'primary',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto')
                    ->money('MXN')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('status_display')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pendiente' => 'warning',
                        'Aprobado' => 'success',
                        'Reembolsado' => 'primary',
                        default => 'gray',
                    }),
                
                Tables\Columns\IconColumn::make('has_attachments')
                    ->label('Archivos')
                    ->boolean()
                    ->trueIcon('heroicon-o-paper-clip')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->getStateUsing(fn ($record) => $record->attachments()->exists()),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->url(fn (TravelExpense $record): string => 
                        route('filament.operator.resources.travel-expenses.view', $record)
                    ),
                
                Tables\Actions\Action::make('edit')
                    ->label('Editar')
                    ->icon('heroicon-o-pencil')
                    ->url(fn (TravelExpense $record): string => 
                        route('filament.operator.resources.travel-expenses.edit', $record)
                    )
                    ->visible(fn (TravelExpense $record): bool => $record->status === 'pending'),
            ])
            ->emptyStateHeading('Sin gastos registrados')
            ->emptyStateDescription('Aún no has registrado ningún gasto de viaje.')
            ->emptyStateIcon('heroicon-o-receipt-percent')
            ->emptyStateActions([
                Tables\Actions\Action::make('create_expense')
                    ->label('Registrar Primer Gasto')
                    ->icon('heroicon-o-plus')
                    ->url(route('filament.operator.resources.travel-expenses.create'))
                    ->button(),
            ]);
    }
}