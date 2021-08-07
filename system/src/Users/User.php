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
use Johncms\Users\Casts\UserSettings;

/**
 * Class User
 *
 * @mixin Builder
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string|null $login
 * @property string|null $email
 * @property string|null $phone
 * @property string $password
 * @property bool $confirmed
 * @property bool $email_confirmed
 * @property int|null $failed_login
 * @property int|null $gender
 * @property Carbon|null $birthday
 * @property Carbon|null $last_visit
 * @property UserConfig $settings
 *
 * @method Builder online() - Выбрать пользователей онлайн
 */
class User extends Model
{
    protected $dateFormat = 'Y-m-d H:i:s';

    protected $table = 'users';

    protected $casts = [
        'confirmed'       => 'bool',
        'email_confirmed' => 'bool',
        'settings'        => UserSettings::class,
    ];

    protected $fillable = [
        'id',
        'login',
        'email',
        'phone',
        'password',
        'confirmed',
        'email_confirmed',
        'failed_login',
        'gender',
        'birthday',
        'last_visit',
        'settings',
    ];

    protected $attributes = [];

    protected $dates = [
        'birthday',
        'last_visit',
    ];

    protected UserRoleChecker $userRoleChecker;

    public function __construct(array $attributes = [])
    {
        $this->setUpModel();
        parent::__construct($attributes);
        $this->userRoleChecker = new UserRoleChecker($this);
    }

    private function setUpModel(): void
    {
        $model_config = config('johncms.user_model', []);

        $casts = $model_config['casts'] ?? [];
        $fillable = $model_config['fillable'] ?? [];
        $attributes = $model_config['attributes'] ?? [];
        $dates = $model_config['dates'] ?? [];

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

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function hasRole(array|string $roles): bool
    {
        return $this->userRoleChecker->hasRole($roles);
    }
}
