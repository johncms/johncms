<?php

declare(strict_types=1);

namespace Johncms\Modules\Consent\Domain\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $context
 * @property string $language
 * @property string $title
 * @property string $text
 * @property string $version
 * @property bool $is_required
 * @property bool $is_active
 */
final class Consent extends Model
{
    protected $table = 'consents';

    protected $fillable = [
        'context',
        'language',
        'title',
        'text',
        'version',
        'is_required',
        'is_active',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active'   => 'boolean',
    ];
}
