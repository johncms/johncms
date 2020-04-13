<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Forum\Models;

use Carbon\Carbon;
use Johncms\System\i18n\Translator;
use Johncms\System\Legacy\Tools;
use Johncms\Users\User;

/**
 * Trait MessageMutators
 *
 * @package Forum\Models
 *
 * @property User $current_user
 * @property User $user_data
 * @property Tools $tools
 */
trait MessageMutators
{
    private $user_model;

    /**
     * Link to the page of the post.
     *
     * @return string
     */
    public function getUrlAttribute(): string
    {
        return '/forum/?act=show_post&amp;id=' . $this->id;
    }

    /**
     * Link to the post editing page.
     *
     * @return string
     */
    public function getEditUrlAttribute(): string
    {
        return '/forum/?act=editpost&amp;id=' . $this->id;
    }

    /**
     * Link to the post deleting page.
     *
     * @return string
     */
    public function getDeleteUrlAttribute(): string
    {
        return '/forum/?act=editpost&amp;do=del&amp;id=' . $this->id;
    }

    /**
     * Link to the post restoring page.
     *
     * @return string
     */
    public function getRestoreUrlAttribute(): string
    {
        if ($this->current_user->rights >= 7 && $this->deleted) {
            return '/forum/?act=editpost&amp;do=restore&amp;id=' . $this->id;
        }
        return '';
    }

    /**
     * Formatted date
     *
     * @return string
     */
    public function getPostTimeAttribute(): string
    {
        return $this->tools->displayDate($this->date);
    }

    /**
     * Formatted text
     *
     * @return string
     */
    public function getPostTextAttribute(): string
    {
        $text = $this->tools->checkout($this->text, 1, 1);
        $text = $this->tools->smilies($text, $this->rights ? 1 : 0);
        return $text;
    }

    /**
     * Preview message
     *
     * @return string
     */
    public function getPostPreviewAttribute(): string
    {
        $post_preview = '';
        if (mb_strlen($this->text) > 500) {
            $post_preview = $this->tools->checkout(mb_substr($this->text, 0, 500), 0, 2);
            $post_preview .= '...';
        }
        return $post_preview;
    }

    /**
     * User profile url
     *
     * @return string
     */
    public function getUserProfileLinkAttribute(): string
    {
        if ($this->current_user->is_valid && $this->user_id !== $this->current_user->id) {
            return $this->user_data->profile_url;
        }
        return '';
    }

    /**
     * User attributes
     *
     * @return User
     */
    public function getUserDataAttribute(): User
    {
        $this->loadUserModel();
        return $this->user_model;
    }

    /**
     * Load the User model
     */
    private function loadUserModel(): void
    {
        if (! is_object($this->user_model)) {
            $this->user_model = new User(
                [
                    'id'           => $this->user_id,
                    'rights'       => $this->rights,
                    'lastdate'     => $this->lastdate,
                    'status'       => $this->status,
                    'datereg'      => $this->datereg,
                    'ip'           => $this->ip,
                    'ip_via_proxy' => $this->ip_via_proxy,
                    'browser'      => $this->user_agent,
                    'name'         => $this->user_name,
                ]
            );
        }
    }
}
