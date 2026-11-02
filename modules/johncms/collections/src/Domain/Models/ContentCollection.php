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
 * @property string|null $owner_module
 * @property string $code
 * @property string|null $declaration_key
 * @property string|null $label_domain
 * @property string $name
 * @property string|null $description
 * @property array|null $settings
 * @property int $sort
 * @property bool $active
 * @property bool $public
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
        'owner_module',
        'name',
        'declaration_key',
        'label_domain',

        'description',
        'settings',
        'sort',
        'active',
        'public',
    ];

    protected $casts = [
        'settings'   => 'array',
        'sort'       => 'integer',
        'active'     => 'boolean',
        'public'     => 'boolean',
        'created_at' => FormattedDate::class,
        'updated_at' => FormattedDate::class,
    ];

    public function getNameAttribute(?string $value): string
    {
        $domain = $this->attributes['label_domain'] ?? null;
        return $value !== null && $value !== '' && is_string($domain) && $domain !== '' ? \Gettext\TranslatorFunctions::getTranslator()->dgettext($domain, $value) : ($value ?? '');
    }

    /**
     * Where the collection is on the site; empty for one the site does not show — turned off, or
     * private and available only through the API.
     */
    public function publicUrl(): string
    {
        return $this->owner_module === null && $this->active && $this->public ? '/' . $this->code : '';
    }

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
