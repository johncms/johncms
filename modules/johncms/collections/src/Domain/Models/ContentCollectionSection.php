<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Johncms\Casts\FormattedDate;

/**
 * A hierarchical section inside a collection. `parent` is NULL for root sections.
 *
 * @mixin Builder
 *
 * @property int $id
 * @property int $collection_id
 * @property int|null $parent
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property bool $active
 * @property int $sort
 * @property string $created_at
 * @property string $updated_at
 *
 * @property ContentCollection $collection
 * @property ContentCollectionSection|null $parentSection
 * @property ContentCollectionSection[] $childSections
 */
final class ContentCollectionSection extends Model
{
    protected $table = 'collection_sections';

    protected $fillable = [
        'collection_id',
        'parent',
        'name',
        'code',
        'description',
        'active',
        'sort',
    ];

    protected $casts = [
        'collection_id' => 'integer',
        'parent'        => 'integer',
        'active'        => 'boolean',
        'sort'          => 'integer',
        'created_at'    => FormattedDate::class,
        'updated_at'    => FormattedDate::class,
    ];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(ContentCollection::class, 'collection_id');
    }

    public function parentSection(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent');
    }

    public function childSections(): HasMany
    {
        return $this->hasMany(self::class, 'parent');
    }
}
