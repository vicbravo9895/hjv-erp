<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WeeklyPayrollResource\Pages;
use App\Models\WeeklyPayroll;
use App\Services\PaymentCalculationService;
use Filament\Forms;
use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class WeeklyPayrollResource extends BaseResource
{
    protected static ?string $model = WeeklyPayroll::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    
    protected static ?string $navigationGroup = 'Finanzas';
    
    protected static ?int $navigationSort = 4;
    
    protected static ?string $modelLabel = 'Nómina Semanal';
    
    protected static ?string $pluralModelLabel = 'Nóminas Semanales';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Nómina')
                    ->schema([
                        Forms\Components\Select::make('operator_id')
                            ->label('Operador')
                            ->relationship('operator', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                            
                        Forms\Components\DatePicker::make('week_start')
                            ->label('Inicio de Semana')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $weekStart = Carbon::parse($state)->startOfWeek();
                                    $weekEnd = $weekStart->copy()->endOfWeek();
                                    $set('week_start', $weekStart->toDateString());
                                    $set('week_end', $weekEnd->toDateString());
                                }
                            }),
                            
                        Forms\Components\DatePicker::make('week_end')
                            ->label('Fin de Semana')
                            ->required()
                            ->disabled(),
                    ])
                    ->columns(3),
                    
                Forms\Components\Section::make('Cálculos de Pago')
                    ->schema([
                        Forms\Components\TextInput::make('trips_count')
                            ->label('Número de Viajes')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state !== null) {
                                    $paymentService = app(PaymentCalculationService::class);
                                    $basePayment = $paymentService->calculateBasePayment((int) $state);
                                    $set('base_payment', $basePayment);
                                    
                                    $adjustments = $get('adjustments') ?? 0;
                                    $totalPayment = $paymentService->calculateTotalPayment($basePayment, $adjustments);
                                    $set('total_payment', $totalPayment);
                                }
                            }),
                            
                        Forms\Components\TextInput::make('base_payment')
                            ->label('Pago Base')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->disabled(),
                            
                        Forms\Components\TextInput::make('adjustments')
                            ->label('Ajustes')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->default(0)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $basePayment = $get('base_payment') ?? 0;
                                $adjustments = $state ?? 0;
                                $paymentService = app(PaymentCalculationService::class);
                                $totalPayment = $paymentService->calculateTotalPayment($basePayment, $adjustments);
                                $set('total_payment', $totalPayment);
                            })
                            ->helperText('Ajustes adicionales (positivos o negativos)'),
                            
                        Forms\Components\TextInput::make('total_payment')
                            ->label('Pago Total')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->disabled(),
                    ])
                    ->columns(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('operator.name')
                    ->label('Operador')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('week_range')
                    ->label('Semana')
                    ->sortable(['week_start'])
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('trips_count')
                    ->label('Viajes')
                    ->sortable()
                    ->alignCenter(),
                    
                Tables\Columns\TextColumn::make('base_payment')
                    ->label('Pago Base')
                    ->money('MXN')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('adjustments')
                    ->label('Ajustes')
                    ->money('MXN')
                    ->sortable()
                    ->color(fn ($record) => $record->adjustments > 0 ? 'success' : ($record->adjustments < 0 ? 'danger' : 'gray')),
                    
                Tables\Columns\TextColumn::make('total_payment')
                    ->label('Pago Total')
                    ->money('MXN')
                    ->sortable()
                    ->weight('bold'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('operator_id')
                    ->label('Operador')
                    ->relationship('operator', 'name')
                    ->searchable()
                    ->preload(),
                    
                Filter::make('week_range')
                    ->form([
                        Forms\Components\DatePicker::make('week_from')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('week_until')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['week_from'],
                                fn (Builder $query, $date): Builder => $query->where('week_start', '>=', $date),
                            )
                            ->when(
                                $data['week_until'],
                                fn (Builder $query, $date): Builder => $query->where('week_end', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('calculate')
                    ->label('Recalcular')
                    ->icon('heroicon-o-calculator')
                    ->action(function (WeeklyPayroll $record) {
                        $paymentService = app(PaymentCalculationService::class);
                        $paymentService->recalculatePayroll($record);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Recalcular Nómina')
                    ->modalDescription('¿Está seguro de que desea recalcular esta nómina basándose en los viajes actuales?'),
                    
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('week_start', 'desc')
            ->headerActions([
                Tables\Actions\Action::make('generate_weekly_payroll')
                    ->label('Generar Nómina Semanal')
                    ->icon('heroicon-o-plus-circle')
                    ->form([
                        Forms\Components\DatePicker::make('week_date')
                            ->label('Fecha de la Semana')
                            ->required()
                            ->helperText('Seleccione cualquier fecha de la semana para generar la nómina'),
                            
                        Forms\Components\Select::make('operator_id')
                            ->label('Operador (Opcional)')
                            ->relationship('operator', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Generar para todos los operadores'),
                    ])
                    ->action(function (array $data) {
                        $weekStart = Carbon::parse($data['week_date'])->startOfWeek();
                        $paymentService = app(PaymentCalculationService::class);
                        
                        if ($data['operator_id']) {
                            // Generate for specific operator
                            $paymentService->calculateWeeklyPayroll($data['operator_id'], $weekStart);
                        } else {
                            // Generate for all operators with trips that week
                            $paymentService->calculateWeeklyPayrollForAllOperators($weekStart);
                        }
                    })
                    ->modalHeading('Generar Nómina Semanal')
                    ->modalDescription('Genere automáticamente la nómina basada en los viajes completados.')
                    ->successNotificationTitle('Nómina generada exitosamente'),
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
            'index' => Pages\ListWeeklyPayrolls::route('/'),
            'create' => Pages\CreateWeeklyPayroll::route('/create'),
            'edit' => Pages\EditWeeklyPayroll::route('/{record}/edit'),
        ];
    }

    /**
     * Check resource-specific access based on user role
     */
    protected static function checkResourceAccess($user, string $action, ?Model $record = null): bool
    {
        // Payroll management access: Super Admin, Administrator, Accountant
        return $user->hasAnyRole(['super_admin', 'administrador', 'contador']);
    }
}
