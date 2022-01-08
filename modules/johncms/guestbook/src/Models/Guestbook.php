<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Guestbook\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Johncms\Database\Eloquent\Casts\Ip;
use Johncms\Database\Eloquent\Casts\SpecialChars;
use Johncms\Database\Eloquent\Casts\TimeToDate;
use Johncms\Media\MediaEmbed;
use Johncms\Security\HTMLPurifier;
use Johncms\System\Legacy\Tools;
use Johncms\Users\User;

/**
 * Class Guestbook
 *
 * @package Guestbook\Models
 *
 * @mixin Builder
 * @property int $id
 * @property bool $adm
 * @property int $time
 * @property int $user_id
 * @property string $name
 * @property string $text
 * @property int $ip
 * @property string $browser
 * @property string $admin
 * @property string $otvet
 * @property int $otime
 * @property string $edit_who
 * @property int $edit_time
 * @property int $edit_count
 * @property array $attached_files
 *
 * @property User|null $user
 * @property string $post_text
 * @property string $reply_text
 * @property bool $is_online
 */
class Guestbook extends Model
{
    protected $table = 'guest';

    public $timestamps = false;

    protected $casts = [
        'adm'            => 'boolean',
        'time'           => TimeToDate::class,
        'otime'          => TimeToDate::class,
        'edit_time'      => TimeToDate::class,
        'ip'             => Ip::class,
        'name'           => SpecialChars::class,
        'edit_who'       => SpecialChars::class,
        'browser'        => SpecialChars::class,
        'attached_files' => 'array',
    ];

    protected $fillable = [
        'adm',
        'time',
        'user_id',
        'name',
        'text',
        'ip',
        'browser',
        'admin',
        'otvet',
        'otime',
        'edit_who',
        'edit_time',
        'edit_count',
        'attached_files',
    ];

    /** @var Tools */
    protected $tools;

    /** @var HTMLPurifier|mixed */
    protected $purifier;

    /** @var \Johncms\Media\MediaEmbed|mixed */
    protected $media;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->tools = di(Tools::class);
        $this->purifier = di(HTMLPurifier::class);
        $this->media = di(MediaEmbed::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function getPostTextAttribute(): string
    {
        $post = $this->purifier->purify($this->text);
        $post = $this->media->embedMedia($post);
        return $this->tools->smilies($post, ($this->user?->hasAnyRole()));
    }

    public function getReplyTextAttribute(): string
    {
        $post = $this->purifier->purify($this->otvet);
        $post = $this->media->embedMedia($post);
        return $this->tools->smilies($post, true);
    }

    public function getIsOnlineAttribute(): bool
    {
        if ($this->user_id && $this->user !== null) {
            return $this->user->isOnline();
        }
        return false;
    }
}
