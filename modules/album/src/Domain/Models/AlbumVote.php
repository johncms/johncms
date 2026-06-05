<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Domain\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Builder
 *
 * @property int $id
 * @property int $user_id
 * @property int $file_id
 * @property int $vote
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class AlbumVote extends Model
{
    public $timestamps = false;

    protected $table = 'cms_album_votes';

    protected $fillable = [
        'user_id',
        'file_id',
        'vote',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'file_id' => 'integer',
        'vote'    => 'integer',
    ];
}
