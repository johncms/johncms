<?php

declare(strict_types=1);

use News\Actions\Article;
use News\Actions\Section;
use Johncms\System\Http\Request;
use Johncms\System\i18n\Translator;

// Register the module languages domain and folder
di(Translator::class)->addTranslationDomain('news', __DIR__ . '/locale');

$route = di('route');

/** @var Request $request */
$request = di(Request::class);

if (! empty($route['article'])) {
    // Если запросили страницу статьи, открываем её
    (new Article())->index();
} else {
    // Страница просмотра раздела
    (new Section())->index();
}
