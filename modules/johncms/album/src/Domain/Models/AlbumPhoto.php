<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Domain\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Johncms\Users\User;

/**
 * @mixin Builder
 *
 * @property int $id
 * @property int $user_id
 * @property int $album_id
 * @property string $description
 * @property string $img_name
 * @property string $tmb_name
 * @property int $time
 * @property bool $comments
 * @property int $comm_count
 * @property int $access
 * @property int $vote_plus
 * @property int $vote_minus
 * @property int $views
 * @property int $downloads
 * @property bool $unread_comments
 *
 * Computed properties
 * @property int $rating
 * @property Album $album
 * @property User $user
 * @property AlbumVote[] $votes
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class AlbumPhoto extends Model
{
    public $timestamps = false;

    protected $table = 'cms_album_files';

    protected $fillable = [
        'user_id',
        'album_id',
        'description',
        'img_name',
        'tmb_name',
        'time',
        'comments',
        'comm_count',
        'access',
        'vote_plus',
        'vote_minus',
        'views',
        'downloads',
        'unread_comments',
    ];

    protected $casts = [
        'user_id'         => 'integer',
        'album_id'        => 'integer',
        'time'            => 'integer',
        'comments'        => 'boolean',
        'comm_count'      => 'integer',
        'access'          => 'integer',
        'vote_plus'       => 'integer',
        'vote_minus'      => 'integer',
        'views'           => 'integer',
        'downloads'       => 'integer',
        'unread_comments' => 'boolean',
    ];

    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class, 'album_id', 'id');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(AlbumVote::class, 'file_id', 'id');
    }

    public function getRatingAttribute(): int
    {
        return $this->vote_plus - $this->vote_minus;
    }
}
