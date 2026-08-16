<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Events;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Johncms\Auth\Schema\AuthSchema;

/**
 * One entry of the audit trail: what happened to an account, who did it and from where.
 *
 * Append-only by intent — nothing in the application updates a row. An entry is evidence, and
 * evidence that can be edited afterwards is worth little.
 *
 * @mixin Builder
 * @property int                        $id
 * @property int|null                   $user_id
 * @property int|null                   $actor_id
 * @property string                     $event
 * @property string                     $ip
 * @property string                     $user_agent
 * @property array<string, mixed>|null  $context
 * @property int                        $created_at
 */
class AuthEvent extends Model
{
    protected $table = AuthSchema::AUTH_EVENTS;

    public $timestamps = false;

    protected $casts = [
        'context' => 'array',
    ];

    protected $fillable = [
        'user_id',
        'actor_id',
        'event',
        'ip',
        'user_agent',
        'context',
        'created_at',
    ];

    /**
     * The event as the enum, or null when the string came from a module the core knows nothing
     * about. Reports must survive that: an unknown key is shown as it is, not turned into an error.
     */
    public function type(): ?AuthEventType
    {
        return AuthEventType::tryFrom($this->event);
    }
}
