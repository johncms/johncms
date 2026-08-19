# Changelog 
This project follows [semantic versioning](https://semver.org/).  
All notable changes to this project will be documented in this file.  
Detailed change can see in the [repository log](https://github.com/johncms/johncms/commits).

История версий 9.x и ранее — в [changelog ветки 9.x](https://github.com/johncms/johncms/blob/9.x/CHANGELOG.md).

## 10.0 - Unreleased

#### Breaking changes
- **Схема базы данных переехала в миграции.** Установщик больше не создаёт таблицы сам: и свежая установка, и обновление существующего сайта идут одним путём — через миграции.

  Миграции лежат в `system/migrations/` (ядро) и `modules/<модуль>/migrations/` (модули). Файл возвращает анонимный класс, унаследованный от `Johncms\Database\Migrations\Migration`, и получает только схему и соединение — ни моделей, ни репозиториев, ни сервисов: миграция обязана отработать без изменений и через несколько лет.

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
