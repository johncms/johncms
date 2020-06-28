<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Guestbook\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Johncms\Casts\Ip;
use Johncms\Casts\SpecialChars;
use Johncms\Casts\TimeToDate;
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
 *
 * @property User $user
 * @property string $post_text
 * @property string $reply_text
 * @property bool $is_online
 */
class Guestbook extends Model
{
    protected $table = 'guest';

    public $timestamps = false;

    protected $casts = [
        'adm'       => 'boolean',
        'time'      => TimeToDate::class,
        'otime'     => TimeToDate::class,
        'edit_time' => TimeToDate::class,
        'ip'        => Ip::class,
        'name'      => SpecialChars::class,
        'edit_who'  => SpecialChars::class,
        'browser'   => SpecialChars::class,
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
    ];

    /** @var Tools */
    protected $tools;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->tools = di(Tools::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function getPostTextAttribute(): string
    {
        if ($this->user_id) {
            $post = $this->tools->checkout($this->text, 1, 1);
            $post = $this->tools->smilies($post, ($this->user !== null && $this->user->rights >= 1 ? 1 : 0));
        } else {
            $post = $this->tools->checkout($this->text, 0, 2);
            $post = preg_replace(
                '~\\[url=(https?://.+?)\\](.+?)\\[/url\\]|(https?://(www.)?[0-9a-z\.-]+\.[0-9a-z]{2,6}[0-9a-zA-Z/\?\.\~&amp;_=/%-:#]*)~',
                '###',
                $post
            );
            $replace = [
                '.ru'   => '***',
                '.com'  => '***',
                '.biz'  => '***',
                '.cn'   => '***',
                '.in'   => '***',
                '.net'  => '***',
                '.org'  => '***',
                '.info' => '***',
                '.mobi' => '***',
                '.wen'  => '***',
                '.kmx'  => '***',
                '.h2m'  => '***',
            ];

            $post = strtr($post, $replace);
        }

        return $post;
    }

    public function getReplyTextAttribute(): string
    {
        if ($this->user_id) {
            $post = $this->tools->checkout($this->otvet, 1, 1);
            $post = $this->tools->smilies($post, 1);
        }

        return $post ?? '';
    }

    public function getIsOnlineAttribute(): bool
    {
        if ($this->user_id && $this->user !== null) {
            return $this->user->is_online;
        }
        return false;
    }
}
