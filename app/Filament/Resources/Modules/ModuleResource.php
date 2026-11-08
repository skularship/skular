<?php

namespace App\Filament\Resources\Modules;

use App\Enums\ContentStatus;
use App\Filament\Resources\Modules\Pages\CreateModule;
use App\Filament\Resources\Modules\Pages\EditModule;
use App\Filament\Resources\Modules\Pages\ListModules;
use App\Filament\Resources\Modules\Pages\PreviewModule;
use App\Filament\Resources\Modules\RelationManagers\PracticeFormsRelationManager;
use App\Filament\Resources\Modules\RelationManagers\QuickQuestionsRelationManager;
use App\Filament\Resources\Modules\Schemas\ModuleForm;
use App\Filament\Resources\Modules\Tables\ModulesTable;
use App\Models\Module;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Filament resource for Module ("Modul pembelajaran") at /admin/modules.
 *
 * Curriculum editor: material (Pengantar, Inti Materi columns, Pahami), brainstorming questions, AI Mentor suggestions, AI rubric, practice forms and quick questions. Modules start as draft and are published from the edit page.
 * Access is governed by the matching Shield policy in app/Policies.
 */
class ModuleResource extends Resource
{
    protected static ?string $model = Module::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Kurikulum';

    protected static ?string $navigationLabel = 'Modul pembelajaran';

    protected static ?string $modelLabel = 'modul';

    protected static ?string $pluralModelLabel = 'Modul pembelajaran';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    /**
     * Surface the drafts still waiting for the curriculum team.
     */
    public static function getNavigationBadge(): ?string
    {
        $drafts = Module::query()
            ->where('content_status', ContentStatus::Draft->value)
            ->count();

        return $drafts === 0 ? null : (string) $drafts;
    }

    /**
     * Get the navigation badge color.
     *
     * @return string|null Filament color name.
     */
    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    /**
     * Get the navigation badge tooltip.
     *
     * @return string|null Tooltip text.
     */
    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Modul berstatus draf, belum terlihat oleh mentee';
    }

    /**
     * Build the create/edit form.
     *
     * @param  Schema  $schema  The schema provided by Filament.
     * @return Schema The configured form schema.
     */
    public static function form(Schema $schema): Schema
    {
        return ModuleForm::configure($schema);
    }

    /**
     * Build the list table.
     *
     * @param  Table  $table  The table provided by Filament.
     * @return Table The configured table.
     */
    public static function table(Table $table): Table
    {
        return ModulesTable::configure($table);
    }

    /**
     * Get the relation managers shown on the edit page.
     *
     * @return array<int, class-string>
     */
    public static function getRelations(): array
    {
        return [
            QuickQuestionsRelationManager::class,
            PracticeFormsRelationManager::class,
        ];
    }

    /**
     * Get the attributes searched by the admin global search.
     *
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'fase.title'];
    }

    /**
     * Get the pages (routes) of this resource.
     *
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListModules::route('/'),
            'create' => CreateModule::route('/create'),
            'edit' => EditModule::route('/{record}/edit'),
            'preview' => PreviewModule::route('/{record}/preview'),
        ];
    }
}
