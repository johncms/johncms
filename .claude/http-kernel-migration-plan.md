# План внедрения HTTP-ядра (Request / Response / Kernel)

Дата анализа: 2026-07-22. Ветка: `10.x`.

Цель: получить полноценное HTTP-ядро с унифицированными объектами запроса и **ответа**,
единой точкой отправки ответа и без глобальных сайд-эффектов, чтобы приложение могло
работать в long-running рантайме (FrankenPHP worker mode / RoadRunner) без утечек
состояния между запросами.

## Решение (краткий ADR)

| Вопрос | Решение |
|---|---|
| Основа Request/Response | **`symfony/http-foundation`** — вместо текущего PSR-7 (`guzzlehttp/psr7`) |
| `symfony/http-kernel` | **Не тащим целиком.** Реализуем свой `Johncms\Http\Kernel implements HttpKernelInterface, TerminableInterface` |
| Event-стек Symfony (`RouterListener`, `ControllerResolver`, `EventDispatcher`) | Не вводим. Оставляем свои `MiddlewareDispatcher` + `ActionInvoker` |
| Целевой рантайм | **FrankenPHP worker mode / RoadRunner** (process-per-worker). Swoole — non-goal |
| Порядок | Сначала Response и вычистка сайд-эффектов, ядро — потом, request-scope — последним |

Обоснование выбора HttpFoundation вместо PSR-7:

1. Проект уже стоит на `symfony/routing`, `symfony/dependency-injection`, `symfony/console`.
   `RequestContext::fromRequest()` + `UrlMatcher::matchRequest()` убирают кастомный парсинг URI
   из `public/index.php`.
2. HttpFoundation закрывает то, чего PSR-7 не даёт вообще и что болит прямо сейчас:
   сессия как сервис (203 обращения к `$_SESSION`; под worker-рантаймом нативные сессии нерабочи),
   trusted proxies (сейчас `Request::isHttps()` и `Environment::getIpViaProxy()` парсят
   `X-Forwarded-*` вручную — заголовок подделывается), cookie bag,
   `BinaryFileResponse`/`StreamedResponse` для `downloads` и `library`.
3. `symfony/runtime` и бриджи к FrankenPHP/RoadRunner/Swoole принимают именно `HttpKernelInterface` —
   поддержка рантаймов становится вопросом конфигурации, а не своего кода.

Что мы теряем: совместимость со сторонними PSR-15 middleware (у проекта их нет — все 14 middleware свои)
и PSR-7 `UploadedFileInterface`, который протёк в Application/Domain шести модулей. Последнее —
не потеря, а повод убрать зависимость домена от HTTP-типа (см. этап 1c).

## Карта текущего состояния

Поток запроса сейчас:

```
public/index.php
  ├─ ручная нормализация URI (rawurldecode, rtrim, спецкейс /forum/index.php)
  ├─ SymfonyRouteMatcher::dispatch($_SERVER['REQUEST_METHOD'], $uri) → RouteMatchResult
  ├─ new UserStat($container)                         // сайд-эффект
  ├─ $request->setCurrentRouteParams($match->params)   // мутация иммутабельного PSR-7 объекта
  ├─ MiddlewareDispatcher::dispatch(...) → mixed
  │    └─ ActionInvoker::invoke($controller, $method, $vars) → string|null
  ├─ echo $result                                      // единственный «эмиттер»
  └─ EmailSender::send() если ! USE_CRON               // сайд-эффект после ответа
```

Ключевые файлы:

* `public/index.php` — фронт-контроллер
* `system/bootstrap.php` — `session_start()`, `ob_start('ob_gzhandler')`, `define()`, глобальные `$page`/`$start`
* `system/src/System/Http/Request.php:23` — `extends GuzzleHttp\Psr7\ServerRequest`
* `system/src/System/Http/RequestFactory.php` — `Request::fromGlobals()` (использует `getallheaders()`)
* `system/src/System/Http/Session.php` — фасад над `$_SESSION` (**уже есть — переиспользуем**)
* `system/src/System/Http/Environment.php:50` — `getIp()` / `getIpViaProxy()` вручную
* `system/src/Router/MiddlewareDispatcher.php`, `MiddlewareInterface.php`, `SymfonyRouteMatcher.php`
* `system/src/Http/Controller/ActionInvoker.php` — де-факто ArgumentResolver
* `system/helpers.php:71` — `pageNotFound()` (`echo` + `exit`)
* `system/helpers.php:195` — `redirect()` (`header('Location')` + `exit`)
* `system/src/Container/PSRContainerFactory.php:12` — статический синглтон контейнера
* `system/config/services.php` — `Request`, `User`, `Render`, `NavChain`, `Translator` — синглтоны

Объём долга:

| Показатель | Кол-во |
|---|---|
| Файлов с `use Johncms\System\Http\Request` | 179 |
| Контроллеров | 221 |
| Вызовов `redirect()` (хелпер) | 176 |
| Вызовов `pageNotFound()` (хелпер) | 31 |
| `throw new PageNotFoundException` | 6 |
| `header(...)` напрямую | 67 |
| `exit` / `die` | 62 |
| `http_response_code()` | 104 |
| `setcookie()` | 13 |
| Обращений к `$_SESSION` | 203 |
| Обращений к суперглобалам всего | 254 в 60 файлах |
| Классов Response | **0** |

Использование API `Request` (что придётся сохранить в шиме):

| Метод | Вызовов | Метод | Вызовов |
|---|---|---|---|
| `getPost` | 405 | `getServer` | 8 |
| `getQuery` | 133 | `getCookie` | 5 |
| `getMethod` | 51 | `getQueryParams` | 3 |
| `getParsedBody` | 22 | `getQueryString` | 2 |
| `getUploadedFiles` | 15 | `getBody` | 2 |
| | | `isHttps` / `getUri` / `getCurrentRouteParams` | по 1 |

## Целевая архитектура

```
public/index.php  (≈5 строк)
  └─ Kernel::handle(Request) : Response
       ├─ RequestContext::fromRequest() → UrlMatcher::matchRequest()
       ├─ request-scope: set(Request), reset(Render|NavChain|Translator|User)
       ├─ MiddlewareDispatcher (Request, callable $next) : Response
       ├─ ActionInvoker → Controller : Response
       └─ ExceptionHandler: PageNotFoundException → 404, HttpRedirectException → 302
  └─ $response->send()
  └─ Kernel::terminate(Request, Response)   // UserStat, EmailSender, отложенные задачи
```

Скелет ядра:

```php
final class Kernel implements HttpKernelInterface, TerminableInterface
{
    public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): Response;
    public function terminate(Request $request, Response $response): void;
}
```

Правило слоёв: HTTP-типы (`Request`, `Response`) допустимы **только** в контроллерах и middleware.
Application/Domain не должны знать о них — сейчас это нарушено `UploadedFileInterface` в юзкейсах
(см. `.agents/architecture.md`).

## Принципы миграции

1. **Каждый коммит оставляет CMS работоспособной и деплоябельной** (стратегия 10.x).
2. **Переходный контракт**: ядро принимает от контроллера `Response|string|null`.
   Строка оборачивается в `Response` автоматически → 221 контроллер мигрирует по одному,
   а не одним коммитом.
3. **Хелперы вместо массовых правок**: `redirect()` и `pageNotFound()` переводим на исключения —
   207 вызовов продолжают работать как есть, но без `exit`. Это самый дешёвый способ
   снять большую часть сайд-эффектов.
4. Семантика `getQuery`/`getPost` (`trim` + `filter_var`, `false → null → default`) сохраняется
   **1:1** — 538 вызовов, любое расхождение в фильтрации даёт баги и дыры.
5. Перед каждым коммитом — `sh .agents/scripts/verify.sh` и `/review-self`.

## Этапы

Статус: `—` не начато, `🚧` в работе, `✅` готово.

### — Этап 0. Подготовка (S)

* Добавить `symfony/http-foundation` в `composer.json` (PSR-7 пока остаётся — удаляется в 1d).
* Тесты «поведение хелперов»: `redirect()`, `pageNotFound()`.
* Характеризационные тесты входных данных — фиксируют контракт, который обёртка из 1a обязана
  сохранить: `trim` (переезжает в middleware), пустая строка vs отсутствующий ключ,
  нечисловое значение при `FILTER_VALIDATE_INT` (тихий дефолт — сохраняется мягким режимом
  `bodyInt()`/`queryInt()`), массив в скалярном параметре (`?id[]=1` — сейчас проходит,
  станет `BadRequestException` → 400; это единственное намеренное изменение поведения).
  Тесты пишутся против нынешнего `Request`, затем переиспользуются для обёртки.
  Без них этапы 1 и 2 непроверяемы.
* Зафиксировать в `.agents/` правило: HTTP-типы только в контроллерах/middleware.

**Готово, когда**: тесты на текущее поведение зелёные и падают при подмене реализации.

### — Этап 1. Смена базы Request (M)

**1a. Тонкая обёртка `Johncms\Http\Request extends Symfony\Component\HttpFoundation\Request`.**
`filterVar()` не переносим.

Решено 2026-07-23. Обёртка оправдана: JohnCMS — конечный продукт с 221 контроллером и аудиторией
авторов модулей, для которых `$request->body('message')` читается лучше, чем
`$request->getPayload()->getString('message')`. Это путь Laravel (`Illuminate\Http\Request`
поверх Symfony), и он совместим с long-running рантаймами.

Цена и механика (проверено по исходникам v7.4.14):

* `Request::setFactory(callable)` (`Request.php:463`) — официальный механизм подмены класса;
  `createFromGlobals()` идёт через `createRequestFromFactory()` (`Request.php:289`). Под FrankenPHP
  worker mode наследник создаётся нативно, без конвертации.
* `createFromBase()` в Symfony **нет** (это метод Laravel). Для RoadRunner/Swoole, где бридж строит
  `Symfony\...\Request` из PSR-7, нужен свой адаптер, собирающий наследника из бэгов (~10 строк,
  этап 6).
* В DI регистрируется под `Johncms\Http\Request::class` **плюс алиас**
  `Symfony\Component\HttpFoundation\Request::class` на тот же экземпляр — чтобы код,
  типизированный на базовый класс, тоже работал.

**Устав обёртки** (нарушение — повод завернуть ревью):

1. **Никаких методов на `get*`** — вся территория `get*` принадлежит Symfony. Это правило снимает
   класс конфликтов, который уже случился: текущий `getQueryString()` перекрывает родительский метод.
2. **Никакого своего состояния.** `setCurrentRouteParams()` не переносится: параметры маршрута
   живут в `$request->attributes`. Мутабельный запрос несовместим с worker-режимом.
3. **Только тонкие делегаты** в `getPayload()` / `query`. Никаких `$filter` / `$options`
   в сигнатурах, никакой бизнес-логики.
4. **Не дублируем то, что уже есть** в Symfony (`isSecure()`, `isXmlHttpRequest()`, `getClientIp()`).

Набор методов — ровно под паттерны, найденные в коде:

```php
final class Request extends Symfony\Component\HttpFoundation\Request
{
    public function body(string $key, string $default = ''): string;      // 411 вызовов
    public function bodyInt(string $key, int $default = 0): int;          // 101 вызов
    public function bodyList(string $key): array;                         // users, post_ids, ids
    public function bodyInts(string $key): array;                         // attached_files, 10 мест
    public function hasBody(string $key): bool;                           // чекбоксы
    public function queryParam(string $key, string $default = ''): string;
    public function queryInt(string $key, int $default = 0): int;
    public function queryInts(string $key): array;
    public function isPost(): bool;
}
```

Имена `body*` выбраны сознательно: `post()` в HTTP-коде читается как глагол («выполнить POST»),
а `body*` — существительное-источник, покрывает и формы, и 16 JSON-эндпоинтов, и точно
соответствует симфонийскому `getPayload()`. `queryParam` вместо `query` — потому что
`$request->query` уже занято свойством-бэгом.

Почему методы висят на `Request`, а не на своём бэге: `InputBag` объявлен `final` (`InputBag.php:24`),
а свойства `$request->request` / `$request->query` жёстко типизированы им (`Request.php:101,108`) —
подменить бэг своим наследником нельзя.

*Как приведение типов сделано в HttpFoundation* (проверено по исходникам v7.4.14,
`InputBag.php`, `ParameterBag.php`):

* `InputBag::get()` возвращает **только скаляр или null**; массив в значении → `BadRequestException`
  («Input value "x" contains a non-scalar value»). Это защита от подмены типа через `?id[]=1`.
* Типизированные геттеры: `getString()`, `getInt()`, `getBoolean()`, `getEnum()`, `getAlnum()`,
  `getDigits()`. `getInt()` = `filter($key, $default, FILTER_VALIDATE_INT, ['flags' => FILTER_REQUIRE_SCALAR])`.
* `InputBag::filter()` — обёртка над `filter_var`, всегда добавляет `FILTER_NULL_ON_FAILURE`;
  если фильтр не прошёл и флаг не был задан явно — **бросает `BadRequestException`**, а не
  возвращает дефолт (`InputBag.php:150`).

Чем это отличается от нашего `filterVar()` — три содержательных различия:

| | `Johncms\System\Http\Request::filterVar()` | HttpFoundation |
|---|---|---|
| Провал фильтра | тихо возвращает `$default` | `BadRequestException` → 400 |
| `trim()` | делает всегда | **не делает** |
| Массив в значении | рекурсивно фильтрует поэлементно | `BadRequestException`, если нет `FILTER_REQUIRE_ARRAY` |

Плюс латентный баг в нашей версии: `if (false !== $result)` — корректный `false` от фильтра
(например `FILTER_VALIDATE_BOOL` для `"0"`/`"off"`) подменяется дефолтом. Сейчас не стреляет,
потому что `FILTER_VALIDATE_BOOL` в коде не используется.

*Почему `filterVar()` не нужен вообще.* Разбор всех 551 вызова `getQuery/getPost/getCookie/getServer`:

| Что используется | Вызовов | Метод обёртки | Что внутри |
|---|---|---|---|
| без фильтра, скаляр (просто строка) | 411 | `body()` / `queryParam()` | `getString()` |
| `FILTER_VALIDATE_INT`, скаляр | 101 | `bodyInt()` / `queryInt()` | `getInt()` в мягком режиме |
| `FILTER_VALIDATE_INT`, **массив** (`attached_files`) | 10 | `bodyInts()` | `filter(…, FILTER_VALIDATE_INT, ['flags' => FILTER_REQUIRE_ARRAY])` |
| без фильтра, **массив** (`users`, `post_ids`, `ids`) | 3 | `bodyList()` | `all($key)` |
| `FILTER_SANITIZE_FULL_SPECIAL_CHARS` / `_SPECIAL_CHARS` | 12 | — | переносится как есть, см. 1a-ter |
| `FILTER_VALIDATE_IP` | 4 | — | уходит в `getClientIp()` (этап 5) |

То есть реально существуют три паттерна — строка, int и список int; девяти методов обёртки хватает
на все 551 вызов. Универсальный `$filter` + `$options` в сигнатуре не нужен.

*«Мягкий режим» и политика невалидного ввода.* `getInt()` бросает `BadRequestException` на
`?id=abc`, текущий код возвращает дефолт. Обёртка — единственное место, где эта политика задаётся:
`bodyInt()` / `queryInt()` ловят `BadRequestException` и возвращают `$default`, сохраняя текущее
поведение на всех 111 местах. Строгая семантика остаётся доступной через бэги напрямую
(`$request->query->getInt('id')`) — включается точечно там, где 400 действительно уместен.
Это снимает необходимость решать вопрос «400 везде или нигде» до миграции.

Про массивы отдельно: 13 вызовов (`forum` — 6, `guestbook` — 3, `news` — 2, плюс `GuestbookForm.php:30`)
полагаются на рекурсивную ветку `filterVar`. В HttpFoundation без явного `FILTER_REQUIRE_ARRAY`
будет `BadRequestException`, поэтому каждое такое место правится вручную — под `sed` они не попадают.
`ParameterBag::all($key)` возвращает `[]`, если ключа нет, и бросает `BadRequestException`,
если значение не массив (`ParameterBag.php:51`) — то есть подмена `users[]` на скаляр отсекается сама.

Отображение вызовов:

| Текущий | Замена |
|---|---|
| `$request->getQuery('x')` | `$request->queryParam('x')` |
| `$request->getQuery('x', null, FILTER_VALIDATE_INT)` | `$request->queryInt('x')` |
| `$request->getPost('x', '')` | `$request->body('x')` |
| `$request->getPost('x', 0, FILTER_VALIDATE_INT)` | `$request->bodyInt('x')` |
| `$request->getPost('x', [], FILTER_VALIDATE_INT)` | `$request->bodyInts('x')` |
| `$request->getPost('x', [])` | `$request->bodyList('x')` |
| `$request->getPost('x') !== null` (чекбоксы) | `$request->hasBody('x')` |
| `$request->getMethod() === 'POST'` | `$request->isPost()` |
| `$request->getCookie('x')` / `getServer('x')` | `$request->cookies->getString('x')` / `$request->server->getString('x')` — 13 вызовов, в обёртку не выносим |
| `$request->getParsedBody()` / `getQueryParams()` | `$request->request->all()` / `$request->query->all()` |
| `$request->getUri()` | `getUri(): string` вместо `UriInterface` — 1 вызов, правится точечно |
| `$request->getBody()` | `getContent()` — 2 вызова |
| `$request->isHttps()` | `isSecure()` + trusted proxies (1b) |
| `$request->get/setCurrentRouteParams()` | `$request->attributes` (пункт 2 устава) |
| `$request->getQueryString($remove, $add)` | вынести в `Johncms\Http\QueryStringBuilder` — 2 вызова, оба в `Pagination.php:79,82`; имя конфликтует с родительским методом |

Замена почти целиком механическая: `getPost(` → `body(`, `getQuery(` → `queryParam(`, затем проход
по строкам с `FILTER_VALIDATE_INT` → `bodyInt`/`queryInt` с удалением лишних аргументов.
Вручную правятся 13 массивных вызовов и 20 вызовов из нижней части таблицы. Заодно уходят
избыточные `(int)` / `(string)` / `(array)` касты — методы обёртки уже типизированы, а по стилю
проекта такие касты запрещены.

*Про `getPayload()`.* `Request::getPayload()` (`Request.php:1588`) возвращает `$request->request`
для form-encoded тела и JSON-декод тела в остальных случаях — поэтому методы `body*()` работают
одинаково и для форм, и для 16 JSON-эндпоинтов. **Важно для реализации**: метод клонирует бэг
при каждом вызове, поэтому внутри обёртки его результат кэшируется в приватном свойстве
(`?InputBag $payload`), а не дёргается на каждый `body()`. Это единственное допустимое
состояние в обёртке — кэш, а не данные запроса.

**1a-quater. Request — аргументом действия, а не через конструктор.**

Сервисы в `system/config/services.php` регистрируются без `shared: false`, то есть контроллеры —
синглтоны. Под FPM это безразлично, но в worker-режиме контроллер, получивший `Request`
в конструктор, навсегда запомнит первый запрос. Поэтому соглашение для нового и мигрируемого кода:

```php
final readonly class ReplyController
{
    public function __construct(
        private ControllerContext $context,
        private Render $render,
        // Request здесь больше нет
    ) {}

    public function __invoke(Request $request): Response { … }
}
```

`ActionInvoker` это уже умеет: class-based аргументы действия резолвятся из контейнера
(`ActionInvoker.php:36`). Так request-scope решается сам собой — без `shared: false`
и без synthetic-хаков на этапе 5, и код совпадает с каноническим Symfony-стилем.

Переносить `Request` из конструктора в сигнатуру действия можно постепенно, модуль за модулем,
но к концу этапа 5 в конструкторах контроллеров его быть не должно.

**1a-bis. `trim()` — единственное, что придётся сохранить осознанно.**
Сейчас `filterVar()` тримит **все** 551 значение; HttpFoundation не тримит. Молча потерять это —
пробелы в логинах, поиске, названиях. Решение: middleware `TrimStringsMiddleware`, рекурсивно
тримящий `$request->request` и `$request->query` (по образцу Laravel `TrimStrings`).
Поведение сохраняется в одном явном месте и работает даже при прямом обращении к бэгам.
Пароли сейчас тоже тримятся (`album/EditAlbumController.php:131`,
`registration/RegistrationController.php:53`, `album/ShowAlbumController.php:36`) — поведение
консистентно между регистрацией и логином, поэтому оставляем как есть; список исключений
у middleware предусмотреть, но пустой.

**1a-ter. `FILTER_SANITIZE_*SPECIAL_CHARS` — отдельная задача, не входит в этот план.**
12 вызовов — это экранирование **на входе**, прямое нарушение `.agents/escaping.md`:

* `modules/profile/.../EditProfileController.php:133-149` — 11 полей профиля
  (`imname`, `live`, `about`, `mibile`, `mail`, `skype`, `jabber`, `www`, `status`, `name`, `sex`)
* `modules/login/.../LogoutController.php:41` — `HTTP_REFERER`
* `system/src/System/Http/Environment.php:85`

Просто убрать фильтр нельзя: данные уже лежат в БД экранированными, а шаблон
`modules/profile/templates/edit.phtml:193` выводит их **без** экранирования именно поэтому.
Нужен связный набор: снять фильтр + добавить экранирование в шаблоны + миграция уже испорченных
данных. Заводится отдельным планом. **На этапе 1 эти 12 вызовов переносятся как есть** —
через `$bag->filter($key, $default, FILTER_SANITIZE_FULL_SPECIAL_CHARS)`.

**1b. Фабрика.** Новый `Johncms\Http\RequestFactory`:

```php
Request::setFactory(static fn (...$args) => new \Johncms\Http\Request(...$args));
Request::setTrustedProxies($config['trusted_proxies'], $config['trusted_headers']);

return \Johncms\Http\Request::createFromGlobals();   // вернёт уже нашего наследника
```

`setFactory()` ставится один раз при бутстрапе (в worker-режиме — тоже один раз, до цикла).
Новые ключи конфига `trusted_proxies` / `trusted_headers`. Уходит зависимость
от `getallheaders()`, которой нет в CLI/worker.

В `system/config/services.php` сервис регистрируется под `Johncms\Http\Request::class`,
плюс алиас `Symfony\Component\HttpFoundation\Request::class` на тот же экземпляр.
На этапе 5 сервис станет `synthetic()`, экземпляр будет подставлять ядро, а фабрика останется
только для классического FPM-режима.

**1c. Отвязать домен от HTTP-типа файлов.** Ввести `Johncms\Http\UploadedFileDTO`
(имя, mime, размер, временный путь, код ошибки) и `UploadedFileMapper`. Переписать:

* `modules/album/src/Application/UseCases/UploadPhotoUseCase.php`
* `modules/mail/src/Application/{Controllers/WriteController,DTO/SendMessageCommand,Services/MailFileService,UseCases/SendMessageUseCase}.php`
* `modules/profile/src/Application/UseCases/{UploadAvatarUseCase,UploadPhotoUseCase}.php`
* `modules/forum/src/Application/Controllers/UploadFileController.php`, `modules/guestbook/.../UploadFileController.php`

Это единственная реально ломающаяся часть API (`getStream()`/`moveTo()` → `move()`), 15 точек.

**1d. Массовая замена и удаление старого namespace.**

Версия мажорная — deprecated-периода нет. Старый класс удаляется в том же коммите, что и замена
ссылок. Заодно расселяется **весь** `Johncms\System\Http\*`: namespace `Johncms\Http\` уже
существует (`PageMeta`, `PublicUrlResolver`, `Controller\*`, `Pagination\*`), держать HTTP-классы
в двух местах смысла нет.

Судьба классов (235 `use`-строк суммарно):

| Класс | `use` | Что с ним |
|---|---|---|
| `Request` | 193 | переезжает в `Johncms\Http\Request` и **переписывается** как наследник HttpFoundation (1a) |
| `RequestFactory` | 1 | переезжает и переписывается под `setFactory()` (1b) |
| `Session` | 22 | переносится как есть в `Johncms\Http\Session`, переписывается на этапе 4 |
| `Environment` | 19 | переносится как есть в `Johncms\Http\Environment`, `getIp*()` растворяется на этапе 5 |

После этого каталог `system/src/System/Http/` исчезает целиком; namespace `Johncms\System\`
остаётся жить (`View`, `Users`, `i18n`, `Utility`).

Процедура (один атомарный коммит — переименование класса не бьётся на части):

```bash
# 1. все четыре класса переезжают в один namespace
git mv system/src/System/Http/Request.php        system/src/Http/Request.php
git mv system/src/System/Http/RequestFactory.php system/src/Http/RequestFactory.php
git mv system/src/System/Http/Session.php        system/src/Http/Session.php
git mv system/src/System/Http/Environment.php    system/src/Http/Environment.php

# 2. namespace во всём репозитории — одним проходом
grep -rl 'Johncms\\System\\Http\\' --include=*.php . \
    --exclude-dir=vendor --exclude-dir=node_modules \
  | xargs sed -i 's/Johncms\\System\\Http\\/Johncms\\Http\\/g'

# 3. вызовы — сахар обёртки (1a)
#    getPost( → body(, getQuery( → queryParam(, затем проход по FILTER_VALIDATE_INT
```

Содержимое `Request.php` и `RequestFactory.php` переписывается по 1a/1b — от прежней реализации
не остаётся ничего, кроме имени класса. `sed` из шага 2 покрывает и `use`, и FQCN вне `use`,
и текст deprecation-сообщения в хелпере.
Точки вне `use`, которые обязаны быть проверены глазами после замены:

* `system/helpers.php:53` — текст deprecation для `di('route')` (упоминает FQCN в строке)
* `system/helpers.php:58` — `di(\Johncms\System\Http\Request::class)->getCurrentRouteParams()`
* `public/index.php:55` — `$container->get(\Johncms\System\Http\Request::class)`
* `system/config/services.php` — `use`-блок, `$services->set(Request::class)->factory(service(RequestFactory::class))`
  и новый алиас на `Symfony\Component\HttpFoundation\Request::class` (1b)
* PSR-4 путь: и старый, и новый каталог внутри `system/src`, поэтому `$services->load('Johncms\\', ROOT_PATH . 'system/src')`
  и autoload в `composer.json` не меняются. Но `composer dump-autoload` после `git mv` обязателен.

Тесты (8 файлов ссылаются на `Request`):

* `tests/Unit/Router/MiddlewareDispatcherTest.php:19,54,83,107` — `new Request('GET', '/forum')` это
  **PSR-7 сигнатура**, у HttpFoundation конструктор другой. Заменить на `Request::create('/forum', 'GET')`.
  Это единственное место, где `sed` недостаточно.
* 10 `createMock(Request::class)` в `PaginationTest`, `PaginationGuardTest`, `RequestContextFactoryTest`,
  `SymfonyRouteMatcherTest`, `GuestbookModeTest`, `GuestbookFormTest`, `GuestbookAccessMiddlewaresTest`
  — переживают смену базового класса без правок, но прогнать обязательно.

Финальная зачистка (тем же коммитом или следующим):

* `composer why guzzlehttp/psr7` — убедиться, что пакет больше никому не нужен транзитивно,
  затем убрать из `composer.json`.
* `grep -rn 'System\\Http\|System/Http'` по всему репозиторию (включая `.agents/`, `docs/`, `.claude/`)
  — не должно остаться ни одного вхождения, кроме самого этого плана: в разделе
  «Карта текущего состояния» старые пути упомянуты намеренно, как описание того, что было.

**Готово, когда**: `grep -r 'Psr\\Http\\Message'` и `grep -r 'System\\Http'` по репозиторию пусты,
`guzzlehttp/psr7` удалён из `composer.json`, `sh .agents/scripts/verify.sh` зелёный.

### — Этап 2. Response (L) — основной объём работы

**2a. Хелперы на исключения (даёт максимум охвата за минимум правок).**

* `redirect(string $url, int $status = 302)` → `throw new HttpRedirectException($url, $status)`.
  176 вызовов не меняются, `exit` из хелпера уходит.
* `pageNotFound(...)` → `throw new PageNotFoundException(...)` (класс уже есть). 31 вызов.
* Ядро/`index.php` ловит оба и формирует `RedirectResponse` / 404-`Response`.

**2b. Переходный контракт.** Ядро принимает `Response|string|null`, строку оборачивает в `Response`.

**2c. Вычистка прямых сайд-эффектов** — помодульно, от простого к сложному:

| Модуль | `header('Location')` | `exit`/`die` | Приоритет |
|---|---|---|---|
| `downloads` | 11 | 11 | 1 (+ `LoadFileController` → `BinaryFileResponse`) |
| `library` | 10 | 13 | 2 (+ `DownloadArticleController` → `StreamedResponse`) |
| `news` | 7 | 12 | 3 (в т.ч. `exit($exception->getMessage())` — заменить на нормальный ответ) |
| `help` | 5 | 3 | 4 |
| `redirect` | 4 | 4 | 5 |
| `mail` / `login` | по 3 | по 3 | 6 |
| `notifications` / `forum` / `album` / `admin` / `collections` | 1–2 | 1–2 | 7 |
| `system` (`Comments.php`, `BanIP.php`) | 5 | 5 | 8 (легаси, последним) |

Плюс: 16 `json_encode` + `header('Content-Type: application/json')` → `JsonResponse`;
104 `http_response_code()` → статус в `Response`; 13 `setcookie()` → `$response->headers->setCookie()`.

**2d. Единая точка отправки.** `echo $result` в `public/index.php` заменяется на `$response->send()`.
`ob_start('ob_gzhandler')` из `system/bootstrap.php` удаляется — сжатие отдаём веб-серверу.

**Готово, когда**: ни один контроллер не пишет в вывод напрямую; `grep -c 'exit\|die('` по `modules/` — 0.

### — Этап 3. Kernel (M)

* `Johncms\Http\Kernel implements HttpKernelInterface, TerminableInterface`.
* Нормализация URI и спецкейс `/forum/index.php` переезжают из `public/index.php` в ядро
  (или в отдельный `RequestNormalizer`) — сейчас это анонимная функция во фронт-контроллере.
* `SymfonyRouteMatcher` переводится на `matchRequest(Request)` + `RequestContext::fromRequest()`.
* `MiddlewareInterface::handle()` меняет возвращаемый тип `mixed` → `Response` (14 реализаций).
* Обработка исключений (404, redirect, 405, 500) — в ядре, а не в `index.php` и не в хелперах.
* `UserStat` и `EmailSender::send()` переезжают в `terminate()`.
* `public/index.php` схлопывается до создания ядра, `handle()`, `send()`, `terminate()`.

**Готово, когда**: `public/index.php` ≤ 15 строк, вся HTTP-логика в `system/src/Http/`.

### — Этап 4. Сессия (M)

Существующий `Session` (после этапа 1d — `Johncms\Http\Session`) — уже готовая точка абстракции,
менять вызовы дважды не придётся.

* Переписать его поверх `Symfony\Component\HttpFoundation\Session\SessionInterface`
  (сохранив `get/set/has/remove/flash/getFlash`; у HttpFoundation flash-bag уже есть).
* `session_start()` из `system/bootstrap.php` убрать — сессию стартует ядро.
* Мигрировать 203 прямых `$_SESSION` на сервис. Порядок по объёму:
  `admin` (59), `news` (21), `collections` (20), `system` (19), `downloads` (16), `profile` (15),
  `contacts` (12), `consent` (9), `forum` (8), остальные — по 2–6.
* Storage делаем сменным (native → redis) — предусловие для worker-рантайма.

**Готово, когда**: `grep -c '\$_SESSION'` по `modules/` и `system/src/` — 0 (кроме самого storage).

### — Этап 5. Request-scope в контейнере (M) — здесь решается long-running

Проблема: `PSRContainerFactory::$containerInstance` статичен, а в контейнере синглтонами лежат
объекты, живущие ровно один запрос. При живом воркере состояние протечёт в следующий запрос:

| Сервис | Что копит |
|---|---|
| `Request` | весь запрос целиком |
| **контроллеры** | все зарегистрированы без `shared: false`, то есть синглтоны: инжектированный в конструктор `Request` протухает вместе с ними |
| `Render` (`extends Plates Engine`) | `addFolder()` + `addData()` (208 вызовов) — `title` предыдущего запроса утечёт в следующий |
| `NavChain` | `$items` — хлебные крошки накапливаются |
| `Translator` | домены, регистрируемые из конструкторов контроллеров |
| `User` / `System\Users\User` | текущий пользователь |

Решение:

* `Request` в контроллерах — аргументом действия, а не через конструктор (соглашение из 1a-quater).
  Это снимает проблему синглтон-контроллеров без `shared: false`; к концу этапа в конструкторах
  контроллеров `Request` быть не должно.
* Объявить оставшиеся сервисы `->synthetic()` в `system/config/services.php`,
  ядро на каждый запрос делает `$container->set(...)` со свежим экземпляром.
* Остальным, копящим состояние, — `Symfony\Contracts\Service\ResetInterface` + `reset()` в `Kernel::handle()`.
* Глобальные `$page` / `$start` из `system/bootstrap.php` и `$GLOBALS['old']`
  (`modules/downloads/.../IndexController.php:57`) — убрать.
* `di()` запретить в новом коде (в существующем — постепенно; уже есть deprecation-ветки внутри).
* `Environment::getIp()` / `getIpViaProxy()` → `$request->getClientIp()` с trusted proxies из этапа 1b.

**Готово, когда**: два последовательных `handle()` в одном процессе дают идентичный результат
(тест на изоляцию: прогнать один и тот же запрос дважды в одном процессе и сравнить ответы,
затем два разных запроса и проверить отсутствие протечки `title`/крошек).

### — Этап 6. Рантайм (S)

* `symfony/runtime` + бридж. Начать с **FrankenPHP worker mode** (проще всего: обычный PHP,
  process-per-worker, без корутин) или RoadRunner.
* Под FrankenPHP обёртка из 1a работает нативно: бридж зовёт `createFromGlobals()`, а тот
  уважает `Request::setFactory()`. Для RoadRunner/Swoole, где запрос строится из PSR-7
  через `HttpFoundationFactory`, нужен адаптер `Johncms\Http\Request::fromBase(SymfonyRequest $base)`
  (~10 строк: пересобрать наследника из бэгов базового запроса) — аналога `createFromBase()`
  в Symfony нет.
* Docker-профиль для worker-режима рядом с текущим `php-fpm` — оба режима поддерживаются
  одновременно, классический FPM остаётся дефолтом.
* Прогон нагрузочного теста на утечки памяти (рост RSS воркера по итерациям).

**Готово, когда**: CMS проходит smoke-набор маршрутов в worker-режиме, RSS воркера стабилен.

## Non-goals

* **Не** переезжаем на `symfony/framework-bundle` / полноценную Symfony — потеряем модульную систему.
* **Не** держим PSR-7 и HttpFoundation одновременно через `psr-http-message-bridge` — двойная
  конвертация на каждый запрос и два способа делать одно и то же.
* **Не** целимся в Swoole: корутины несовместимы с Eloquent/PDO, `getid3`, `intervention/image`
  без отдельной обвязки. Если понадобится — бридж тот же `HttpKernelInterface`.
* **Не** вводим event-driven стек HttpKernel (9 слушателей вместо понятного pipeline).
* **Не** начинаем с ядра: без Response оно бесполезно.
* **Не** делаем магических аксессоров в стиле Laravel (`$request->input('x')`, `$request->get('x')`),
  где источник значения неочевиден: у нас источник всегда в имени метода — `body*` или `query*`.
  Сам `Request::get()` в Symfony 7.4 уже задепрекейчен в пользу явных бэгов (`Request.php:754`).
* **Не** наращиваем обёртку сверх девяти методов из 1a без явного решения: устав (никаких `get*`,
  никакого состояния, только тонкие делегаты) существует ровно для того, чтобы она не превратилась
  в божественный объект, каким стал прежний `Request` с его `setCurrentRouteParams()`.
* **Не** чиним `FILTER_SANITIZE_*` (экранирование на входе) в рамках этого плана — отдельная
  задача с миграцией данных (см. 1a-ter).

## Открытые вопросы

1. ~~Deprecated-алиасы для `Johncms\System\Http\*`~~ — **решено 2026-07-22: не делаем.**
   Версия мажорная, старый namespace удаляется сразу (этап 1d). Ломающее изменение фиксируется
   в `CHANGELOG.md` как часть 10.0.

2. ~~Политика невалидного ввода: 400 или тихий дефолт?~~ — **решено 2026-07-23 через обёртку.**
   `bodyInt()` / `queryInt()` работают мягко (дефолт вместо 400), сохраняя текущее поведение
   на всех 111 местах; строгая семантика доступна через бэги напрямую и включается точечно.
   Остаётся частный вопрос: мапить ли `BadRequestException` (он всё равно возможен — например
   `?id[]=1` в скалярном параметре) на нормальную 400-страницу в ядре. Да, на этапе 3.
3. ~~`getQueryString()` и шаблоны~~ — проверено: оба вызова внутри
   `system/src/Http/Pagination/Pagination.php:79,82`, в `themes/` метод не используется.
   Вынос в `QueryStringBuilder` безопасен.

4. Нужен ли `Response` в middleware сразу (этап 3) или допустить `mixed` ещё на один этап.

5. Сессия: сразу выносить storage в Redis или оставить native до этапа 6.

6. Порядок этапов 4 и 5 взаимозаменяем — сессия может быть быстрее вычищена, если параллельно
   идёт работа по другим модулям.
