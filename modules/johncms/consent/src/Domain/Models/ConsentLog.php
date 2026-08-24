<?php

declare(strict_types=1);

namespace Johncms\Modules\Consent\Domain\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|null $user_id
 * @property int $consent_id
 * @property string $version
 * @property string $ip_address
 * @property \Illuminate\Support\Carbon $accepted_at
 * @property-read Consent|null $consent
 */
final class ConsentLog extends Model
{
    protected $table = 'consent_log';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'consent_id',
        'version',
        'ip_address',
        'accepted_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    public function consent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Consent::class, 'consent_id');
    }
}
