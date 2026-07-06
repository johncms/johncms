<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Johncms\Casts\FormattedDate;
use Johncms\Modules\Collections\Domain\Enums\FieldType;

/**
 * A custom field definition of a collection.
 *
 * @mixin Builder
 *
 * @property int $id
 * @property int $collection_id
 * @property string $code
 * @property string $name
 * @property FieldType $type
 * @property bool $required
 * @property bool $multiple
 * @property int $sort
 * @property array|null $settings
 * @property string $created_at
 * @property string $updated_at
 *
 * @property ContentCollection $collection
 */
final class ContentCollectionField extends Model
{
    protected $table = 'collection_fields';

    protected $fillable = [
        'collection_id',
        'code',
        'name',
        'type',
        'required',
        'multiple',
        'sort',
        'settings',
    ];

    protected $casts = [
        'collection_id' => 'integer',
        'type'          => FieldType::class,
        'required'      => 'boolean',
        'multiple'      => 'boolean',
        'sort'          => 'integer',
        'settings'      => 'array',
        'created_at'    => FormattedDate::class,
        'updated_at'    => FormattedDate::class,
    ];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(ContentCollection::class, 'collection_id');
    }
}
