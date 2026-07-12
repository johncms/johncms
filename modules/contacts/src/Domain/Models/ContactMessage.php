<?php

declare(strict_types=1);

namespace Johncms\Modules\Contacts\Domain\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Johncms\Modules\Contacts\Domain\Enums\ContactMessageStatus;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $name
 * @property string $email
 * @property string $message
 * @property ContactMessageStatus $status
 * @property string $ip_address
 * @property string|null $user_agent
 * @property Carbon|null $processed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class ContactMessage extends Model
{
    protected $table = 'contact_messages';

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'message',
        'status',
        'ip_address',
        'user_agent',
        'processed_at',
    ];

    protected $casts = [
        'status'       => ContactMessageStatus::class,
        'processed_at' => 'datetime',
    ];
}
