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
 * Class MailMessage
 *
 * @property int $id
 * @property int $user_id - Recipient ID
 * @property int $from_id - Sender ID
 * @property string $text
 * @property int $time
 * @property bool $read
 * @property bool $sys
 * @property int $delete - 0=not deleted, 1=sender deleted, 2=recipient deleted
 * @property string $file_name
 * @property int $count
 * @property int $size
 * @property string $them - Subject
 * @property bool $spam
 *
 * @property User $recipient
 * @property User $sender
 */
class MailMessage extends Model
{
    protected $table = 'cms_mail';

    public $timestamps = false;

    protected $casts = [
        'read' => 'bool',
        'sys' => 'bool',
        'spam' => 'bool',
        'time' => 'int',
        'delete' => 'int',
        'count' => 'int',
        'size' => 'int',
    ];

    protected $fillable = [
        'user_id',
        'from_id',
        'text',
        'time',
        'read',
        'sys',
        'delete',
        'file_name',
        'count',
        'size',
        'them',
        'spam',
    ];

    /**
     * Relationship to the recipient user.
     */
    public function recipient(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    /**
     * Relationship to the sender user.
     */
    public function sender(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'from_id');
    }
}
