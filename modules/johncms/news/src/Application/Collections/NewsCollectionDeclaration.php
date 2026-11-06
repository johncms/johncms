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
        return [
            new CollectionDeclarationDTO(
                key:               self::KEY,
                code:              'news',
                label:             CollectionLabelDTO::translatableLabel('news', 'News'),
                contentPermission: NewsPermissions::MANAGE,
                fields: [
                    new CollectionFieldDeclarationDTO(
                        code:     'page_title',
                        label:    CollectionLabelDTO::translatableLabel('news', 'Page title'),
                        type:     FieldType::String_,
                        role:     'meta_title',
                        sort:     100,
                        settings: ['hint' => CollectionLabelDTO::translatableLabel('news', 'Left empty, the title is made of the template in the settings of the news.')->message],
                    ),
                    new CollectionFieldDeclarationDTO(
                        code:     'description',
                        label:    CollectionLabelDTO::translatableLabel('news', 'Meta description'),
                        type:     FieldType::Text,
                        role:     'meta_description',
                        sort:     200,
                        settings: ['hint' => CollectionLabelDTO::translatableLabel('news', 'Left empty, they are made of the template in the settings of the news.')->message],
                    ),
                    new CollectionFieldDeclarationDTO(
                        code:     'keywords',
                        label:    CollectionLabelDTO::translatableLabel('news', 'Meta keywords'),
                        type:     FieldType::Text,
                        role:     'meta_keywords',
                        sort:     300,
                        settings: ['hint' => CollectionLabelDTO::translatableLabel('news', 'Left empty, they are made of the template in the settings of the news.')->message],
                    ),
                    new CollectionFieldDeclarationDTO(
                        code:     'tags',
                        label:    CollectionLabelDTO::translatableLabel('news', 'Tags'),
                        type:     FieldType::String_,
                        multiple: true,
                        role:     'tags',
                        inList:   true,
                        sort:     400,
                    ),
                ],
                settings: [
                    'has_sections'  => true,
                    'per_page'      => 20,
                    'search_fields' => [],
                    '_workspace'    => [
                        'labels'  => [
                            'singular' => CollectionLabelDTO::translatableLabel('news', 'News item')->toArray(),
                            'plural'   => CollectionLabelDTO::translatableLabel('news', 'News')->toArray(),
                            'new'      => CollectionLabelDTO::translatableLabel('news', 'Add news')->toArray(),
                            'create'   => CollectionLabelDTO::translatableLabel('news', 'Add news')->toArray(),
                            'edit'     => CollectionLabelDTO::translatableLabel('news', 'Edit news')->toArray(),
                            'delete'   => CollectionLabelDTO::translatableLabel('news', 'Delete news')->toArray(),
                        ],
                        'layout'  => [
                            'fields' => [
                                'tags'        => ['group' => CollectionLabelDTO::translatableLabel('news', 'Content')->toArray(), 'sort' => 100],
                                'page_title'  => ['group' => CollectionLabelDTO::translatableLabel('news', 'SEO')->toArray(), 'sort' => 200],
                                'description' => ['group' => CollectionLabelDTO::translatableLabel('news', 'SEO')->toArray(), 'sort' => 300],
                                'keywords'    => ['group' => CollectionLabelDTO::translatableLabel('news', 'SEO')->toArray(), 'sort' => 400],
                            ],
                        ],
                        'listing' => [
                            'columns' => ['author', 'created_at', 'views', 'section'],
                            'filters' => ['author', 'created_at', 'views'],
                            'sort'    => [
                                'field'     => 'id',
                                'direction' => 'desc',
                            ],
                        ],
                    ],
                ],
            ),
        ];
    }
}
