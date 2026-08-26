# Changelog 
This project follows [semantic versioning](https://semver.org/).  
All notable changes to this project will be documented in this file.  
Detailed change can see in the [repository log](https://github.com/johncms/johncms/commits).

История версий 9.x и ранее — в [changelog ветки 9.x](https://github.com/johncms/johncms/blob/9.x/CHANGELOG.md).

## 10.0 - Unreleased

#### Breaking changes
- **Модули переехали в каталог вендора.** Было `modules/<модуль>/`, стало `modules/<вендор>/<модуль>/`: всё, что поставляется вместе с CMS, теперь лежит в `modules/johncms/` — `modules/johncms/forum/`, `modules/johncms/news/` и так далее. Так у каждого модуля появляется собственное пространство имён, и два разных автора могут выпустить модуль с одинаковым названием, не сталкиваясь друг с другом.

  **Имена не изменились.** Twig-неймспейс шаблона (`@forum/...`), домен переводов (`d__('forum', ...)`) и источник миграций (`migrate --source=forum`) остались прежними — вендор относится только к тому, где лежат файлы. Ни шаблоны, ни словари, ни таблица `migrations` правки не требуют.

  Что нужно сделать при обновлении: после распаковки новой версии **удалить старые каталоги `modules/<модуль>/`**. Система их не видит и не подключает, но они останутся на диске мёртвым грузом и будут сбивать с толку при работе по FTP.

  Авторам модулей и тем:

  | Было                                     | Стало                                            |
  |------------------------------------------|--------------------------------------------------|
  | `modules/blog/`                          | `modules/<вендор>/blog/`                         |
  | `MODULES_PATH . 'blog/src/Application'`  | `MODULES_PATH . '<вендор>/blog/src/Application'` |
  | `"Johncms\\Modules\\Blog\\": "modules/blog/src/"` | `"...": "modules/<вендор>/blog/src/"`            |
  | `@blog/public/index.twig`                | без изменений                                    |
  | `d__('blog', ...)`                       | без изменений                                    |

  Атрибут маршрута `_module` теперь несёт ключ модуля целиком (`johncms/forum`, не `forum`) — по нему конвейер находит каталог словарей. Домен переводов из него берётся как имя без вендора.

- **У модулей появился реестр.** Система больше не считает модулем всё, что лежит в папке: модуль описывает себя манифестом `module.php` (ключ `вендор/модуль`, короткое имя `alias`, название), а сайт хранит в `config/autoload/modules.local.php`, что у него установлено и что выключено.

  Формат `config/autoload/modules.global.php` изменился: вместо `installed_modules` и `system_modules` — один список `bundled` с ключами модулей поставки. Какой модуль системный, теперь сказано в его манифесте (`'system' => true`), а не в конфигурации сайта.

  ```php
  // config/autoload/modules.local.php — создаётся системой
  return [
      'modules' => [
          'state' => [
              'mysite/partners' => ['alias' => 'partners', 'installed' => true, 'enabled' => true],
          ],
      ],
  ];
  ```

  Команды: `php system/bin/console module:list` показывает все модули и их состояние, `module:sync` записывает состояние сайта в файл (ничего не устанавливая). В `config/constants.php` добавлена константа `MODULES_SAFE_MODE`: включённая, она оставляет только системные модули — способ вернуть панель администратора на сайте, который уронил чужой модуль.

  Класс `Johncms\Modules\Modules` удалён; список установленных модулей отдаёт `Johncms\Modules\ModuleRegistry`.

  Сервисы, маршруты, миграции и переводы теперь берутся у реестра, а не у папки: выключенный модуль ничего не объявляет, и его страницы отвечают 404, а не ошибкой. Миграции выключенного модуля остаются в журнале — его таблицы никуда не делись.

  Модуль может добавить себя в меню сайта и в меню панели администратора: он реализует `Johncms\View\Menu\MenuItemProviderInterface` и возвращает `MenuItem` — заголовок, адрес, иконку, право и вес. Пункт, который посетителю недоступен, до шаблона не доходит: право проверяется до отрисовки. Тема выводит эти пункты сама, редактировать её не нужно.

  Права модуля больше не остаются в ролях навсегда: `module:uninstall --purge` снимает их со всех ролей, предварительно записав снимок в `data/backups/permissions-<вендор>-<модуль>-<дата>.json`. Обычное удаление права оставляет — как и таблицы. Какие права принадлежат модулю, спрашивается у самого модуля (его провайдеров), а не угадывается по префиксу ключа.

  После установки модуля выполните `auth:sync-roles`, чтобы роли получили его права: контейнер собирается до появления модуля, поэтому в том же процессе его права ещё не известны — команда установки об этом напоминает.

  Модуль может привозить собственные стили и скрипты. Он кладёт **собранные** файлы в `modules/<вендор>/<модуль>/public/` и перечисляет их в манифесте (`assets.entries`); установка копирует их в `public/modules/<алиас>/`, выключение — убирает. В document root попадают только файлы, которые загружает браузер: `.php` среди ассетов не окажется. Шаблон подключает отдельный файл через `module_asset('blog', 'js/app.js')`, а всё перечисленное в `entries` макет выводит сам — в базовые шаблоны темы добавлен блок `module_assets`. Команда `module:publish-assets [модуль] [--symlink]` копирует ассеты заново.

  Модули устанавливаются и удаляются командами: `module:install <вендор>/<модуль> [--demo]`, `module:enable`, `module:disable`, `module:update`, `module:uninstall [--purge]`. Установка прогоняет миграции модуля и его `install()`; удаление вызывает `uninstall()` и забывает модуль, **оставляя таблицы** — данные удаляет только `--purge`, и он отказывается работать, если хоть одна миграция модуля не умеет откатываться. Выключить системный модуль или тот, от которого зависит другой установленный, нельзя — команда скажет, кто держит.

  У `Johncms\Modules\Installer` снова есть `install()`, добавился `update($from, $to)`, и ни один метод больше не абстрактный: модуль переопределяет то, что ему нужно. Таблицы по-прежнему создаются только миграциями. Класс `Johncms\Modules\ModuleInstaller` удалён.

  `migrate:rollback --all --source=<источник>` откатывает все миграции источника, а не последнюю партию.

  Модуль может объявить, без чего он не работает: `requires` в манифесте — версия PHP, версия CMS и другие модули (ограничения версий пишутся как в Composer и читаются `composer/semver`). Несовместимый модуль не загружается и виден в `module:list` с причиной; вместе с ним не загружается то, что на нём построено. Модуль, нужный системному, остаётся загруженным, а в списке сказано, кто его держит: выключить то, на чём стоит панель администратора, нельзя.

  **Версия CMS поднята до 10.0.** Это меняет две вещи: префикс кэша (`jc9_9` → `jc10_0`, весь прежний кэш перестаёт использоваться) и запрос списка языков к johncms.com, который теперь спрашивает языки для 10.0.

  Классы стороннего модуля загружаются без правки корневого `composer.json`: модуль объявляет `autoload` в своём манифесте, и `Johncms\Modules\ModuleAutoloader` регистрирует пространство имён при загрузке сайта. Модуль, у которого есть собственные зависимости, указывает там же свой `vendor/autoload.php`. Модули поставки остаются в корневом `composer.json`.

  ```php
  'autoload' => [
      'psr-4' => ['Vendor\\Module\\' => 'src/'],
      'files' => ['vendor/autoload.php'],
  ],
  ```

  Команда `forum:cleanup-orphan-files` переехала из ядра в модуль форума: класс `Johncms\Console\Commands\CronCleanupForumFilesCommand` заменён на `Johncms\Modules\Forum\Application\Console\CleanupOrphanFilesCommand`. Имя команды и расписание не изменились. Авторам модулей: консольная команда, работающая с данными модуля, должна лежать в самом модуле — иначе выключение модуля ломает сборку контейнера.

- **Схема базы данных переехала в миграции.** Установщик больше не создаёт таблицы сам: и свежая установка, и обновление существующего сайта идут одним путём — через миграции.

  Миграции лежат в `system/migrations/` (ядро) и `modules/<вендор>/<модуль>/migrations/` (модули). Файл возвращает анонимный класс, унаследованный от `Johncms\Database\Migrations\Migration`, и получает только схему и соединение — ни моделей, ни репозиториев, ни сервисов: миграция обязана отработать без изменений и через несколько лет.

  ```php
  return new class extends Migration {
      public function up(): void
      {
          $this->schema->create('widgets', static function (TableDefinition $table): void {
              $table->increments('id');
              $table->string('name');
              $table->timestamps();
          });
      }
  };
  ```

  Команды:

  ```bash
  php system/bin/console migrate                 # применить неприменённое
  php system/bin/console migrate:status          # что применено, что ждёт
  php system/bin/console migrate:rollback        # откатить последний прогон (для разработки)
  php system/bin/console make:migration <источник> <имя> [--table=] [--create]
  ```

  Журнал применённого — таблица `migrations` в самой базе, а не файл: восстановленный дамп приносит с собой ровно то состояние, которому соответствует.

  Что нужно сделать при обновлении: выполнить `php system/bin/console migrate`. Команды `auth:upgrade-schema` и `mail:upgrade-schema` удалены — их работу делает `migrate`.

  Без консоли обойтись тоже можно: на странице **Обслуживание** (`/admin/maintenance`) появилась задача «Update the database», она выполняется в фоне планировщиком. А если база отстала от файлов, админ-панель говорит об этом баннером на каждой странице — забыть про обновление больше не выйдет.

  Разовые команды `library:generate-slugs`, `downloads:generate-slugs` и `forum:normalize-message-links` удалены. Все три конвертировали данные при переходе с 9.8 на 9.9, а перейти на 10.0 можно только с 9.9 — где эта работа уже сделана. Вместе с последней удалён служивший ей `Johncms\Modules\Forum\Application\Services\ForumMessageLinkNormalizer`.

  **Мост с 9.9 на роли сжат с четырёх команд до двух.** `auth:migrate-rights` и `auth:migrate-module-access` объединены в `auth:migrate-legacy-access`: она сама создаёт встроенные роли, раздаёт должности и переносит настройки `mod_*`. Нужно ли что-то делать, определяется по самой базе — пока колонка `users.rights` на месте, перенос не завершён, — поэтому файл `config/autoload/one_time_tasks.local.php` и класс `Johncms\Console\OneTimeTaskTracker` больше не нужны и удалены; файл можно удалить руками.

  `auth:apply-default-permissions` заменена на `auth:sync-roles` — её стоит выполнять после **каждого** обновления, а не однократно: новая версия или новый модуль могут объявить роль или право, которых у сайта ещё нет. Она только добавляет и ничего не отнимает. `auth:drop-legacy-columns` осталась отдельной командой и теперь отказывается работать, пока хоть у кого-то есть прежняя должность и нет роли.

  Авторам модулей: метод `install()` у `Johncms\Modules\Installer` удалён, таблицы модуля описываются его миграциями. `installDemoData()` и `uninstall()` не изменились. Классы `Johncms\Auth\Schema\AuthSchema` и `Johncms\Mail\Schema\MailSchema` удалены; имена таблиц, которые они держали, переехали в `Johncms\Auth\AuthTables` и `Johncms\Mail\MailTables`.

- **Шаблоны переведены на Twig, движок Plates удалён.** Файлы `.phtml` больше не рендерятся, пакет `mobicms/render` исключён из зависимостей, вместе с ним удалены `Johncms\System\View\Render`, его расширения (`Assets`, `Avatar`, `Vite`, `Formatter`) и переменные шаблонов `$this->e()`, `$this->layout()`, `$this->fetch()`, `$user`, `$config`, `$tools`.

  Своя тема потребует переписывания. Что меняется:

  | Было (Plates)                                                         | Стало (Twig)                                                          |
  |-----------------------------------------------------------------------|-----------------------------------------------------------------------|
  | `$this->layout('system::layout/default')`                             | `{% extends '@theme/layouts/default.twig' %}` + `{% block content %}` |
  | `$this->fetch('ns::partial', [...])`                                  | `{% include '@ns/partial.twig' with {...} only %}`                    |
  | `<?= $this->e($value) ?>`                                             | `{{ value }}` — Twig экранирует всё, что печатает                     |
  | `<?= $value ?>` (готовая разметка)                                    | `{{ value }}`, если источник отдаёт `Twig\Markup`; иначе `{{ value    |raw }}` |
  | `$this->asset(...)`, `$this->avatar(...)`, `$this->formatNumber(...)` | функции `asset()`, `avatar()`, фильтр `                               |format_number` |
  | `$user`, `$config`, `$csrf_token`                                     | `app.user`, `config('johncms.…')`, `app.csrf_token`                   |
  | `модуль::файл`                                                        | `@модуль/public/файл.twig`                                            |

  Шаблоны модулей разложены по областям: публичные — в `templates/public`, админские — в `templates/admin`, поэтому и путь переопределения в теме стал длиннее (`themes/<тема>/templates/homepage/public/index.twig`). В теме: макеты — в `templates/layouts`, общие блоки — в `templates/components`, системные страницы (результат действия, 403, 404) — в `templates/pages`, письма — в `templates/emails`, вёрстка админ-панели — в `templates/admin`.

  Тема получила манифест `theme.php`: имя, родительская тема (`parent`), точки входа сборки (`entries`) и настройки. Тема без манифеста считается наследницей `default`, а цепочка наследования теперь произвольной длины — тема может состоять из одного файла. Пример такой темы — `themes/example`.

  Письма и инсталлятор отрисовываются в отдельных окружениях: у письма нет запроса, csrf и сборки, адреса в нём абсолютные, а язык — язык получателя; инсталлятор работает до появления конфигурации. Прежняя тема `themes/admin` удалена: админ-панель — это пространство имён `@admin` внутри темы сайта.

- **Отправка почты выполняется только планировщиком.** Отправка «на хитах» удалена вместе с константой `USE_CRON`: очередь писем больше не разбирается после ответа посетителю. Настройте cron-задачу планировщика с периодичностью раз в минуту, иначе письма не будут уходить:

  ```bash
  php /path/to/project/system/bin/console schedule:run --no-interaction
  ```

  Команда очереди — `mail:send-pending`, она зарегистрирована в планировщике и запускается им ежеминутно. Если в вашем `config/constants.php` осталась строка `const USE_CRON = ...`, её нужно удалить.
- **Проверка CSRF выполняется конвейером, а не валидатором.** Правило `Csrf` удалено вместе со старым валидатором: подлинность запроса — не валидация пользовательского ввода. `CsrfMiddleware` проверяет каждый запрос небезопасным методом (`POST`, `PUT`, `PATCH`, `DELETE`) до того, как тот дойдёт до логики модуля, поэтому 46 вызовов валидатора с рулсетом `['csrf_token' => ['Csrf']]` из контроллеров исчезли — проверять токен вручную больше не нужно и не следует.

  Токен берётся из поля `csrf_token` **или** из заголовка `X-CSRF-Token`. Что нужно сделать авторам модулей и тем:

  * в каждую POST-форму добавить `<input type="hidden" name="csrf_token" value="{{ app.csrf_token }}">`;
  * запросам без формы (axios, загрузчик CKEditor) передавать заголовок; в теме по умолчанию он проставляется автоматически из мета-тега `<meta name="csrf-token">`, который печатает макет;
  * блок ошибок `errors.csrf_token` в шаблонах переименован в `errors._form` — это область ошибок формы целиком, через неё же выводятся сообщения `Flood` и `Ban`.

  Запрос без токена получает `403`: для XHR и `Accept: application/json` — JSON `{"message": "…"}`, иначе — страница ошибки. Эндпоинт, которому проверка не нужна, объявляется явно: `$router->post('/path', Controller::class)->withoutCsrf()` (действует и на `RouteCollection`, включая вложенные группы) либо через список путей в `config/csrf.php`.

  Попутно закрыта уязвимость: 60 POST-форм не передавали токен вовсе — среди них вход, регистрация, восстановление пароля и формы модулей `downloads`, `library`, `mail`, `notifications`, `album` и части `profile` и `forum`.
- **Валидатор переписан на `symfony/validator`, пакеты `laminas/*` удалены из зависимостей.** `Johncms\Validator\Validator` удалён — валидатор внедряется через `Johncms\Validator\ValidatorInterface`, а правила стали типизированными объектами вместо строковых ключей массива:

  ```php
  // Было
  $validator = new Validator($formData, ['name' => ['NotEmpty', 'StringLength' => ['min' => 2, 'max' => 25]]]);
  if (! $validator->isValid()) {
      $errors = $validator->getErrors();
  }

  // Стало
  $result = $this->validator->validate($formData, ['name' => [new StringLength(min: 2, max: 25)]]);
  if (! $result->isValid()) {
      $errors = $result->getErrors();
  }
  ```

  `validate()` возвращает `ValidationResult` с методами `isValid()`, `getErrors()`, `hasError()`, `getFirstError()`, `withError()`, `merge()` и `throwIfInvalid()`. Форма массива ошибок прежняя — `имя поля => список сообщений`, — так что шаблоны и доменные исключения менять не нужно.

  Что важно знать при переносе своего кода:

  * **поле, у которого есть правила, обязательно, если явно не помечено обратное.** Раньше обязательность вычитывалась из семантики конкретного правила laminas; теперь необязательное поле объявляется как `new StringLength(max: 250, allowEmpty: true)`;
  * правила уровня формы (`Flood`, `Ban`) переехали с поля `csrf_token`, которое служило им случайным носителем, на зарезервированный ключ `Johncms\Validator\ValidationResult::FORM_KEY` (`_form`);
  * сообщение переопределяется у конкретного правила (`new Identical(token: '1', message: __('…'))`), а не для всей формы сразу, как принимал третий аргумент старого конструктора;
  * правила из 38 неиспользуемых (файловые, `Date`, `Ip`, `Uri`, `Regex`, `Hostname`, `Barcode`, `Iban`, `Isbn`, `CreditCard`, хэш-правила) не перенесены — они добавляются по мере появления первого вызова;
  * своё правило модуль добавляет, не трогая ядро: значение-объект плюс фабрика с тегом `johncms.validator.rule_factory` — либо, если проверка своя, класс-констрейнт со своим `ConstraintValidator`, получающим зависимости через конструктор. Как это делается — в `.agents/validation.md`.

  Тексты сообщений и их переводы сохранены: msgid остались прежними, ни одна из 20 языковых версий не потребовала новых строк. DNS-проверка почтового адреса (`useMxCheck` в регистрации, профиле и инсталляторе), которой в `symfony/validator` нет, сохранена собственным правилом `MxRecord`.
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

  | Было                         | Стало                                                                                   |
  |------------------------------|-----------------------------------------------------------------------------------------|
  | `Tools::antiflood()`         | `Johncms\Security\AntifloodCheckerInterface::getRemainingSeconds()`                     |
  | `Tools::checkout()`          | удалён без прямой замены, см. ниже                                                      |
  | `Tools::displayDate()`       | `Johncms\Utils\DateFormatterInterface::format()`                                        |
  | `Tools::displayError()`      | удалён без замены (не использовался)                                                    |
  | `Tools::displayPlace()`      | `Johncms\Users\UserPlaceFormatterInterface::format()`                                   |
  | `Tools::formatNumber()`      | `Johncms\Utils\ShortNumberFormatter::format()`                                          |
  | `Tools::getSections()`       | `Johncms\Modules\Forum\Application\Services\ForumSectionTreeService::getAncestors()`    |
  | `Tools::getSectionsTree()`   | `Johncms\Modules\Forum\Application\Services\ForumSectionTreeService::getFlatTree()`     |
  | `Tools::getUser()`           | модель `Johncms\Users\User`                                                             |
  | `Tools::isIgnor()`           | `Johncms\Users\IgnoreListCheckerInterface::isBlockedBy()`                               |
  | `Tools::recountForumTopic()` | `Johncms\Modules\Forum\Application\Services\ForumTopicStatsRecalculator::recalculate()` |
  | `Tools::rusLat()`            | `Johncms\Utils\Transliterator::toLatin()`                                               |
  | `Tools::smilies()`           | `Johncms\Smilies\SmiliesRendererInterface::render()`                                    |
  | `Tools::timecount()`         | `Johncms\Utils\DurationFormatter::format()`                                             |
  | `Tools::trans()`             | `Johncms\Utils\Transliterator::toCyrillic()`                                            |

  Отдельно про изменения контрактов:

  * **Переменная `$tools` больше не передаётся в шаблоны.** В Twig те же операции доступны фильтрами `|format_number` и `|display_date`.
  * `Tools::checkout()` (`htmlentities` на этапе подготовки данных) удалён: по правилу «escape on output» экранированием занимается шаблонизатор. Для HTML-фрагментов, которые собираются в PHP, добавлен `Johncms\Utils\PlainTextFormatter` (`escape()` и `toHtml()` — экранирование с `nl2br`).
  * `antiflood()` возвращал `int|false`, новый `getRemainingSeconds()` возвращает `int` (0 — флуда нет).
  * `smilies($str, $adm)` принимал `int|bool` вторым аргументом, новый `render(string $text, bool $withAdminSmilies)` — строго `bool`.

  Попутно исправлено: убрано двойное экранирование в поиске по форуму и в хлебных крошках; добавлено экранирование значения редактора CKEditor, заголовка новой темы форума, ссылок из рекламных блоков и списков в модуле library; в админке снова корректно выделяется текущий родительский раздел при редактировании раздела форума; `isIgnor()` больше не возвращает закешированный результат от предыдущего пользователя; пересчёт статистики темы не падает на теме без сообщений.
