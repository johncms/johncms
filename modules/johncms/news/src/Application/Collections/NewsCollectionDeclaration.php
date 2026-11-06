<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Collections;

use Johncms\Modules\Collections\Application\DTO\CollectionDeclarationDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionFieldDeclarationDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionLabelDTO;
use Johncms\Modules\Collections\Application\Extension\CollectionDeclarationProviderInterface;
use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Johncms\Modules\News\Application\Services\NewsPermissions;

final class NewsCollectionDeclaration implements CollectionDeclarationProviderInterface
{
    public const string OWNER = 'johncms/news';
    public const string KEY = 'articles';

    public function owner(): string
    {
        return self::OWNER;
    }

    public function collections(): iterable
    {
        return [new CollectionDeclarationDTO(
            self::KEY,
            'news',
            CollectionLabelDTO::translatableLabel('news', 'News'),
            NewsPermissions::MANAGE,
            [
                new CollectionFieldDeclarationDTO('page_title', CollectionLabelDTO::translatableLabel('news', 'Page title'), FieldType::String_, role: 'meta_title', sort: 100),
                new CollectionFieldDeclarationDTO('description', CollectionLabelDTO::translatableLabel('news', 'Meta description'), FieldType::Text, role: 'meta_description', sort: 200),
                new CollectionFieldDeclarationDTO('keywords', CollectionLabelDTO::translatableLabel('news', 'Meta keywords'), FieldType::Text, role: 'meta_keywords', sort: 300),
                new CollectionFieldDeclarationDTO('tags', CollectionLabelDTO::translatableLabel('news', 'Tags'), FieldType::String_, multiple: true, role: 'tags', inList: true, sort: 400),
            ],
            ['has_sections' => true, 'per_page' => 20, 'search_fields' => [], '_workspace' => ['labels' => [
                'singular' => CollectionLabelDTO::translatableLabel('news', 'News item')->toArray(),
                'plural' => CollectionLabelDTO::translatableLabel('news', 'News')->toArray(),
                'new' => CollectionLabelDTO::translatableLabel('news', 'Add news')->toArray(),
                'create' => CollectionLabelDTO::translatableLabel('news', 'Add news')->toArray(),
                'edit' => CollectionLabelDTO::translatableLabel('news', 'Edit news')->toArray(),
                'delete' => CollectionLabelDTO::translatableLabel('news', 'Delete news')->toArray(),
            ]]]
        )];
    }
}
