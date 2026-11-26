<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\AdminRole;
use App\Enums\EducationLevel;
use App\Enums\LocationScope;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

/**
 * Create/edit form schema of the Pengguna resource.
 */
class UserForm
{
    /**
     * Add the form components to the schema.
     *
     * @param  Schema  $schema  The schema to configure.
     * @return Schema The configured schema.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->maxLength(255),
                // Only roles listed in AdminRole open the panel (User::canAccessPanel()),
                // so other roles (e.g. made on the Shield role page) are not offered:
                // they would create an "admin" account that cannot sign in.
                Select::make('roles')
                    ->label('Role admin')
                    ->relationship(
                        'roles',
                        'name',
                        fn (Builder $query): Builder => $query->whereIn('name', AdminRole::values()),
                    )
                    ->getOptionLabelFromRecordUsing(fn (Role $record): string => AdminRole::tryFrom($record->name)?->getLabel() ?? $record->name)
                    ->helperText('Hanya role yang terdaftar di AdminRole yang bisa masuk panel admin.')
                    ->nestedRecursiveRules([
                        Rule::exists('roles', 'id')->whereIn('name', AdminRole::values()),
                    ])
                    ->validationMessages([
                        'exists' => 'Role ini tidak bisa masuk panel admin.',
                    ])
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->visible(fn (): bool => self::canManageAdminRoles()),
                DateTimePicker::make('two_factor_confirmed_at'),
                TextInput::make('google_id'),
                TextInput::make('avatar_url')
                    ->url(),
                Select::make('level')
                    ->options(EducationLevel::class),
                TextInput::make('age')
                    ->numeric(),
                TextInput::make('gpa')
                    ->numeric(),
                Toggle::make('has_sktm'),
                Select::make('desired_level')
                    ->options(EducationLevel::class),
                Select::make('desired_location')
                    ->options(LocationScope::class),
                Select::make('cluster_id')
                    ->relationship('cluster', 'name'),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('subscription'),
                DateTimePicker::make('profile_completed_at'),
                TagsInput::make('tags')
                    ->label('Tag mentee')
                    ->placeholder('Contoh: alumni_akademi_unggulan')
                    ->helperText('Dipakai untuk event khusus undangan dan untuk memisahkan data masukan. Huruf kecil dan garis bawah.')
                    ->nestedRecursiveRules(['alpha_dash', 'max:64'])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Only Super Admins may assign or change another user's admin role.
     */
    private static function canManageAdminRoles(): bool
    {
        $auth_user = Auth::user();

        return $auth_user instanceof User
            && $auth_user->hasRole(AdminRole::SuperAdmin->value);
    }
}
