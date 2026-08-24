<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An EAV value row: the value of a single custom field for a single item.
 * The value is stored in the typed column matching the field's FieldType.
 *
 * @mixin Builder
 *
 * @property int $id
 * @property int $item_id
 * @property int $field_id
 * @property string|null $value_string
 * @property int|null $value_int
 * @property float|null $value_double
 * @property \Illuminate\Support\Carbon|null $value_date
 * @property string|null $value_text
 * @property int $sort
 *
 * @property ContentCollectionItem $item
 * @property ContentCollectionField $field
 */
final class ContentCollectionItemValue extends Model
{
    protected $table = 'collection_item_values';

    public $timestamps = false;

    protected $fillable = [
        'item_id',
        'field_id',
        'value_string',
        'value_int',
        'value_double',
        'value_date',
        'value_text',
        'sort',
    ];

    protected $casts = [
        'item_id'      => 'integer',
        'field_id'     => 'integer',
        'value_int'    => 'integer',
        'value_double' => 'float',
        'value_date'   => 'datetime',
        'sort'         => 'integer',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(ContentCollectionItem::class, 'item_id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(ContentCollectionField::class, 'field_id');
    }
}
