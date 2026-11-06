<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Models;

use Gettext\TranslatorFunctions;
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
 * @property string|null $owner_module
 * @property string|null $label_domain
 * @property string|null $role
 * @property string $name
 * @property-read string $hint
 * @property FieldType $type
 * @property bool $required
 * @property bool $multiple
 * @property bool $in_list Shown as a column of the list of the items in the panel.
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

    /** The defaults of the table, so a field built in code reads like a stored one. */
    protected $attributes = [
        'in_list' => false,
    ];

    protected $fillable = [
        'collection_id',
        'code',
        'name',
        'owner_module',
        'label_domain',
        'role',

        'type',
        'required',
        'multiple',
        'in_list',
        'sort',
        'settings',
    ];

    protected $casts = [
        'collection_id' => 'integer',
        'type'          => FieldType::class,
        'required'      => 'boolean',
        'multiple'      => 'boolean',
        'in_list'       => 'boolean',
        'sort'          => 'integer',
        'settings'      => 'array',
        'created_at'    => FormattedDate::class,
        'updated_at'    => FormattedDate::class,
    ];

    public function getNameAttribute(?string $value): string
    {
        $domain = $this->attributes['label_domain'] ?? null;
        return $value !== null && $value !== '' && is_string($domain) && $domain !== '' ? TranslatorFunctions::getTranslator()->dgettext($domain, $value) : ($value ?? '');
    }

    public function getHintAttribute(): string
    {
        $hint = $this->settings['hint'] ?? '';
        $domain = $this->attributes['label_domain'] ?? null;
        return $hint !== '' && is_string($domain) && $domain !== '' ? TranslatorFunctions::getTranslator()->dgettext($domain, $hint) : $hint;
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(ContentCollection::class, 'collection_id');
    }
}
