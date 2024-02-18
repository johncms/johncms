<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Users;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Johncms\Database\Eloquent\Casts\SpecialChars;
use Johncms\Files\Models\File;
use Johncms\Users\Ban\UserBan;
use Johncms\Users\Ban\UserBanChecker;
use Johncms\Users\Casts\AdditionalFieldsCast;
use Johncms\Users\Casts\UserSettings;

/**
 * Class User
 *
 * @mixin Builder
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string|null $name
 * @property string|null $login
 * @property string|null $email
 * @property string|null $phone
 * @property string $password
 * @property bool $confirmed
 * @property bool $email_confirmed
 * @property string|null $confirmation_code
 * @property int|null $failed_login
 * @property int|null $gender
 * @property Carbon|null $birthday
 * @property UserConfig $settings
 * @property string|null $restore_password_code
 * @property Carbon|null $restore_password_date
 *
 * @property StoredAuth[] $storedAuth
 *
 * @property string $profile_url
 *
 * @method Builder online() - Выбрать пользователей онлайн
 */
class User extends Model
{
    protected $dateFormat = 'Y-m-d H:i:s';

    protected $table = 'users';

    protected $casts = [
        'confirmed'         => 'bool',
        'email_confirmed'   => 'bool',
        'name'              => SpecialChars::class,
        'login'             => SpecialChars::class,
        'email'             => SpecialChars::class,
        'phone'             => SpecialChars::class,
        'settings'          => UserSettings::class,
        'additional_fields' => AdditionalFieldsCast::class,
        'birthday'          => 'date',
    ];

    protected $fillable = [
        'id',
        'name',
        'login',
        'email',
        'phone',
        'password',
        'confirmed',
        'email_confirmed',
        'confirmation_code',
        'failed_login',
        'gender',
        'birthday',
        'settings',
        'additional_fields',
        'avatar_id',
        'restore_password_code',
        'restore_password_date',
    ];

    protected $attributes = [];

    protected ?UserRoleChecker $userRoleChecker = null;
    protected ?UserPermissionChecker $userPermissionChecker = null;
    protected ?UserBanChecker $userBanChecker = null;

    public function __construct(array $attributes = [])
    {
        $this->setUpModel();
        parent::__construct($attributes);
    }

    private function setUpModel(): void
    {
        $modelConfig = config('johncms.user_model', []);

        $casts = $modelConfig['casts'] ?? [];
        $fillable = $modelConfig['fillable'] ?? [];
        $attributes = $modelConfig['attributes'] ?? [];

        $this->casts = array_merge($this->casts, $casts);
        $this->fillable = array_merge($this->fillable, $fillable);
        $this->attributes = array_merge($this->attributes, $attributes);
    }

    /**
     * Only approved users
     */
    public function scopeApproved(Builder $query): Builder
    {
        $query->where('confirmed', '=', 1);
        if (config('registration.email_confirmation', false)) {
            $query->where('email_confirmed', '=', 1);
        }

        return $query;
    }

    /**
     * Only approved users
     */
    public function scopeUnconfirmed(Builder $query): Builder
    {
        $query->whereNull('confirmed');
        if (config('registration.email_confirmation', false)) {
            $query->orWhereNull('email_confirmed');
        }

        return $query;
    }

    public function getRoleChecker(): UserRoleChecker
    {
        if ($this->userRoleChecker === null) {
            $this->userRoleChecker = new UserRoleChecker($this);
        }
        return $this->userRoleChecker;
    }

    public function getPermissionChecker(): UserPermissionChecker
    {
        if ($this->userPermissionChecker === null) {
            $this->userPermissionChecker = new UserPermissionChecker($this);
        }
        return $this->userPermissionChecker;
    }

    public function getUserBanChecker(): UserBanChecker
    {
        if ($this->userBanChecker === null) {
            $this->userBanChecker = new UserBanChecker($this);
        }
        return $this->userBanChecker;
    }

    public function hasBan(array | string $bans): bool
    {
        return $this->getUserBanChecker()->hasBan($bans);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function bans(): HasMany
    {
        return $this->hasMany(UserBan::class, 'user_id', 'id');
    }

    public function hasRole(array | string $roles): bool
    {
        return $this->getRoleChecker()->hasRole($roles);
    }

    public function hasAnyRole(): bool
    {
        return $this->getRoleChecker()->hasAnyRole();
    }

    public function isAdmin(): bool
    {
        return $this->getRoleChecker()->hasRole('admin');
    }

    public function hasPermission(array | string $permissions): bool
    {
        return $this->getPermissionChecker()->hasPermission($permissions);
    }

    public function storedAuth(): HasMany
    {
        return $this->hasMany(StoredAuth::class, 'user_id', 'id');
    }

    /** @deprecated use role_names attribute */
    public function getRoleNames(): string
    {
        $roles = (array) $this->getRoleChecker()->getUserRoles();
        if (empty($roles)) {
            return d__('system', 'User');
        }
        return implode(', ', array_column($roles, 'display_name'));
    }

    public function getRoleNamesAttribute(): string
    {
        $roles = (array) $this->getRoleChecker()->getUserRoles();
        if (empty($roles)) {
            return d__('system', 'User');
        }
        return implode(', ', array_column($roles, 'display_name'));
    }

    public function displayName(): string
    {
        if ($this->name) {
            return $this->name;
        } elseif ($this->login) {
            return $this->login;
        } elseif ($this->email) {
            return $this->email;
        } elseif ($this->phone) {
            return $this->phone;
        }
        return '';
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->name) {
            return $this->name;
        } elseif ($this->login) {
            return $this->login;
        } elseif ($this->email) {
            return $this->email;
        } elseif ($this->phone) {
            return $this->phone;
        }
        return '';
    }

    /** @deprecated use is_online property */
    public function isOnline(): bool
    {
        return $this->activity?->last_visit && Carbon::now()->subMinutes(5)->lessThan($this->activity?->last_visit);
    }

    public function getIsOnlineAttribute(): bool
    {
        return $this->activity?->last_visit && Carbon::now()->subMinutes(5)->lessThan($this->activity?->last_visit);
    }

    public function getLastSeen(): string
    {
        if ($this->is_online) {
            return d__('system', 'Online');
        }
        return format_date($this->last_visit);
    }

    public function getAge(): ?int
    {
        if ($this->birthday) {
            return Carbon::now()->diffInYears($this->birthday);
        }
        return null;
    }

    public function getGenderName(): string
    {
        return match ($this->gender) {
            1 => d__('system', 'Male'),
            2 => d__('system', 'Female'),
            default => d__('system', 'Not specified'),
        };
    }

    public function avatar(): HasOne
    {
        return $this->hasOne(File::class, 'id', 'avatar_id');
    }

    public function activity(): HasOne
    {
        return $this->hasOne(UserActivity::class, 'user_id', 'id');
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar?->url;
    }

    public function getProfileUrlAttribute(): ?string
    {
        return route('personal.profile', ['id' => $this->id]);
    }

    public function scopeOnline(Builder $query): Builder
    {
        return $query->whereHas('activity', fn(Builder $builder) => $builder->where('last_visit', '>=', Carbon::now()->subMinutes(5)));
    }

    public function updateActivity(array $fields = []): UserActivity
    {
        return UserActivity::query()->updateOrCreate(['user_id' => $this->id], $fields);
    }
}
