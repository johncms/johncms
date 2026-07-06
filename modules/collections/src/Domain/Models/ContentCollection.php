<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Johncms\Casts\FormattedDate;

/**
 * A content collection: Blog, News, Catalog, ...
 *
 * @mixin Builder
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property array|null $settings
 * @property int $sort
 * @property bool $active
 * @property string $created_at
 * @property string $updated_at
 *
 * @property ContentCollectionField[] $fields
 * @property ContentCollectionSection[] $sections
 * @property ContentCollectionItem[] $items
 */
final class ContentCollection extends Model
{
    protected $table = 'collections';

    protected $fillable = [
        'code',
        'name',
        'description',
        'settings',
        'sort',
        'active',
    ];

    protected $casts = [
        'settings'   => 'array',
        'sort'       => 'integer',
        'active'     => 'boolean',
        'created_at' => FormattedDate::class,
        'updated_at' => FormattedDate::class,
    ];

    public function fields(): HasMany
    {
        return $this->hasMany(ContentCollectionField::class, 'collection_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(ContentCollectionSection::class, 'collection_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ContentCollectionItem::class, 'collection_id');
    }
}
