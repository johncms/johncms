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
 * @property Carbon|null $last_visit
 * @property UserConfig $settings
 *
 * @property StoredAuth[] $storedAuth
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
        'last_visit',
        'settings',
        'additional_fields',
        'avatar_id',
    ];

    protected $attributes = [];

    protected $dates = [
        'birthday',
        'last_visit',
    ];

    protected UserRoleChecker $userRoleChecker;
    protected UserPermissionChecker $userPermissionChecker;

    public function __construct(array $attributes = [])
    {
        $this->setUpModel();
        parent::__construct($attributes);
        $this->userRoleChecker = new UserRoleChecker($this);
        $this->userPermissionChecker = new UserPermissionChecker($this);
    }

    private function setUpModel(): void
    {
        $modelConfig = config('johncms.user_model', []);

        $casts = $modelConfig['casts'] ?? [];
        $fillable = $modelConfig['fillable'] ?? [];
        $attributes = $modelConfig['attributes'] ?? [];
        $dates = $modelConfig['dates'] ?? [];

        $this->casts = array_merge($this->casts, $casts);
        $this->fillable = array_merge($this->fillable, $fillable);
        $this->attributes = array_merge($this->attributes, $attributes);
        $this->dates = array_merge($this->dates, $dates);
    }

    /**
     * Only approved users
     *
     * @return $this
     */
    public function approved(): self
    {
        $this->where('confirmed', '=', 1);
        $config = di('config')['johncms'];
        if (! empty($config['user_email_confirmation'])) {
            $this->where('email_confirmed', '=', 1);
        }

        return $this;
    }

    public function getRoleChecker(): UserRoleChecker
    {
        return $this->userRoleChecker;
    }

    public function getPermissionChecker(): UserPermissionChecker
    {
        return $this->userPermissionChecker;
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function hasRole(array|string $roles): bool
    {
        return $this->userRoleChecker->hasRole($roles);
    }

    public function hasAnyRole(): bool
    {
        return $this->userRoleChecker->hasAnyRole();
    }

    public function isAdmin(): bool
    {
        return $this->userRoleChecker->hasRole('admin');
    }

    public function hasPermission(array|string $permissions): bool
    {
        return $this->userPermissionChecker->hasPermission($permissions);
    }

    public function storedAuth(): HasMany
    {
        return $this->hasMany(StoredAuth::class, 'user_id', 'id');
    }

    public function getRoleNames(): string
    {
        $roles = (array) $this->userRoleChecker->getUserRoles();
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

    public function isOnline(): bool
    {
        return $this->last_visit && Carbon::now()->subMinutes(5)->lessThan($this->last_visit);
    }

    public function getLastSeen(): string
    {
        if ($this->isOnline()) {
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
}
