<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Modules\Forum\Domain\Models;

use Johncms\Smilies\SmiliesRendererInterface;
use Johncms\Users\User;
use Johncms\Utils\DateFormatterInterface;

/**
 * Trait MessageMutators
 *
 * @package Forum\Models
 *
 * @property User $current_user
 * @property User $user_data
 * @property DateFormatterInterface $dateFormatter
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
        return '/forum/post/' . $this->id . '/';
    }

    /**
     * Link to the post editing page.
     *
     * @return string
     */
    public function getEditUrlAttribute(): string
    {
        return '/forum/edit-post/' . $this->id . '/';
    }

    /**
     * Link to the post deleting page.
     *
     * @return string
     */
    public function getDeleteUrlAttribute(): string
    {
        return '/forum/delete-post/' . $this->id . '/';
    }

    /**
     * Link to the post restoring page.
     *
     * @return string
     */
    public function getRestoreUrlAttribute(): string
    {
        if ($this->current_user->rights >= 7 && $this->deleted) {
            return '/forum/restore-post/' . $this->id . '/';
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
        return $this->dateFormatter->format($this->date);
    }

    /**
     * Formatted text
     *
     * @return string
     */
    public function getPostTextAttribute(): string
    {
        $text = $this->purifier->purify($this->text);
        $text = $this->media->embedMedia($text);
        $text = di(SmiliesRendererInterface::class)->render($text, (bool) $this->rights);
        return $text;
    }

    /**
     * Preview message
     *
     * @return string
     */
    public function getPostPreviewAttribute(): string
    {
        $plainText = trim(strip_tags($this->text));
        if (mb_strlen($plainText) <= 500) {
            return '';
        }
        return mb_substr($plainText, 0, 500) . '...';
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
