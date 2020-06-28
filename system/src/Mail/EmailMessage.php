<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Mail;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Johncms\Casts\DateHuman;

/**
 * Class EmailMessage
 *
 * @mixin Builder
 * @property int $id
 * @property int $priority - Priority of sending the message
 * @property string $locale - The language used for displaying the message.
 * @property string $template - Template path
 * @property array $fields - Fields
 * @property array $sent_at - Date the message was sent.
 *
 * @property array $created_at - Creation date
 * @property array $updated_at - Date of change
 *
 * @method Builder unsent() - Select only unsent messages.
 */
class EmailMessage extends Model
{
    protected $casts = [
        'fields'     => 'array',
        'sent_at'    => DateHuman::class,
        'created_at' => DateHuman::class,
        'updated_at' => DateHuman::class,
    ];

    protected $fillable = [
        'priority',
        'locale',
        'template',
        'fields',
        'sent_at',
    ];

    protected $attributes = [
        'priority' => 100,
    ];

    /**
     * Select only unsent messages.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeUnsent(Builder $query): Builder
    {
        return $query->whereNull('sent_at');
    }
}
