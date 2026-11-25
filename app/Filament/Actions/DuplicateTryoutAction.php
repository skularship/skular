<?php

namespace App\Filament\Actions;

use App\Enums\ContentStatus;
use App\Models\Tryout;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Copies a Try Out as a draft with the same subtests and unit pools (the
 * units and questions are shared, not copied). Attempts are never copied.
 */
class DuplicateTryoutAction extends Action
{
    /**
     * Build the "Duplikat" record action.
     *
     * @param  string|null  $name  Action name.
     * @return static The configured action.
     */
    public static function make(?string $name = 'duplicateTryout'): static
    {
        return parent::make($name)
            ->label('Duplikat')
            ->icon('heroicon-o-document-duplicate')
            ->color('gray')
            ->requiresConfirmation()
            ->authorize('create')
            ->action(function (Tryout $record): void {
                $copy = DB::transaction(function () use ($record): Tryout {
                    $suffix = Str::lower(Str::random(4));
                    $copy = $record->replicate(['published_at']);
                    $copy->title = $record->title.' (salinan)';
                    $copy->slug = Str::slug($record->slug.'-'.$suffix);
                    $copy->code = Str::limit($record->code, 26, '').'-'.Str::upper($suffix);
                    $copy->status = ContentStatus::Draft;
                    $copy->theory_pdf_path = null;
                    $copy->save();

                    foreach ($record->sections()->get() as $section) {
                        $new_section = $section->replicate();
                        $new_section->tryout_id = $copy->id;
                        $new_section->save();

                        $pool = DB::table('tryout_section_unit')->where('tryout_section_id', $section->id)->get(['tryout_unit_id', 'ordering']);
                        $sync = [];

                        foreach ($pool as $row) {
                            $sync[(int) $row->tryout_unit_id] = ['ordering' => (int) $row->ordering];
                        }

                        $new_section->units()->sync($sync);
                    }

                    return $copy;
                });

                Notification::make()->title('Try Out diduplikat sebagai draf')->body($copy->title)->success()->send();
            });
    }
}
