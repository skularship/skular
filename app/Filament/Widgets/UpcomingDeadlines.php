<?php

namespace App\Filament\Widgets;

use App\Enums\ScholarshipStatus;
use App\Filament\Resources\Scholarships\ScholarshipResource;
use App\Models\Scholarship;
use Carbon\CarbonImmutable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class UpcomingDeadlines extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return ScholarshipResource::canViewAny();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Deadline Beasiswa Terdekat')
            ->query(fn (): Builder => Scholarship::query()
                ->where('status', ScholarshipStatus::Active)
                ->whereNotNull('closing_date')
                ->whereDate('closing_date', '>=', CarbonImmutable::today())
                ->orderBy('closing_date'))
            ->columns([
                TextColumn::make('scholarship_name')
                    ->label('Beasiswa')
                    ->searchable()
                    ->weight('semibold'),
                TextColumn::make('institution')
                    ->label('Institusi'),
                TextColumn::make('level')
                    ->label('Jenjang')
                    ->badge(),
                TextColumn::make('closing_date')
                    ->label('Ditutup')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('days_remaining')
                    ->label('Sisa hari')
                    ->state(fn (Scholarship $record): int => $record->daysRemaining() ?? 0)
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 7 => 'danger',
                        $state <= 30 => 'warning',
                        default => 'success',
                    }),
            ])
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->recordUrl(null);
    }
}
