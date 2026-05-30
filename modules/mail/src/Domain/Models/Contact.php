<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Modules\Mail\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Johncms\Users\User;

/**
 * Class Contact
 *
 * @property int $id
 * @property int $user_id - Owner ID
 * @property int $from_id - Contact user ID
 * @property int $time
 * @property int $type
 * @property bool $friends
 * @property bool $ban - User is blocked
 * @property bool $man
 *
 * @property User $owner
 * @property User $contactUser
 */
class Contact extends Model
{
    protected $table = 'cms_contact';

    public $timestamps = false;

    protected $casts = [
        'friends' => 'bool',
        'ban' => 'bool',
        'man' => 'bool',
        'time' => 'int',
        'type' => 'int',
    ];

    protected $fillable = [
        'user_id',
        'from_id',
        'time',
        'type',
        'friends',
        'ban',
        'man',
    ];

    /**
     * Relationship to the owner user.
     */
    public function owner(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    /**
     * Relationship to the contact user.
     */
    public function contactUser(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'from_id');
    }
}
