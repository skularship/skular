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
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

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

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Modul berstatus draf, belum terlihat oleh mentee';
    }

    public static function form(Schema $schema): Schema
    {
        return ModuleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ModulesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            QuickQuestionsRelationManager::class,
            PracticeFormsRelationManager::class,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'fase.title'];
    }

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
