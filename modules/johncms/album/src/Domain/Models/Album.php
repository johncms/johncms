<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Domain\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Johncms\Users\User;

/**
 * @mixin Builder
 *
 * @property int $id
 * @property int $user_id
 * @property int $sort
 * @property string $name
 * @property string $description
 * @property string|null $password
 * @property int|null $access
 *
 * Computed properties
 * @property User $user
 * @property AlbumPhoto[] $photos
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class Album extends Model
{
    public $timestamps = false;

    protected $table = 'cms_album_cat';

    protected $fillable = [
        'user_id',
        'sort',
        'name',
        'description',
        'password',
        'access',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'sort'    => 'integer',
        'access'  => 'integer',
    ];

    public function photos(): HasMany
    {
        return $this->hasMany(AlbumPhoto::class, 'album_id', 'id');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
