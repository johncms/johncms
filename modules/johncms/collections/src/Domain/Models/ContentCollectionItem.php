<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Johncms\Casts\FormattedDate;

/**
 * A collection item (record). Base fields are columns here; custom fields live
 * in `collection_item_values`.
 *
 * @mixin Builder
 *
 * @property int $id
 * @property int $collection_id
 * @property int|null $section_id
 * @property string $name
 * @property string $code
 * @property bool $active
 * @property \Illuminate\Support\Carbon|null $active_from
 * @property \Illuminate\Support\Carbon|null $active_to
 * @property int $sort
 * @property string|null $preview_text
 * @property string|null $detail_text
 * @property int|null $view_count
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property string $created_at
 * @property string $updated_at
 *
 * @property ContentCollection $collection
 * @property ContentCollectionSection|null $section
 * @property ContentCollectionItemValue[] $values
 */
final class ContentCollectionItem extends Model
{
    protected $table = 'collection_items';

    protected $fillable = [
        'collection_id',
        'section_id',
        'name',
        'code',
        'active',
        'active_from',
        'active_to',
        'sort',
        'preview_text',
        'detail_text',
        'view_count',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'collection_id' => 'integer',
        'section_id'    => 'integer',
        'active'        => 'boolean',
        // Publish window drives selection logic (compared in SQL), keep as Carbon.
        'active_from'   => 'datetime',
        'active_to'     => 'datetime',
        'sort'          => 'integer',
        'view_count'    => 'integer',
        'created_by'    => 'integer',
        'updated_by'    => 'integer',
        'created_at'    => FormattedDate::class,
        'updated_at'    => FormattedDate::class,
    ];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(ContentCollection::class, 'collection_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(ContentCollectionSection::class, 'section_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(ContentCollectionItemValue::class, 'item_id');
    }

    /**
     * Custom field values as a map of field code => cast value.
     *
     * Multiple-valued fields yield an array ordered by value sort. Load the
     * `values` relation together with the nested `field` (eager load
     * `values.field`) before calling this to avoid N+1 queries.
     *
     * @return array<string, mixed>
     */
    public function getValuesMap(): array
    {
        $map = [];
        foreach ($this->values->sortBy('sort') as $value) {
            $field = $value->field;
            if ($field === null) {
                continue;
            }

            $cast = $field->type->cast($value->{$field->type->valueColumn()});
            if ($field->multiple) {
                $map[$field->code][] = $cast;
            } else {
                $map[$field->code] = $cast;
            }
        }

        return $map;
    }
}
