# Changelog 
This project follows [semantic versioning](https://semver.org/).  
All notable changes to this project will be documented in this file.  
Detailed change can see in the [repository log](https://github.com/johncms/johncms/commits).

История версий 9.x и ранее — в [changelog ветки 9.x](https://github.com/johncms/johncms/blob/9.x/CHANGELOG.md).

## 10.0 - Unreleased

#### Breaking changes
- **Шаблоны переведены на Twig, движок Plates удалён.** Файлы `.phtml` больше не рендерятся, пакет `mobicms/render` исключён из зависимостей, вместе с ним удалены `Johncms\System\View\Render`, его расширения (`Assets`, `Avatar`, `Vite`, `Formatter`) и переменные шаблонов `$this->e()`, `$this->layout()`, `$this->fetch()`, `$user`, `$config`, `$tools`.

  Своя тема потребует переписывания. Что меняется:

  | Было (Plates) | Стало (Twig) |
  |---|---|
  | `$this->layout('system::layout/default')` | `{% extends '@theme/layouts/default.twig' %}` + `{% block content %}` |
  | `$this->fetch('ns::partial', [...])` | `{% include '@ns/partial.twig' with {...} only %}` |
  | `<?= $this->e($value) ?>` | `{{ value }}` — Twig экранирует всё, что печатает |
  | `<?= $value ?>` (готовая разметка) | `{{ value }}`, если источник отдаёт `Twig\Markup`; иначе `{{ value|raw }}` |
  | `$this->asset(...)`, `$this->avatar(...)`, `$this->formatNumber(...)` | функции `asset()`, `avatar()`, фильтр `|format_number` |
  | `$user`, `$config`, `$csrf_token` | `app.user`, `config('johncms.…')`, `app.csrf_token` |
  | `модуль::файл` | `@модуль/public/файл.twig` |

  Шаблоны модулей разложены по областям: публичные — в `templates/public`, админские — в `templates/admin`, поэтому и путь переопределения в теме стал длиннее (`themes/<тема>/templates/homepage/public/index.twig`). В теме: макеты — в `templates/layouts`, общие блоки — в `templates/components`, системные страницы (результат действия, 403, 404) — в `templates/pages`, письма — в `templates/emails`, вёрстка админ-панели — в `templates/admin`.

  Тема получила манифест `theme.php`: имя, родительская тема (`parent`), точки входа сборки (`entries`) и настройки. Тема без манифеста считается наследницей `default`, а цепочка наследования теперь произвольной длины — тема может состоять из одного файла. Пример такой темы — `themes/example`.

  Письма и инсталлятор отрисовываются в отдельных окружениях: у письма нет запроса, csrf и сборки, адреса в нём абсолютные, а язык — язык получателя; инсталлятор работает до появления конфигурации. Прежняя тема `themes/admin` удалена: админ-панель — это пространство имён `@admin` внутри темы сайта.

- **Отправка почты выполняется только планировщиком.** Отправка «на хитах» удалена вместе с константой `USE_CRON`: очередь писем больше не разбирается после ответа посетителю. Настройте cron-задачу планировщика с периодичностью раз в минуту, иначе письма не будут уходить:

  ```bash
  php /path/to/project/system/bin/console schedule:run --no-interaction
  ```

  Команда очереди — `mail:send-pending`, она зарегистрирована в планировщике и запускается им ежеминутно. Если в вашем `config/constants.php` осталась строка `const USE_CRON = ...`, её нужно удалить.
- **Запрос перестал быть сервисом контейнера.** `di(\Johncms\Http\Request::class)` и `$container->get(Request::class)` теперь бросают исключение. Контроллер получает запрос аргументом действия, middleware — аргументом `handle()`, сервис, живущий дольше запроса, — из `Symfony\Component\HttpFoundation\RequestStack`. Шаблоны не обращаются к запросу: нужный факт отдаёт тонкий сервис поверх стека (например, `Johncms\Http\CurrentPage::isHomePage()`). Подробности — в разделе документации «Работа с запросом (Request)».
- **Сессии переведены на `symfony/http-foundation`.** Данные больше не лежат в корне `$_SESSION`, а хранятся в `AttributeBag`. При обновлении все существующие сессии перестают читаться — **пользователи разлогинятся один раз**, ничего восстанавливать не нужно. Имя куки (`SESID`) не изменилось.

  Для авторов модулей и тем: класс `Johncms\Http\Session` сохранил методы `get/set/has/remove/clear/flash/getFlash`, но **больше не поддерживает точечную нотацию** (`$session->get('a.b')`) — ключи плоские, вложенные данные читаются и пишутся целым массивом. Добавлены `start()`, `isStarted()`, `save()` и `invalidate()` (сброс данных со сменой идентификатора сессии — используется при выходе из аккаунта). Сессию открывает само приложение: вызывать `session_start()` в своём коде не нужно, а консольные команды и крон работают на in-memory хранилище и файлов сессий не создают.
- **Document root переехал в `public/`.** Теперь по HTTP доступен только этот каталог: `index.php`, `favicon.ico`, `robots.txt`, `sitemap*.xml`, `assets/`, `upload/`, `install/` и ассеты тем (`public/themes/<тема>/assets/`). Код, конфигурация и шаблоны остались в корне и больше не доступны из веба. **URL не изменились ни один.**

  При обновлении переведите document root сайта на `<каталог сайта>/public` (в nginx — директива `root`, в панели хостинга — «корневая директория сайта») и перезапустите php-fpm: кэш realpath держит старые пути. Если сменить document root нельзя, на Apache сработает `.htaccess` в корне: он перенаправит запросы в `public/` и закроет доступ к коду. На nginx такой запасной вариант невозможен — там смена root обязательна.

  Исходники тем (`themes/<тема>/src`, `templates`) остались в корне; собранные ассеты авторам тем нужно класть в `public/themes/<тема>/assets/`.
- **Класс `Johncms\System\View\Theme` переименован в `Johncms\View\ColorScheme`.** Он отвечает за цветовую схему страницы (dark/light/auto из куки `siteTheme`), а не за тему оформления, и имя понадобилось под тему сайта. Методы тоже переименованы: `getCurrentTheme()` → `getCurrentScheme()`, `isDarkTheme()` → `isDarkScheme()`. Авторам тем: в шаблонах цветовая схема доступна как `app.color_scheme`. Имя куки и набор значений не изменились.
- Composer: зависимости переехали из `system/vendor` в стандартный `vendor/`. При обновлении удалите каталог `system/vendor` и выполните `composer install`.
- Из каталога `install/` удалены разовые скрипты обновления с версий ниже 9.9, конвертеры и скрипты доустановки модулей 9.9. Обновляйтесь по пути 9.8 → 9.9 → 10.0: скрипты и инструкции к ним остались в ветке `9.x`. Каталог `install/` теперь содержит только веб-инсталлятор.
- **Удалён легаси-класс `Johncms\System\Legacy\Tools`.** Вместе с ним удалены каталог `system/src-legacy/` и весь namespace `Johncms\System\Legacy\`. Методы разнесены по подходящим местам:

  | Было | Стало |
  |---|---|
  | `Tools::antiflood()` | `Johncms\Security\AntifloodCheckerInterface::getRemainingSeconds()` |
  | `Tools::checkout()` | удалён без прямой замены, см. ниже |
  | `Tools::displayDate()` | `Johncms\Utils\DateFormatterInterface::format()` |
  | `Tools::displayError()` | удалён без замены (не использовался) |
  | `Tools::displayPlace()` | `Johncms\Users\UserPlaceFormatterInterface::format()` |
  | `Tools::formatNumber()` | `Johncms\Utils\ShortNumberFormatter::format()` |
  | `Tools::getSections()` | `Johncms\Modules\Forum\Application\Services\ForumSectionTreeService::getAncestors()` |
  | `Tools::getSectionsTree()` | `Johncms\Modules\Forum\Application\Services\ForumSectionTreeService::getFlatTree()` |
  | `Tools::getUser()` | модель `Johncms\Users\User` |
  | `Tools::isIgnor()` | `Johncms\Users\IgnoreListCheckerInterface::isBlockedBy()` |
  | `Tools::recountForumTopic()` | `Johncms\Modules\Forum\Application\Services\ForumTopicStatsRecalculator::recalculate()` |
  | `Tools::rusLat()` | `Johncms\Utils\Transliterator::toLatin()` |
  | `Tools::smilies()` | `Johncms\Smilies\SmiliesRendererInterface::render()` |
  | `Tools::timecount()` | `Johncms\Utils\DurationFormatter::format()` |
  | `Tools::trans()` | `Johncms\Utils\Transliterator::toCyrillic()` |

  Отдельно про изменения контрактов:

  * **Переменная `$tools` больше не передаётся в шаблоны.** В Twig те же операции доступны фильтрами `|format_number` и `|display_date`.
  * `Tools::checkout()` (`htmlentities` на этапе подготовки данных) удалён: по правилу «escape on output» экранированием занимается шаблонизатор. Для HTML-фрагментов, которые собираются в PHP, добавлен `Johncms\Utils\PlainTextFormatter` (`escape()` и `toHtml()` — экранирование с `nl2br`).
  * `antiflood()` возвращал `int|false`, новый `getRemainingSeconds()` возвращает `int` (0 — флуда нет).
  * `smilies($str, $adm)` принимал `int|bool` вторым аргументом, новый `render(string $text, bool $withAdminSmilies)` — строго `bool`.

  Попутно исправлено: убрано двойное экранирование в поиске по форуму и в хлебных крошках; добавлено экранирование значения редактора CKEditor, заголовка новой темы форума, ссылок из рекламных блоков и списков в модуле library; в админке снова корректно выделяется текущий родительский раздел при редактировании раздела форума; `isIgnor()` больше не возвращает закешированный результат от предыдущего пользователя; пересчёт статистики темы не падает на теме без сообщений.
