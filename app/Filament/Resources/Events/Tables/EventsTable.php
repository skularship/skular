<?php

namespace App\Filament\Resources\Events\Tables;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Event;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * List table of the Event resource: date (WIB), type, registered/capacity,
 * status, and "Duplikat event" for recurring events.
 */
class EventsTable
{
    /**
     * Add the seat count (registered/capacity column) to the list query.
     *
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    private static function withSeatCount(Builder $query): Builder
    {
        return $query->withSeatCount();
    }

    /**
     * Add columns, filters and actions to the table.
     *
     * @param  Table  $table  The table to configure.
     * @return Table The configured table.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(self::withSeatCount(...))
            ->defaultSort('starts_at', 'desc')
            ->columns([
                TextColumn::make('starts_at')
                    ->label('Tanggal (WIB)')
                    ->dateTime('j M Y H:i', Event::DISPLAY_TIMEZONE)
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('type')
                    ->label('Jenis')
                    ->formatStateUsing(fn (EventType $state): string => $state->getLabel())
                    ->badge(),
                TextColumn::make('seated_registrations_count')
                    ->label('Terdaftar / kapasitas')
                    ->formatStateUsing(fn (int $state, Event $record): string => $state.' / '.($record->capacity ?? '∞')),
                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (EventStatus $state): string => $state->getLabel())
                    ->badge()
                    ->color(fn (EventStatus $state): string => match ($state) {
                        EventStatus::Published => 'success',
                        EventStatus::Draft => 'gray',
                        EventStatus::Cancelled => 'danger',
                    }),
            ])
            ->filters([
                SelectFilter::make('type')->label('Jenis')->options(EventType::options()),
                SelectFilter::make('status')->label('Status')->options(EventStatus::options()),
            ])
            ->recordActions([
                EditAction::make(),
                ReplicateAction::make()
                    ->label('Duplikat event')
                    ->excludeAttributes(['seated_registrations_count'])
                    ->mutateRecordDataUsing(function (array $data): array {
                        $data['title'] = $data['title'].' (salinan)';
                        $data['slug'] = Str::slug((string) $data['title']).'-'.Str::lower(Str::random(4));
                        $data['status'] = EventStatus::Draft->value;
                        $data['recording_url'] = null;

                        return $data;
                    })
                    ->beforeReplicaSaved(function (Event $replica): void {
                        $replica->slug = Str::slug($replica->title).'-'.Str::lower(Str::random(4));
                        $replica->status = EventStatus::Draft;
                        $replica->recording_url = null;
                    })
                    ->successNotificationTitle('Event diduplikat sebagai draf. Ubah tanggalnya sebelum diterbitkan.'),
                DeleteAction::make()
                    ->hidden(fn (Event $record): bool => $record->registrations()->exists()),
            ])
            ->emptyStateHeading('Belum ada event')
            ->emptyStateDescription('Buat webinar, kelas intensif, atau mentoring. Mentee mendaftar langsung di website.')
            ->emptyStateIcon('heroicon-o-video-camera');
    }
}
