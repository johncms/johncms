<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Install;

use Gettext\TranslatorFunctions;
use Illuminate\Support\Str;
use Johncms\Modules\Collections\Application\Api\CollectionSchemaApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionSectionsApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsApiInterface;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionFormDTO;
use Johncms\Modules\Collections\Domain\Query\ContentCollectionItemQuery;
use Johncms\Modules\Installer as ModuleInstaller;
use Johncms\Modules\News\Application\Collections\NewsCollectionDeclaration;
use Johncms\Modules\News\Application\Collections\NewsStorageUpdater;
use Johncms\Modules\News\Domain\Repository\NewsDiscussionRepositoryInterface;
use Johncms\System\i18n\Translator;
use Johncms\Updates\SystemOperationContext;
use LogicException;

class Installer extends ModuleInstaller
{
    public function update(string $from, string $to): void
    {
        di(NewsStorageUpdater::class)->update();
    }

    public function installDemoData(): void
    {
        $translator = TranslatorFunctions::getTranslator();
        if ($translator instanceof Translator) {
            $translator->addTranslationDomain('news', MODULES_PATH . 'johncms/news/locale', false);
        }
        di(SystemOperationContext::class)->runTrusted(function (): void {
            di(CollectionSchemaApiInterface::class)->synchronizeDeclarations();
            $api = di(CollectionsApiInterface::class);
            $collection = $api->findDeclaredCollection(NewsCollectionDeclaration::OWNER, NewsCollectionDeclaration::KEY)
                ?? throw new LogicException('The news declaration must be synchronized before installing demo data.');
            if ($api->countItems(new ContentCollectionItemQuery(collectionId: $collection->id, onlyActive: false)) > 0) {
                return;
            }
            $apiSections = di(CollectionSectionsApiInterface::class);
            $discussion = di(NewsDiscussionRepositoryInterface::class);
            $sections = [];
            foreach ([1 => d__('news', 'Site news'), 2 => d__('news', 'Announcements')] as $key => $name) {
                $sections[$key] = $apiSections->addSection(new CollectionSectionFormDTO($collection->id, null, Str::slug($name), $name, null, true, 100));
            }
            $articles = [
            [
                'id'           => 1,
                'section_id'   => 1,
                'name'         => d__('news', 'Welcome to the site!'),
                'preview_text' => d__('news', '<p>We are glad to welcome you to our site.</p>'),
                'text'         => d__('news', '<p>We are glad to welcome you to our site. Here you will find the latest news, useful materials and lively discussion.</p>'),
            ],
            [
                'id'           => 2,
                'section_id'   => 1,
                'name'         => d__('news', 'The site is launched'),
                'preview_text' => d__('news', '<p>Our site is officially open to visitors.</p>'),
                'text'         => d__('news', '<p>Our site is officially open to visitors. Stay tuned for updates — a lot of interesting things are coming!</p>'),
            ],
            [
                'id'           => 3,
                'section_id'   => 2,
                'name'         => d__('news', 'Site rules'),
                'preview_text' => d__('news', '<p>We ask all members to read the rules.</p>'),
                'text'         => d__('news', '<p>We ask all members to read the site rules. Respect each other, avoid rudeness and spam. The administration reserves the right to remove materials that violate the rules.</p>'),
            ],
            [
                'id'           => 4,
                'section_id'   => 2,
                'name'         => d__('news', 'Maintenance'),
                'preview_text' => d__('news', '<p>The scheduled maintenance is complete.</p>'),
                'text'         => d__('news', '<p>The scheduled maintenance has been completed successfully. All site features are working normally. Thank you for your patience!</p>'),
            ],
            ];
            foreach ($articles as $article) {
                $id = $api->addItem(item: new CollectionItemFormDTO(
                    $collection->id,
                    $sections[$article['section_id']],
                    Str::slug($article['name']),
                    $article['name'],
                    true,
                    null,
                    null,
                    100,
                    $article['preview_text'],
                    $article['text'],
                    []
                ), actorId: 1);
                $discussion->addComment(
                    $id,
                    1,
                    d__('news', '<p>Great news! Glad to see the site up and running.</p>'),
                    ['user_agent' => 'JohnCMS demo installer', 'ip' => '127.0.0.1', 'ip_via_proxy' => ''],
                    []
                );
                $discussion->vote($id, 1, 1);
            }
        });
    }
}
