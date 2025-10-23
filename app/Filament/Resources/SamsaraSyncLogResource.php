<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SamsaraSyncLogResource\Pages;
use App\Models\SamsaraSyncLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;

class SamsaraSyncLogResource extends Resource
{
    protected static ?string $model = SamsaraSyncLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationLabel = 'Samsara Sync Logs';

    protected static ?string $navigationGroup = 'Sistema';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('sync_type')
                    ->required()
                    ->disabled(),
                Forms\Components\Select::make('status')
                    ->options([
                        'running' => 'Running',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                    ])
                    ->required()
                    ->disabled(),
                Forms\Components\DateTimePicker::make('started_at')
                    ->required()
                    ->disabled(),
                Forms\Components\DateTimePicker::make('completed_at')
                    ->disabled(),
                Forms\Components\TextInput::make('synced_records')
                    ->numeric()
                    ->disabled(),
                Forms\Components\TextInput::make('duration_seconds')
                    ->numeric()
                    ->disabled(),
                Forms\Components\Textarea::make('error_message')
                    ->disabled()
                    ->rows(3),
                Forms\Components\KeyValue::make('params')
                    ->disabled(),
                Forms\Components\KeyValue::make('additional_data')
                    ->disabled(),
                Forms\Components\KeyValue::make('error_details')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sync_type')
                    ->label('Sync Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'vehicles' => 'success',
                        'trailers' => 'info',
                        'drivers' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                
                BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'running',
                        'success' => 'completed',
                        'danger' => 'failed',
                    ])
                    ->sortable(),
                
                TextColumn::make('started_at')
                    ->label('Started')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->toggleable(),
                
                TextColumn::make('completed_at')
                    ->label('Completed')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->toggleable(),
                
                TextColumn::make('synced_records')
                    ->label('Records')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                
                TextColumn::make('formatted_duration')
                    ->label('Duration')
                    ->toggleable(),
                
                TextColumn::make('error_message')
                    ->label('Error')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    })
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('sync_type')
                    ->options([
                        'vehicles' => 'Vehicles',
                        'trailers' => 'Trailers',
                        'drivers' => 'Drivers',
                    ]),
                
                SelectFilter::make('status')
                    ->options([
                        'running' => 'Running',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('started_at', 'desc')
            ->poll('30s'); // Auto-refresh every 30 seconds
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
            'index' => Pages\ListSamsaraSyncLogs::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Sync logs are created automatically
    }

    public static function canEdit($record): bool
    {
        return false; // Sync logs should not be edited
    }
}
