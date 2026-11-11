<?php

namespace App\Filament\Resources\ScholarshipInfoReports;

use App\Enums\ReportStatus;
use App\Filament\Resources\ScholarshipInfoReports\Pages\ListScholarshipInfoReports;
use App\Filament\Resources\ScholarshipInfoReports\Tables\ScholarshipInfoReportsTable;
use App\Models\ScholarshipInfoReport;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Filament resource for ScholarshipInfoReport ("Laporan info beasiswa") at /admin/scholarship-info-reports.
 *
 * Admins review mentee "Informasi Tidak Akurat?" reports: current vs suggested value, evidence link,
 * and accept/reject with an internal note. Scholarship data is still edited by hand on the scholarship.
 * Access is governed by the matching Shield policy in app/Policies.
 */
class ScholarshipInfoReportResource extends Resource
{
    protected static ?string $model = ScholarshipInfoReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static string|UnitEnum|null $navigationGroup = 'Beasiswa';

    protected static ?string $navigationLabel = 'Laporan info beasiswa';

    protected static ?string $modelLabel = 'laporan info beasiswa';

    protected static ?string $pluralModelLabel = 'Laporan info beasiswa';

    protected static ?int $navigationSort = 3;

    /**
     * Show how many reports still wait for a first look.
     *
     * @return string|null Count of new reports, or null to hide the badge.
     */
    public static function getNavigationBadge(): ?string
    {
        $count = ScholarshipInfoReport::query()->where('status', ReportStatus::New->value)->count();

        return $count > 0 ? (string) $count : null;
    }

    /**
     * Get the badge color of the navigation item.
     *
     * @return string Filament color name.
     */
    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    /**
     * Build the list table.
     *
     * @param  Table  $table  The table provided by Filament.
     * @return Table The configured table.
     */
    public static function table(Table $table): Table
    {
        return ScholarshipInfoReportsTable::configure($table);
    }

    /**
     * Get the pages (routes) of this resource.
     *
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListScholarshipInfoReports::route('/'),
        ];
    }
}
