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
| Порядок | Request → гейт статического анализа → Response → скелет ядра → вычистка сайд-эффектов → достройка ядра → сессия → request-scope → рантайм (уточнено 2026-07-24). На 2026-07-25 пройдено до скелета ядра включительно |
| Управляющий поток (`redirect` / 404) | Исключения `HttpRedirectException` / `PageNotFoundException`, единая точка перехвата в HTTP-слое (2a, готово 2026-07-25) |

Обоснование выбора HttpFoundation вместо PSR-7:

1. Проект уже стоит на `symfony/routing`, `symfony/dependency-injection`, `symfony/console`.
   `RequestContext::fromRequest()` + `UrlMatcher::matchRequest()` убирают кастомный парсинг URI
   из `public/index.php`.
2. HttpFoundation закрывает то, чего PSR-7 не даёт вообще и что болит прямо сейчас:
   сессия как сервис (203 обращения к `$_SESSION`, размазанных по модулям, — абстракции нет,
   storage несменный), trusted proxies (сейчас `Request::isHttps()` и `Environment::getIpViaProxy()`
   парсят `X-Forwarded-*` вручную — заголовок подделывается), cookie bag,
   `BinaryFileResponse`/`StreamedResponse` для `downloads` и `library`.

   *Уточнение 2026-07-24*: под FrankenPHP worker mode (process-per-worker, без корутин — наш
   таргет) **нативные сессии работоспособны**, просто `session_start()` / `session_write_close()`
   должны вызываться на каждый запрос, а не один раз в бутстрапе. Ранее здесь утверждалось
   обратное; это меняло оценку этапа 4 в худшую сторону (казалось, что Redis — предусловие
   воркера; на деле нет, см. открытый вопрос 5).
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
5. Перед каждым коммитом — `sh .agents/scripts/verify.sh`. `/review-self` запускается
   только по явному запросу разработчика (агент сам ревьюверов не поднимает).

## Этапы

Статус: `—` не начато, `🚧` в работе, `✅` готово.

### ✅ Этап 0. Подготовка (S) — завершён (тесты хелперов закрыты в 2a)

* ✅ Добавить `symfony/http-foundation` в `composer.json` (PSR-7 пока остаётся — удаляется в 1d).
  Установлен `v7.4.14`.
* ~~Тесты «поведение хелперов»: `redirect()`, `pageNotFound()`~~ — **перенесены в 2a (решено 2026-07-23).**
  Хелперы вызывают `exit`, а `header()` в CLI-SAPI — no-op, поэтому в текущем виде они не
  тестируются юнит-тестом (тест либо гибнет на `exit`, либо не видит заголовок). Осмысленными
  тесты становятся ровно тогда, когда 2a переведёт хелперы на `HttpRedirectException` /
  `PageNotFoundException` — исключение тривиально проверяется на url/статус/шаблон. Пишутся вместе с 2a.
* ✅ Характеризационные тесты входных данных — фиксируют контракт, который обёртка из 1a обязана
  сохранить: `trim` (переезжает в middleware), пустая строка vs отсутствующий ключ,
  нечисловое значение при `FILTER_VALIDATE_INT` (тихий дефолт — сохраняется мягким режимом
  `bodyInt()`/`queryInt()`), массив в скалярном параметре (`?id[]=1` — сейчас проходит,
  станет `BadRequestException` → 400; это единственное намеренное изменение поведения).
  Тесты пишутся против нынешнего `Request`, затем переиспользуются для обёртки.
  Без них этапы 1 и 2 непроверяемы.
  ✅ Реализовано в `tests/Unit/Http/RequestInputBehaviorTest.php` (12 тестов).
* ✅ Зафиксировать в `.agents/` правило: HTTP-типы только в контроллерах/middleware
  (`.agents/architecture.md`, раздел «Dependency Rules»).

**Готово, когда**: тесты на текущее поведение зелёные и падают при подмене реализации.
✅ Тесты хелперов закрыты вместе с 2a (`tests/Unit/HelpersTest.php`), то есть этап 0 завершён.

### ✅ Этап 1. Смена базы Request (M) — завершён 2026-07-24 вместе с 1e (не закоммичено)

**1a. Тонкая обёртка `Johncms\Http\Request extends Symfony\Component\HttpFoundation\Request`.**
`filterVar()` не переносим.

Статус 1a: ✅ класс обёртки создан — `system/src/Http/Request.php` (девять методов из устава),
контрактные тесты `tests/Unit/Http/RequestWrapperTest.php` (20 тестов) зелёные. Обёртка ещё
**не подключена** в DI и не используется вызывающим кодом — это делается в 1b (фабрика) и 1d
(массовая замена). `bodyInt()`/`queryInt()` реализуют мягкий режим для провала фильтра
(`?id=abc` → default), но массив в скалярном параметре (`?id[]=1`) сознательно пропускается как
`BadRequestException` → 400 (единственное намеренное изменение поведения; закрыто тестами).
Осталось в 1a: `TrimStringsMiddleware` (1a-bis), вынос `getQueryString()` в `QueryStringBuilder`,
перевод контроллеров на Request-аргумент действия (1a-quater, постепенно до конца этапа 5).

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
| `$request->getQueryString($remove, $add)` | ✅ вынесено в `Johncms\Http\QueryStringBuilder` (`build(path, query, remove, add)`); `Pagination` больше не зависит от `Request`, path/query извлекает `PaginationFactory` — единственная точка касания Request, 1d обновит `getUri()->getPath()`/`getQueryParams()` на HttpFoundation-API. Старый `getQueryString()` теперь мёртвый код, удаляется в 1d. Тесты: `QueryStringBuilderTest` + обновлённые `PaginationTest`/`PaginationGuardTest` |

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

Переносить `Request` из конструктора в сигнатуру действия можно постепенно, модуль за модулем,
но к концу этапа 5 в конструкторах контроллеров его быть не должно. На 2026-07-24 `Request`
в конструкторе остаётся у **145 контроллеров из 221**.

**⚠️ Поправка 2026-07-24.** Здесь раньше было написано, что `ActionInvoker` это уже умеет
и «request-scope решается сам собой — без `shared: false` и без synthetic-хаков на этапе 5».
Это **неверно**: `ActionInvoker.php:36` резолвит class-based аргумент через
`$this->container->get($type->getName())`, то есть достаёт **контейнерный** экземпляр `Request`,
а не тот, что течёт по пайплайну. Перенос `Request` в сигнатуру действия сам по себе ничего
не изолирует — под воркером экземпляр протухнет ровно так же, просто на уровень позже.

Сегодня расхождения нет только потому, что объект физически один и тот же. На этом же неявном
инварианте держится `TrimStringsMiddleware`: он мутирует бэги **in place**
(`TrimStringsMiddleware.php:47-50`), и контроллер видит тримленный ввод лишь потому, что читает
тот же объект. Как только ядро начнёт создавать `Request` само (этап 3), инвариант ломается —
и ломается тихо: тримминг просто перестанет доезжать до контроллеров, без ошибки.

Починка — в 1e (ниже), до этапа 3. После неё обещание 1a-quater становится правдой, а
`synthetic()` на этапе 5 нужен только для кода, который всё ещё берёт `Request` из конструктора.

**1a-bis. `trim()` — единственное, что придётся сохранить осознанно.**
Сейчас `filterVar()` тримит **все** 551 значение; HttpFoundation не тримит. Молча потерять это —
пробелы в логинах, поиске, названиях. Решение: middleware `TrimStringsMiddleware`, рекурсивно
тримящий `$request->request` и `$request->query` (по образцу Laravel `TrimStrings`).
Поведение сохраняется в одном явном месте и работает даже при прямом обращении к бэгам.
Пароли сейчас тоже тримятся (`album/EditAlbumController.php:131`,
`registration/RegistrationController.php:53`, `album/ShowAlbumController.php:36`) — поведение
консистентно между регистрацией и логином, поэтому оставляем как есть; список исключений
у middleware предусмотреть, но пустой.

Статус 1a-bis: ✅ создан `system/src/Http/Middleware/TrimStringsMiddleware.php` (тримит `$request->request`
и `$request->query`, рекурсивно; `EXCEPT` пуст и применяется только к top-level ключам), тесты
`tests/Unit/Http/Middleware/TrimStringsMiddlewareTest.php` (7 тестов) зелёные. Класс **не подключён**
и **пока не** `implements MiddlewareInterface` — интерфейс всё ещё типизирован на старый PSR-7 Request
(несвязанный класс, добавление `implements` сейчас = фатальная несовместимость сигнатуры). Подключение
в глобальный пайплайн + `implements` — в 1d (сигнатура `handle()` уже совпадает с интерфейсом).
Область тримминга сознательно совпадает с legacy: только form-body (`$_POST`) и query (`$_GET`);
сырое JSON-тело вне области (HttpFoundation отдаёт его свежим bag через `getPayload()`, legacy его тоже
не тримил).

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

Статус 1b: ✅ создан `system/src/Http/RequestFactory.php` (`final readonly`, метод `configure()` +
`__invoke()`), конфиг `config/autoload/http.global.php` (ключ `http.trusted_proxies` пуст по
умолчанию, `http.trusted_headers` = `null` → стандартный набор `X-Forwarded-*`), тесты
`tests/Unit/Http/RequestFactoryTest.php` (5 тестов) зелёные. Guard на не-массивный
`trusted_proxies` (защита от `TypeError` при опечатке оператора) и предупреждение о
host-header injection через `X-Forwarded-Host` добавлены по итогам self-review.
Класс **не подключён** в `services.php` — живой сервис `Request` всё ещё старый PSR-4 класс;
переключение сервиса и алиас `Symfony\...\Request::class` — атомарно в 1d, иначе коммит сломает
179 файлов, зовущих `getQuery`/`getPost`/`setCurrentRouteParams`.

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

Статус 1c: ✅ **готово.** Создан `system/src/Http/UploadedFileDTO.php` (`final readonly`: `clientName`,
`mimeType`, `size`, `tmpPath`, `error`; методы `isValid()` и `moveTo()` — `move_uploaded_file()` с
fallback на `rename()` для worker/тестов) и `system/src/Http/UploadedFileMapper.php` (`fromPsr()` —
единственная граница чтения HTTP-типа; в 1d меняется только тело метода). DTO исключён из автовайринга
в `services.php` (скалярный конструктор). Тесты: `UploadedFileDTOTest` + `UploadedFileMapperTest`.

Мигрированы (use cases/services/DTO принимают DTO; контроллеры мапят через `UploadedFileMapper`):
`mail` (SendMessageCommand, SendMessageUseCase, MailFileService, WriteController), `album`
(UploadPhotoUseCase + Controller), `profile` (UploadAvatar/UploadPhotoUseCase + Avatar/PhotoController),
`forum` (AttachFileToPostUseCase — сигнатура `array` → `?UploadedFileDTO`, AddFileController,
UploadFileController), `guestbook` (UploadFileController). Application/Domain всех модулей теперь свободен
от PSR-7/Guzzle upload-типов; PSR-7 остался только в контроллерах (HTTP-слой, `instanceof`-гард перед
маппингом). Каждый контроллер проверен резолвом из DI-контейнера.

**Не входило в 1c (вынесено на 1d):** `system/src/Files/FileStorage.php` всё ещё использует
`GuzzleHttp\Psr7\UploadedFile` + `di(Request)` внутри (`saveFromRequest()`); CKEditor-контроллеры
forum/guestbook делают через него фактическое сохранение. Инфраструктурный сервис, закрывается в 1d
вместе с cutover Request. Контроллеры downloads/library/news тоже используют `getStream()`/`moveTo()`
напрямую внутри контроллеров (HTTP-слой) — мигрируются в 1d.

**1d. ✅ ГОТОВО (2026-07-24).** Cutover выполнен, self-review (architecture/security/php-quality) + gate
зелёные. Урок: обёртка типизирует `body/queryParam` как `string $default` (старые `getQuery/getPost` —
`mixed`), поэтому механический sed оставил ~60 вызовов с int/null/false-дефолтом → фатальный `TypeError`
под strict_types. Не ловится gate'ом (psalm отключён — стухший `psalm.xml.dist`; а `errorLevel=8`
подавляет `InvalidArgument`). Все исправлены на `bodyInt/queryInt`/`hasBody`/`query->has`/`(string)`.
Ниже — исходная процедура.

**Массовая замена и удаление старого namespace.**

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

**1e. ✅ ГОТОВО (2026-07-24, не закоммичено).** Заведено по итогам ревью плана, выполнено
в тот же день; self-review (architecture/security/php-quality) + gate зелёные.

1. ✅ **`ActionInvoker` получает текущий `Request` параметром, а не из контейнера.**
   Причина — в поправке к 1a-quater выше. `invoke()` принимает `Request $request`, а
   `resolveArguments()` спец-кейсит его через `$request instanceof $typeName` (покрывает и
   обёртку, и хинт на базовый HttpFoundation-класс) перед обращением к контейнеру.
   Тесты: `tests/Unit/Http/Controller/ActionInvokerTest.php` (8 тестов).

2. ✅ **Удалена мёртвая ветка legacy-include** в `public/index.php`. Проверено: во всех 21
   `modules/*/config/routes.php` хендлеры — только `::class`. Вместо тихого `return null`
   нерезолвящийся хендлер теперь бросает `RuntimeException`.

3. ✅ **`http.trusted_hosts` → `Request::setTrustedHosts()`** в `RequestFactory::configure()`
   (метод называется `setTrustedHosts`, а не `setTrustedHostPatterns` — последнее из Laravel).
   Паттерны — регулярки без делимитеров, матчатся **неякорно**, поэтому в комментарии конфига
   явно требуется `^…$`. Ключ пуст по умолчанию.

4. ✅ **`Environment::getIp()` / `getIpViaProxy()` → `$request->getClientIps()`.**
   `[0]` — ближайший недоверенный адрес (посетитель), `[1]` — то, что заявляет его собственный
   прокси; ровно та пара `ip` / `ip_via_proxy`, которую класс собирал руками. Сигнатуры и формы
   возврата (`int|string`) сохранены 1:1 — 28 мест вызова и разнотипные колонки в БД.
   Побочно закрыт живой баг: кэш хранил результат **первого** вызова независимо от
   `$return_long`, из-за чего `Users\UserFactory::ipHistory()` сравнивал строку с int-колонкой.
   Тесты: `tests/Unit/Http/EnvironmentTest.php` (8 тестов).

**Урок 1e (важнее самих правок).** Дефолт `http.trusted_proxies` сначала поставили
`['private_ranges']` — рассуждение было, что за nginx `REMOTE_ADDR` это адрес nginx, а публичный
клиент под приватные диапазоны не попадает. **Оба посыла неверны.** Штатный nginx общается с
PHP-FPM по FastCGI, а не `proxy_pass`: он отдаёт `REMOTE_ADDR = $remote_addr` (адрес того, кто
подключился к nginx) и пробрасывает клиентский `X-Forwarded-For` как есть, ничего не перезаписывая.
При docker port publishing этот адрес — шлюз бриджа (172.x.0.1) **у всех посетителей**, то есть
приватный. Итог: любой клиент из интернета мог выбрать себе адрес заголовком — обход IP-банов и
сброс пер-IP лимитов регистрации и контакт-формы. Воспроизведено `curl -H 'X-Forwarded-For: …'`
на штатном docker-стенде.

Отсюда дефолт `trusted_proxies => []` и три следствия, зафиксированных тестами и комментариями:

* Непустой дефолт вдобавок **неотключаем**: `ConfigLoader` мержит конфиги через
  `array_replace_recursive()`, поэтому `'trusted_proxies' => []` в `*.local.php` — no-op.
  Безопасный дефолт здесь ещё и единственный переопределяемый.
* `tests/Unit/Http/RequestFactoryTest::testShippedConfigTrustsNoProxyAndNoForwardedHeader`
  сторожит сам файл конфига, чтобы дефолт не вернули обратно по невнимательности.
* Установки за Traefik / CDN / балансировщиком обязаны прописать адреса прокси явно —
  иначе `getIp()` вернёт адрес прокси. Это пункт в CHANGELOG и в docs при релизе 10.0.

Заодно вылезло и починено: `themes/default/templates/system/layout/default.phtml:15,19` —
хвост 1d, ссылка на удалённый `\Johncms\System\Http\Request` и `getUri()->getPath()`
(теперь `getPathInfo()`). Это был последний `System\Http`-остаток в репозитории.

### ✅ Этап 1.5. Гейт: статический анализ (M) — готово 2026-07-24 (не закоммичено)

Заведено 2026-07-24. Причина — задокументированный урок 1d: механический `sed` оставил ~60 вызовов
с несовместимым типом дефолта, они дали фатальный `TypeError` под `strict_types`, и
`.agents/scripts/verify.sh` этого не поймал, потому что статический анализ отключён.

Впереди изменения ровно той же формы, но крупнее: 176 `redirect()` (2a), смена возвращаемого типа
у 14 middleware (3b), 203 `$_SESSION` (этап 4), 145 конструкторов контроллеров (этап 5).
Без чекера типов урок 1d повторится на каждом из них.

Реанимировать psalm смысла мало: `psalm.xml.dist` исключает 20 модулей из 21, то есть даже после
починки конфига анализироваться будет практически один `system/src`.

* ✅ **PHPStan 2.2, level 5**, `phpstan.neon.dist`; анализируются `config/`, `modules/`,
  `public/`, `system/src`, `tests/`. Baseline `phpstan-baseline.neon` — **773 записи**;
  валит гейт только новая ошибка.
* ✅ `phpstan` добавлен в `.agents/scripts/verify.sh` между `cs-check` и `test`,
  плюс `composer phpstan` / `composer phpstan-baseline`; `composer check` теперь гоняет всю тройку.
* ✅ `vimeo/psalm` и `psalm.xml.dist` удалены.
* Хелперам `redirect()` / `pageNotFound()` на этапе 2a поставить нативный `never` в сигнатуре
  (сейчас у них только `@return never-return` в PHPDoc, который ничего не значит). Тогда чекер
  сам найдёт и недостижимый код после них, и места, где после `redirect()` ожидался возврат.

*Дополнение 2026-07-25 (по итогам 2a):* в `test` добавлен
`tests/Unit/Container/ContainerCompilationTest.php` — компиляция DI-контейнера. Ни `cs-check`,
ни PHPStan, ни остальные тесты не видят некомпилируемый контейнер, а он означает белый экран
на всех страницах.

**Готово**: `sh .agents/scripts/verify.sh` прогоняет `cs-check` → `phpstan` → `test`, всё зелёное;
приёмка проверена — подсаженный `$request->body('page', 0)` (ровно дефект класса 1d) валит гейт
с `argument.type`, после отката снова зелено.

Что дала настройка, помимо самого гейта:

1. **Найден живой фатал.** `modules/album/.../AlbumValidationException` переобъявлял
   унаследованное `$errors` как promoted `readonly` — PHP роняет это на этапе объявления класса
   (`Cannot redeclare non-readonly property … as readonly`). То есть **любая** ошибка валидации
   формы альбома давала белый экран. Воспроизведено `php -r`, починено (присваивание в
   унаследованное свойство, вызов на `getErrors()`), закрыто тестом
   `tests/Unit/Modules/Album/Exceptions/AlbumValidationExceptionTest.php`. Это была одна из двух
   «non-ignorable» ошибок, которые PHPStan отказался класть в baseline — и он был прав.
2. **2107 ложных `function.notFound`** оказались конфигом, а не долгом: `__()` / `d__()` / `dn__()`
   объявлены под `function_exists()`, поэтому не видны автолоадеру — лечится
   `scanFiles: vendor/gettext/translator/src/functions.php`. Без этого baseline был бы втрое
   больше и бесполезен.
3. `config/constants.php` — `const DS` заменена на guarded `define()`: phar PHPStan поднимает
   composer-автолоад дважды и сыпал `Constant DS already defined` на каждый прогон гейта.

Состав baseline (773): ~496 — магические свойства и методы Eloquent (`property.notFound`,
`method.notFound`, `staticMethod.notFound`), их без larastan/докблоков моделей не убрать;
далее `return.type` 60, `phpDoc.parseError` 48, `argument.type` 44, `variable.undefined` 22.
Разобранные `argument.type` в guestbook / mail / `Casts\FormattedDate` — **не** живые баги,
а протухшие аннотации: `TimeToDate::get()` и `FormattedDate::get()` помечены `@return Carbon|int`,
а фактически возвращают строку (`->calendar()` / `->isoFormat()`), и `@property int $time` /
`@property int $ip` на моделях врут про свои же касты. Чистка докблоков — отдельная задача,
она будет уменьшать baseline.

### 🚧 Этап 2. Response (L) — основной объём работы. 2a-pre / 2a / 2b / 3a готовы;
2c идёт помодульно (закрыты `downloads`, `library`), дальше `news`; затем 2d

**Порядок внутри этапов 2–3 изменён 2026-07-24**: `2a-pre → 2a → 2b → 3a (скелет ядра) → 2c → 3b`.
Причина — 2c это самый большой кусок плана (60 файлов, 57 `exit`, 62 `header()`), и в исходном
порядке он выполнялся вслепую: критерий «ни один контроллер не пишет в вывод напрямую» нечем
проверить, пока нет `handle()`. Тезис «не начинаем с ядра, без Response оно бесполезно» остаётся
верным — 2a/2b как раз и дают Response, а 2b совсем маленький. Скелет ядра сразу открывает
`tests/Functional/`, и этапы 4–5 получают свой приёмочный тест бесплатно.

**2a-pre. ✅ ГОТОВО (2026-07-24, не закоммичено). Аудит широких `catch` перед переводом хелперов
на исключения.**

Исходная гипотеза была: в `modules/` + `system/src` 32 блока `catch (Throwable)` / `catch (Exception)`,
часть из них в контроллерах, которые сами зовут `redirect()`, — значит после 2a редиректы начнут
молча проглатываться, и все 32 места надо сузить.

**Аудит гипотезу не подтвердил.** Для каждого из 32 мест поднято тело соответствующего `try`
(поиск открывающего `try {` по совпадению отступа) и проверено на `redirect(` / `pageNotFound(`:
**прямых вызовов нет ни в одном**. Затем разрезолвлены фактические цели вызовов внутри этих
`try` — `FileStorage::saveFromRequest()` / `::delete()`, `library\Services\Utils::imageUpload()`,
капча guestbook/contacts, upload-юзкейсы album/profile, `CleanupOrphanForumFilesUseCase`,
Intervention Image, Eloquent: **ни одна не достигает управляющих хелперов**. (Промежуточный
прогон по именам методов дал 21 «риск» — это мусор: `save`/`delete`/`run`/`generate`
совпадают у сотен несвязанных классов. Имя метода без разрешения типа тут не работает,
проверять надо цели вызовов.)

Поэтому сузили не 32 места, а два — те, где проблема реальна:

1. **`system/src/System/View/Render.php` — снят перехват.** Класс переопределял `render()`
   ровно ради `catch (Throwable) { return $e->getMessage(); }`. Это и есть та самая дыра:
   шаблоны Plates исполняют произвольный PHP **внутри** этого вызова, а `render()` стоит на
   пути каждого из 208 мест. После 2a любой `redirect()` из шаблона стал бы телом страницы.
   Плюс это самостоятельный дефект: ошибка шаблона отдавала **200** с сырым текстом исключения
   вместо страницы, показывала его посетителю независимо от `DEBUG` и ничего не логировала.
   Теперь ошибки доходят до `GlobalErrorHandler` — лог + 500, детали только при `DEBUG`.
   Тесты: `tests/Unit/System/View/RenderTest.php`.
2. **`online/{Index,History,Guest}Controller` — пустой `catch (Throwable) {}`** сужен до
   `Psr\Container\ContainerExceptionInterface`. Намерение там — «модуль forum может быть не
   установлен»; всё остальное больше не глотается молча.

Остальные 28 оставлены широкими сознательно: они оборачивают обработку изображений, файловый
ввод-вывод, консольные команды и бутстрап, имеют осмысленный фолбэк или логирование, и ни один
не достигает управляющих хелперов. Спекулятивное сужение превратило бы обработанные сбои в 500
без всякой пользы для 2a.

Чтобы аудит не пришлось повторять руками, правило записано в `.agents/review/php-quality.md`
(раздел Error Handling): широкий `catch` не должен оборачивать код, способный выполнить
HTTP-управление.

**Что вскрыл self-review 2a-pre.** Аудит был проведён по одной оси — «может ли `try` дойти до
`redirect()`». Снятие перехвата в `Render` добавило вторую: `render()` теперь **бросает**, а
значит все существующие `try`, внутри которых он вызывается, поменяли смысл. По этой оси нашлись
два места, и оба починены:

* `downloads/EditScreenController.php` — `catch (Exception)` оборачивал `return $this->render->render(...)`
  и отдавал `'Screenshot not attached ' . $e->getMessage()`. То есть ошибка шаблона выдавалась
  за сбой загрузки картинки, не логировалась, а её текст печатался посетителю — ровно тот дефект,
  ради которого перехват и снимали, только этажом выше. Рендер вынесен из `try`.
  Это единственное место во всём `modules/` + `system/src`, где широкий `catch` оборачивает
  `render()` (проверено скобочно-сбалансированным сканом всех `try`).
* `system/src/Mail/EmailSender.php` — `render()` вызывается в цикле по очереди писем **до**
  `update(['sent_at' => …])`. Одна нерендерящаяся запись теперь обрывала бы весь батч и навсегда блокировала
  очередь. Добавлена пер-сообщенческая изоляция: лог + пометка записи + `continue`. Раньше такая
  запись рассылалась подписчикам с текстом исключения в теле письма.

**Побочно найден сломанный установщик** (хвост 1d, а не 2a-pre). Регистрация `GlobalErrorHandler`
в `public/install/index.php` — до неё у установщика вообще не было обработчика ошибок — сразу дала
в лог `Call to undefined method Johncms\Http\Request::getQuery()`. Массовая замена вызовов в 1d
не дошла до `public/install/`: `getQuery` ×2, `getPost` ×6, `isHttps` ×1 в `index.php` и
`steps/step_{1,3,4}.php`. **Установка 10.x была невозможна в принципе.** Исправлено
(`queryInt`/`queryParam`/`body`/`bodyInt`, `isHttps()` → `getSchemeAndHttpHost()` с сохранением
`FILTER_SANITIZE_URL` на присланном `homeurl`).

**Урок про baseline.** Все девять этих фаталов **лежали в baseline этапа 1.5**: он снимался с кода,
в котором регрессии 1d ещё не были исправлены, и потому узаконил их. Baseline фиксирует «долг,
который мы принимаем», а не «долг, который мы проверили». Правило на будущее: после генерации
baseline просматривать записи `method.notFound` / `class.notFound` / `function.notFound` по
**проектным** классам — они почти всегда означают фатал, а не стилистику. Отфильтровать шум Eloquent
можно так:

```bash
grep -A3 'identifier: method.notFound' phpstan-baseline.neon | grep -v '\\Models\\\|Illuminate'
```

Заодно из baseline убрана запись по моему же коду: `ActionInvoker` звал `ReflectionType::getName()`,
которого нет у union-типов (`int|string $id` в сигнатуре действия дал бы фатал) — теперь
`$type instanceof ReflectionNamedType ? … : null`. Baseline после чистки: 773 → **758** ошибок.

**2a. ✅ ГОТОВО (2026-07-25, не закоммичено). Хелперы на исключения (даёт максимум охвата за
минимум правок).** Self-review (architecture/security/php-quality/localization) + гейт зелёные.

* ✅ `redirect(string $url, int $status = 302): never` → `throw new HttpRedirectException($url, $status)`.
  177 вызовов не изменились ни в одном файле, `exit` из хелпера ушёл.
* ✅ `pageNotFound(...): never` → `throw new PageNotFoundException(...)` (класс уже был). 31 вызов.
* ✅ `checkRedirect(): void` тоже переведён на исключение (301) — иначе `exit` остался бы внутри
  `pageNotFound()`. Заодно guard: не-строка или пустая строка в `config/redirects.php` теперь
  даёт внятный `RuntimeException` с именем ключа, а не битый `Location:`.
* ✅ Новый `Johncms\Exceptions\HttpRedirectException` (`final`, `getUrl()`/`getStatus()`).
  Валидирует пустой URL и не-3xx статус **в конструкторе** — ошибка падает на месте вызова
  `redirect()`, а не позже как `InvalidArgumentException` из `RedirectResponse`. URL в
  `getMessage()` намеренно **не** попадает: `GlobalErrorHandler` печатает исключение в `<pre>`
  без экранирования.
* ✅ `Johncms\Http\ExceptionResponseFactory` (`final readonly`, зависит только от `Render`):
  `fromRedirect()` → `RedirectResponse`, `fromPageNotFound()` → 404-`Response`. Переводимые
  дефолты title/message переехали сюда из хелпера; ядро 3a переиспользует класс как есть.
* ✅ `PageNotFoundException`: типизированные свойства, дефолтный `title` теперь **пустой**.
  Раньше при `throw new PageNotFoundException(...)` заголовок был непереведённой английской
  строкой — теперь дефолт подставляет HTTP-слой, то есть переведённый.
* ✅ `public/index.php`: один `try` вокруг всего роутинга (включая ветки 405 и «маршрут не найден»),
  два `catch` формируют и отправляют ответ.
* ✅ Тесты: `tests/Unit/HelpersTest.php` (8), `tests/Unit/Http/ExceptionResponseFactoryTest.php` (4).
  Смоук на стенде: `/` 200, неизвестный URL 404 (переведённый title), `/admin/` гостю → 302
  `Location: /admin/login`, `/mail/` гостю → 404, `/news/no-such-section/` → 404.

**Урок 2a: контейнер сломался, а гейт этого не увидел.** `services.php` автовайрит **каталог**
`system/src` целиком, поэтому новый `HttpRedirectException` со скалярным `$url` в конструкторе
сделал контейнер некомпилируемым — белый экран на **каждой** странице. При этом `cs-check`,
PHPStan и все 324 теста были зелёные. Починка — исключить `system/src/Exceptions` из `load()`
(исключения никогда не сервисы; в модулях такое исключение уже было принято).
Чтобы класс дефектов закрылся: `tests/Unit/Container/ContainerCompilationTest.php` компилирует
контейнер (0.2 с) и восстанавливает статический синглтон `PSRContainerFactory` в `tearDown()`.
Приёмка проверена — с возвращённым автовайром `Exceptions` тест падает.

**Изменение поведения, найденное self-review (починено).** Снятие `exit` открыло путь к коду,
который раньше не выполнялся: хвост `public/index.php` с `EmailSender::send()`. То есть каждый
редирект и каждая 404 (в т.ч. ботовый флуд) начали флашить очередь писем. Плюс `Response::send()`
по умолчанию зовёт `fastcgi_finish_request()` — ответ уходил до `session_write_close()`, и
следующий запрос браузера ждал бы блокировку файла сессии всё время SMTP-батча. Сохранён паритет:
`send(false)` + `return` в обеих ветках. Отправка ответа одной точкой и post-response работа —
это 2d и `terminate()` в 3b, там же это место переделывается по-настоящему.

**Открытое (не блокеры).**

* Ветка 301 в `checkRedirect()` не покрыта тестом: карта читается `require CONFIG_PATH . 'redirects.php'`
  без шва, а поставляемый файл пуст. Закрывается функциональным тестом в 3a или швом (карта параметром).
* ~~`.pot`: ссылки на файл/строку двух строк 404 указывают на старое место~~ — **закрыто в 2b**:
  прогнан `i18n:scan`, обновлены ссылки во всех 15 `.pot`, заодно вычищены msgid'ы удалённого
  `src-legacy` (`Added`, `ERROR` — оба живут в других доменах с переводами). `.po` и `.lng.php`
  не тронуты: msgid'ы не менялись, новый только один — `Method Not Allowed` (уйдёт на Crowdin).
* На 404/редиректе уходят два заголовка `Cache-Control` (нативный сессионный + симфонийский
  `no-cache, private`): Symfony отправляет `header()` с `replace = false` для всего, кроме
  `Content-Type`. Безвредно, растворяется в 2d, когда через `Response` пойдут все ответы.

**2b. ✅ ГОТОВО (2026-07-25, не закоммичено). Переходный контракт.** Ядро принимает
`Response|string|null`, строку оборачивает в `Response`. Self-review (architecture/security/
php-quality) + гейт зелёные.

* ✅ `Johncms\Http\ResponseNormalizer` (`final readonly`): `normalize(mixed $result, int $status = 200): Response`.
  `Response` пропускается как есть, строка/`null` оборачиваются, всё остальное — `LogicException`
  с типом в сообщении. Тесты `tests/Unit/Http/ResponseNormalizerTest.php` (10).
* ✅ `public/index.php`: `echo $result` заменён на `normalize(...)->send(false)`. Статус берётся
  из `http_response_code()` — иначе статус, выставленный легаси-контроллером (`403` в
  `ForumErrorRenderer`, `404` в `library/DownloadArticleController` и ещё ~100 мест), был бы
  затёрт двухсоткой от `Response`. Шов живёт во фронт-контроллере, не в нормализаторе, и
  исчезает вместе с 2c.
* ✅ Заодно 405: вместо `echo '405 Method Not Allowed'` со статусом **200** — новый
  `Johncms\Exceptions\MethodNotAllowedException` (та же форма, что у 2a) и
  `ExceptionResponseFactory::fromMethodNotAllowed()`: статус 405, заголовок `Allow` из
  `RouteMatchResult::$allowedMethods`, `Content-Type: text/plain`, переводимое тело.
  Ответ строится в `system/src/Http/`, а не в `public/` — то есть 3a переиспользует его как есть,
  и тело попадает в `system.pot` (для `public/` сканер не настроен).
* ✅ Побочно (хвост 1d, найден ревьюером): `news/CommentsController::add()/del()` звали
  `$request->getContent()->getContents()` — у HttpFoundation `getContent()` возвращает строку,
  то есть **любой непустой JSON POST в добавление/удаление комментария новостей падал фаталом**.
  Заменено на приватный `decodeJsonBody()`; из baseline убрана запись `method.nonObject`,
  которая этот фатал узаконивала (758 → 756).
* ✅ Смоук на стенде: все главные страницы 200 с прежним размером; `POST /library/` → 405 +
  `Allow: GET`; `/library/article/999999/download/txt` → 404 (легаси-статус сохранён);
  `/library/article/1/download/txt` → `Content-Type: application/octet-stream` (легаси-заголовок
  сохранён); 404 и редиректы из 2a работают.

**⚠️ Поправка к ловушке, записанной здесь после 2a.** Формулировка была неверной: `new Response()`
кладёт в бэг только `Cache-Control` и `Date` — `Content-Type` **не кладёт** (проверено
`php -r` на v7.4.14). Он появляется только через `Response::prepare()`, `JsonResponse`,
`RedirectResponse`, `BinaryFileResponse`. То есть само оборачивание строки Content-Type не ломает;
ломает его `prepare()`, потому что `sendHeaders()` отправляет `Content-Type` **с `replace = true`**
(единственный заголовок, для которого это так). Отсюда правило, зафиксированное в докблоке
`ResponseNormalizer::wrapLegacyOutput()` и тестом: **не вызывать `prepare()`** ни здесь, ни в ядре
3a, пока JSON-эндпоинты и `library/DownloadArticleController` не начнут строить свой `Response`
(2c). `Cache-Control` из обёрнутого ответа удаляется — он дублировал бы нативный сессионный.

**Что 2b оставляет для следующих этапов** (иначе всплывёт как «тихая» регрессия):

* **3a**: `http_response_code()` — process-global состояние, между двумя `handle()` в одном
  процессе оно не сбрасывается. Перед обработкой запроса в ядре его надо явно ставить в 200
  (или передавать статус в `handle()`), иначе смоук-набор 3a и тест изоляции этапа 5 будут
  ложно-зелёными или заражать друг друга.
* **2c**: `normalize()` при возвращённом `Response` игнорирует легаси-статус. Сегодня это
  безопасно (ни один контроллер не возвращает `Response`), но полумиграция вида «контроллер
  возвращает `new Response($this->forumErrorRenderer->render(...))`, а рендерер внутри всё ещё
  зовёт `http_response_code(403)`» отдаст страницу отказа с кодом 200. Мигрировать контроллер
  вместе с его error-рендерером.
* **2d**: у 405/404/редиректа уходит симфонийский `Cache-Control: no-cache, private` рядом с
  нативным сессионным (`sendHeaders()` использует `replace = false` для всего, кроме
  `Content-Type`). Безвредно, но чистится там, где все ответы пойдут через `Response`.

**→ Здесь выполняется 3a (скелет ядра) — см. этап 3.** 2c метётся уже под функциональными тестами.

**2c. Вычистка прямых сайд-эффектов** — помодульно, от простого к сложному.
На 2026-07-25 закрыты `downloads` и `library`; осталось 120 мест в остальных модулях.

| Модуль | `header('Location')` | `exit`/`die` | Приоритет |
|---|---|---|---|
| ✅ `downloads` | 11 | 11 | 1 — готово 2026-07-25. `LoadFileController` отдаёт **`RedirectResponse`**, а не `BinaryFileResponse`: он редиректит на статический URL файла, файл сам не читает. План здесь был неточен |
| ✅ `library` | 10 | 13 | 2 — готово 2026-07-25 (`DownloadArticleController` → `StreamedResponse`, заголовки 1:1) |
| `news` | 7 | 12 | 3 (в т.ч. `exit($exception->getMessage())` — заменить на нормальный ответ) |
| `help` | 5 | 3 | 4 |
| `redirect` | 4 | 4 | 5 |
| `mail` / `login` | по 3 | по 3 | 6 |
| `notifications` / `forum` / `album` / `admin` / `collections` | 1–2 | 1–2 | 7 |
| `system` (`Comments.php`, `BanIP.php`) | 5 | 5 | 8 (легаси, последним) |

Плюс: 16 `json_encode` + `header('Content-Type: application/json')` → `JsonResponse`;
104 `http_response_code()` → статус в `Response`; 13 `setcookie()` → `$response->headers->setCookie()`.

*Механика, отработанная на первых двух модулях (2026-07-25).* Работу делает субагент на дешёвой
модели, один модуль за раз, с обязательным прогоном гейта; результат проверяется вручную по трём
осям — сохранён ли нестандартный статус (301 не должен стать 302), сохранены ли заголовки файловой
отдачи 1:1, мигрирован ли error-рендерер модуля **вместе** с контроллерами. Один модуль ≈ 110–155k
токенов субагента, `library` в один заход не уложился. Ещё два наблюдения:

* Список файлов по `grep` неполон: в `library` пришлось менять и `LibraryPathController`
  (диспетчер, форвардящий в другие контроллеры) — его нашёл PHPStan, а не `grep`.
* Сервисы (`library/Services/{Rating,Utils}.php`) не могут вернуть `Response` — его некому принять.
  Там правильный ход — глобальный `redirect()`, который с 2a бросает исключение.

**⚠️ Урок 2c: baseline прячет живые баги, и это уже второй раз.** Хвост 1d дал четыре места, где
результат `queryParam()`/`body()` (тип `string`) сравнивается с `null` — сравнение всегда истинно.
Два из них были боевыми поломками: `downloads/LoadFileController` отдавал **404 на любую загрузку
файла**, `downloads/EditScreenController` уводил POST без `?do=` в удаление вместо загрузки.
Ещё два меняли поведение тише (`album/ShowPhotoController` — недостижимая авто-страница,
`admin/LanguagesController` — `''` вместо `null` в «не менять язык»).

Существенно: **PHPStan их находил** правилом `notIdentical.alwaysTrue` — записи лежали в
`phpstan-baseline.neon` этапа 1.5, потому что baseline снимался с кода, где регрессии 1d ещё не
были исправлены. Ровно та же причина, что и у девяти фаталов в уроке 2a-pre. Правило из того урока
(просматривать `method.notFound` / `class.notFound` / `function.notFound` по проектным классам)
следует расширить на `*.alwaysTrue` / `*.alwaysFalse`: после массовой замены API это индикатор
мёртвой ветки, а не стилистика. Проверка остатка:

```bash
grep -B6 'identifier: .*always' phpstan-baseline.neon | grep 'path:'
```

Остальные записи этого класса проверены и безвредны (`downloads/FilesUploadController:217`,
`collections/ReservedCodeChecker:39` — легаси-сравнения).

*Побочно закрыт open redirect* в `library/Services/Rating.php`: голосование редиректило на
непроверенный `$_SERVER['HTTP_REFERER']`. Теперь от него берутся только path + query, host
отбрасывается всегда. Отдельно отсекается путь, начинающийся с `//` или `/\` — браузеры
нормализуют `/\evil.com` в протокол-относительный `//evil.com`, то есть одной проверки
«начинается со слеша» недостаточно.

**2d. Единая точка отправки.** `echo $result` в `public/index.php` заменяется на `$response->send()`.
`ob_start('ob_gzhandler')` из `system/bootstrap.php` удаляется — сжатие отдаём веб-серверу.

**Готово, когда**: ни один контроллер не пишет в вывод напрямую; `grep -c 'exit\|die('` по `modules/` — 0.

### 🚧 Этап 3. Kernel (M) — 3a готов (2026-07-25), остался 3b

**3a. ✅ ГОТОВО (2026-07-25, не закоммичено). Скелет ядра.** Self-review (architecture/security/
php-quality) + гейт зелёные (383 теста: 352 unit + 31 functional). Что сделано:

* ✅ `Johncms\Http\Kernel implements HttpKernelInterface` (`final readonly`). `symfony/http-kernel`
  добавлен в зависимости **ради интерфейса** — event-стек, `HttpKernel`, `ControllerResolver` не
  используются (это и означало «не тащим целиком»): его требуют бриджи `symfony/runtime` на этапе 6.
* ✅ `Johncms\Http\RequestPathNormalizer` — нормализация URI из `public/index.php`.
* ✅ `SymfonyRouteMatcher::matchRequest(Request)`: `RequestContext::fromRequest()` на каждый запрос
  (иначе воркер генерировал бы URL от хоста прошлого запроса) + матчинг по нормализованному пути.
* ✅ Маппинг исключений в ядре: redirect / 404 / 405 / **400** (`RequestExceptionInterface`) / 500.
  `http_response_code(200)` сбрасывается в начале `handle()` (долг 2b).
* ✅ `Johncms\Logs\DebugDetailsPolicy` — извлечена из `GlobalErrorHandler::canShowDebug()`, чтобы
  500 из ядра показывал детали по тому же правилу (`DEBUG = true` в поставке, поэтому «показывать
  при DEBUG» было бы утечкой трейсов всем).
* ✅ `Request` стал `synthetic()` (перенос части этапа 5 — без него ядро не может публиковать
  запрос цикла, а функциональные тесты невозможны). Публикуется в `bootstrap.php` (для кода,
  который резолвит сервисы до ядра) и на каждый `handle()`.
* ✅ `public/index.php` — 38 строк: bootstrap → `handle()` → `send(false)` → блок cron.
* ✅ `tests/Functional/` (новый testsuite + `composer test:functional`): харнесс поднимает
  приложение один раз и гоняет реальные запросы через `handle()`; без доступной БД набор
  скипается, поэтому CI (`composer test:unit`) остаётся зелёным. 31 тест, 0.5 с.

**Что вскрыл смоук-набор (главная ценность 3a).**

1. **`Render::addFolder()` фаталил на втором запросе в одном процессе.** Движок — синглтон, каждый
   контроллер регистрирует namespace своего модуля в конструкторе, повторная регистрация — 
   `InvalidArgumentException: The template namespace "forum" is already being used`. То есть
   worker-режим (этап 6) был невозможен в принципе, и это не было видно под FPM. Повторная
   регистрация тех же каталогов стала no-op; регистрация другого каталога под занятым именем
   по-прежнему падает (тесты в `RenderTest`).
2. **Установка была не единственным сломанным входом**: ревьюер нашёл, что `getPayload()` бросает
   `JsonException` (не `BadRequestException`!) на любом нераспарсиваемом теле, а
   `TranslatorServiceFactory` читал `body('setlng')` **в бутстрапе** — до регистрации обработчика
   ошибок. `curl -H 'Content-Type: application/json' --data '{' /login` давал **uncaught fatal
   со статусом 200**. Починено трижды: чтение `setlng` переведено на query (в тело лезть на
   бутстрапе нечего), ядро ловит `RequestExceptionInterface` целиком (кроме
   `SessionNotFoundException` — это серверный дефект, ему 500), а `GlobalErrorHandler`
   регистрируется теперь **внутри** `bootstrap.php`, сразу после контейнера, — то есть боевые
   сбои бутстрапа наконец логируются.
3. **`SuspiciousOperationException` (чужой `Host`) не доходил до ядра**: `RequestContextFactory`
   вызывал `fromRequest()` при сборке контейнера, то есть `getHost()` срабатывал раньше
   `handle()`. Фабрика больше не читает запрос (контекст заполняет `matchRequest()`), и 400
   действительно отдаётся ядром.
4. **`getPathInfo()` создавал второй адрес каждой страницы.** Он вычитает префикс фронт-контроллера,
   поэтому `/index.php/admin/users` начал матчиться (а через `RequestContext::fromRequest()`
   префикс просачивался во все генерируемые ссылки, плюс определение «это админка» по
   `REQUEST_URI` ломалось). Нормализатор снова берёт `getRequestUri()` без query — как было до 3a;
   закрыто тестом `testFrontControllerPathIsNotARouteAlias`.
5. **`php_sapi_name() === 'cli'`** в правиле показа деталей — под RoadRunner/Swoole воркером это
   true, то есть каждый посетитель увидел бы стектрейс. Заменено на `CONSOLE_MODE`.

**Гейт расширен** (по итогам ревью): в `phpstan.neon.dist` добавлены `system/bootstrap.php` и
`system/helpers.php` — раньше бутстрап не анализировался вообще, из-за чего `set()` на значении с
типом PSR-`ContainerInterface` (метода там нет) прошёл незамеченным. Ошибок это добавило две
(константные условия про версию PHP), обе в baseline. Приёмка проверена: подсаженный вызов
несуществующего метода в бутстрапе валит гейт.

Исходный список задач 3a — ниже, для истории.

Минимум, достаточный для функциональных тестов:

* `Johncms\Http\Kernel implements HttpKernelInterface` (без `TerminableInterface` — он в 3b).
* Нормализация URI и спецкейс `/forum/index.php` переезжают из `public/index.php` в ядро
  (или в отдельный `RequestNormalizer`) — сейчас это анонимная функция во фронт-контроллере.
* `SymfonyRouteMatcher` переводится на `matchRequest(Request)` + `RequestContext::fromRequest()`.
* Обработка исключений (404, redirect, 405, 500) — в ядре, а не в `index.php` и не в хелперах.
  На 2026-07-25 три из них уже сведены к исключениям + `ExceptionResponseFactory` (2a/2b), так что
  3a переносит готовые вызовы из `public/index.php` в `handle()`, а заводит с нуля только 500.
  Тогда же: сбрасывать `http_response_code(200)` в начале `handle()` — см. долг из 2b.
  Отдельно 400: `BadRequestException` (массив в скалярном параметре, см. 1a) и
  `SuspiciousOperationException` (`Host` не прошёл `trusted_hosts`, см. 1e). Сейчас оба доходят
  до `GlobalErrorHandler` и дают 500 плюс запись в лог на каждый запрос — то есть после
  заполнения `trusted_hosts` неаутентифицированный клиент может гнать лог циклом запросов
  с чужим `Host`. Это причина не откладывать 3a.
* Возвращаемый тип middleware пока остаётся `mixed` — переходный контракт из 2b позволяет.
* **`tests/Functional/`**: новый testsuite в `phpunit.xml.dist`, харнесс поднимает контейнер
  и зовёт `$kernel->handle(Request::create('/forum'))`. Смоук-набор на ~30 главных маршрутов
  (по одному-двум на модуль): проверка статуса и ключевых фрагментов тела.

Именно ради этого пункта 3a вынесен вперёд. Без него 2c (60 файлов, 57 `exit`, 62 `header()`)
метётся вслепую, а критерии готовности этапов 2c, 4 и 5 непроверяемы: приёмка этапа 5 буквально
сформулирована через два последовательных `handle()` в одном процессе.

**3b. Достройка ядра — после 2c/2d.**

* `MiddlewareInterface::handle()` меняет возвращаемый тип `mixed` → `Response` (14 реализаций).
  Решено делать одним коммитом, см. открытый вопрос 4.
* `implements TerminableInterface`; `UserStat` и `EmailSender::send()` переезжают в `terminate()`.
* `public/index.php` схлопывается до создания ядра, `handle()`, `send()`, `terminate()`.

**Готово, когда**: `public/index.php` ≤ 15 строк, вся HTTP-логика в `system/src/Http/`,
функциональный смоук-набор зелёный.

**Долги, оставленные 3a осознанно** (кроме уже записанных выше по 2c/2d):

* `Kernel::handleRaw()` зовёт глобальный `pageNotFound()` (ради `checkRedirect()`, который читает
  `$_SERVER['REQUEST_URI']`) и `new UserStat($container)` — единственные две не-request-driven
  точки ядра. `UserStat` уезжает в `terminate()` (3b), карта редиректов — в сервис, принимающий
  `Request` (этап 5, вместе с остальными суперглобалами).
* Ядро отвергает `SUB_REQUEST` явным `LogicException`: `handle()` публикует запрос в контейнер без
  восстановления родительского. Если под-запросы понадобятся — нужен `RequestStack` (этап 5/6).
* Функциональный набор пишет в БД стенда (гостевые записи `UserStat`, счётчики) и не откатывает:
  транзакционная изоляция появится, когда/если появится отдельная тестовая БД.
* Ветка 301 `checkRedirect()` по-прежнему без теста: карта читается `require` из
  `config/redirects.php` без шва. Закрывается вместе с выносом карты в сервис (этап 5).
* `data/cache/container.php` (если оператор включил `CACHE_CONTAINER`) после смены `Request` на
  synthetic обязан быть удалён — со старым дампом `set()` в ядре упадёт. Строка в CHANGELOG 10.0.

### — Этап 4. Сессия (M) — строго два шага, порядок критичен

Существующий `Session` (после этапа 1d — `Johncms\Http\Session`) — уже готовая точка абстракции,
менять вызовы дважды не придётся.

**⚠️ Ловушка, из-за которой этап разбит (найдена 2026-07-24).** Нынешний `Session` — фасад над
**корнем** `$_SESSION` (`Arr::get($_SESSION, $key)`, `Session.php:47`). Symfony `AttributeBag`
хранит данные под `$_SESSION['_sf2_attributes']`. Значит в момент, когда фасад перепишут на
`SessionInterface`, 22 места, ходящие через фасад, и 203 места, ходящие в `$_SESSION` напрямую,
начнут читать **разные хранилища**. Ничего не упадёт — значения будут молча теряться, и это
худший вид бага в auth-логике. Поэтому порядок обязателен: сначала все вызовы, потом реализация.

**4a. Все `$_SESSION` — на существующий фасад.** Поведение не меняется вообще: фасад по-прежнему
пишет в корень `$_SESSION`. Механическая правка, коммит на модуль, каждый деплоябелен.
Порядок по объёму: `admin` (59), `news` (21), `collections` (20), `system` (19), `downloads` (16),
`profile` (15), `contacts` (12), `consent` (9), `forum` (8), остальные — по 2–6.

**Готово, когда**: `grep -c '\$_SESSION'` по `modules/` и `system/src/` — 0 (кроме самого фасада).

**4b. Атомарная подмена реализации фасада.** Одна точка изменения вместо 225.

* Переписать `Session` поверх `Symfony\Component\HttpFoundation\Session\SessionInterface`
  (сохранив `get/set/has/remove/flash/getFlash`; у HttpFoundation flash-bag уже есть).
  Семантика flash совпадает достаточно: сейчас `getFlash()` удаляет при чтении, у Symfony —
  `get()` из `FlashBag` тоже одноразовый.
* `session_start()` из `system/bootstrap.php` убрать — сессию стартует ядро,
  на каждый запрос (под воркером — старт/`session_write_close()` в рамках `handle()`).
* **Живые сессии инвалидируются**: данные существующих установок лежат в корне `$_SESSION`,
  после переключения читаться перестанут — все разлогинятся. Для мажорной версии приемлемо,
  но это осознанное решение и строка в `CHANGELOG.md`, а не сюрприз при апгрейде.
* Storage оставляем native, сменным его делаем только если понадобится (см. открытый вопрос 5).

**Готово, когда**: фасад не упоминает `$_SESSION`, функциональный смоук-набор с авторизацией зелёный.

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
| `Environment` | добавлено 2026-07-24: синглтон, кэширует `$ip`, `$ipViaProxy`, `$userAgent`, `$ipCount` — чистое per-request состояние. Плюс `di(Request::class)` прямо в конструкторе (`Environment.php:34`) |

Решение:

* `Request` в контроллерах — аргументом действия, а не через конструктор (соглашение из 1a-quater).
  На 2026-07-24 в конструкторе он остаётся у 145 контроллеров из 221; к концу этапа — ни у одного.
  **Важно**: само по себе это не изолирует запрос, пока не сделан пункт 1 этапа 1e
  (`ActionInvoker` резолвит `Request` из контейнера, а не из пайплайна) — см. поправку к 1a-quater.
* Объявить оставшиеся сервисы `->synthetic()` в `system/config/services.php`,
  ядро на каждый запрос делает `$container->set(...)` со свежим экземпляром.
* Остальным, копящим состояние, — `Symfony\Contracts\Service\ResetInterface` + `reset()` в `Kernel::handle()`.
* Глобальные `$page` / `$start` из `system/bootstrap.php` и `$GLOBALS['old']`
  (`modules/downloads/.../IndexController.php:57`) — убрать.
* `di()` запретить в новом коде (в существующем — постепенно; уже есть deprecation-ветки внутри).
* ~~`Environment::getIp()` / `getIpViaProxy()` → `$request->getClientIp()`~~ — **сделано в 1e.**
  Здесь остаётся сам `Environment`: `di(Request::class)` в конструкторе (`Environment.php:57`) —
  тот же захват контейнерного экземпляра, который 1e убрал из `ActionInvoker`, — плюс `ipLog()`
  как сайд-эффект конструктора. Из-за них `EnvironmentTest` вынужден создавать объект через
  `newInstanceWithoutConstructor()`; после этапа 5 обходной приём убрать.
* `Environment` инжектится в Application-слой шести модулей (`forum/CreateTopicUseCase`,
  `PostMessageUseCase`, `ReplyMessageUseCase`, `registration/RegisterUserUseCase`,
  `online/GetIpActivityUseCase`, `admin/PrepareIpBanUseCase`). Связанность досталась по
  наследству, но после переезда в `Johncms\Http\` она стала явным нарушением правила
  «HTTP-типы только в контроллерах/middleware». Развязывать — здесь же.

**Готово, когда**: два последовательных `handle()` в одном процессе дают идентичный результат
(тест на изоляцию: прогнать один и тот же запрос дважды в одном процессе и сравнить ответы,
затем два разных запроса и проверить отсутствие протечки `title`/крошек). Инфраструктура
для этого теста появляется в 3a вместе с `tests/Functional/` — отдельно её городить не нужно.

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
* **Не** начинаем с ядра: без Response оно бесполезно. (Уточнено 2026-07-24: скелет ядра 3a
  всё же идёт до 2c — но после 2a/2b, то есть Response к тому моменту уже есть. Причина —
  без `handle()` вычистка 60 файлов в 2c непроверяема.)
* **Не** тащим Redis-сессии в этот план — см. открытый вопрос 5.
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

4. ~~Нужен ли `Response` в middleware сразу или допустить `mixed` ещё на один этап~~ —
   **решено 2026-07-24: `mixed` живёт до 3b, там меняется одним коммитом.** В 3a переходный
   контракт из 2b это позволяет, а к 3b изменение уже чисто механическое: реализаций 14,
   `MiddlewareDispatcher` типизирован на `Request`, а те middleware, что сейчас делают
   `redirect()` + `exit` (`admin/AdminAccessMiddleware.php:33`, `SuperAdminAccessMiddleware.php:32`,
   четыре `AuthorizedUserMiddleware` с `pageNotFound()`), после 2a и так бросают исключение.
   Держать `mixed` дольше 3b нельзя: нормализация `string → Response` расползётся на два места.

5. ~~Сессия: сразу выносить storage в Redis или оставить native до этапа 6~~ —
   **решено 2026-07-24: native, Redis в этот план не входит.** Вопрос стоял из-за неверной посылки,
   что нативные сессии несовместимы с воркером (см. уточнение в разделе «Решение»). Под FrankenPHP
   worker mode они работают; предусловие воркера — не Redis, а stateless-обработчик, то есть
   этап 5. Redis — вопрос горизонтального масштабирования, а не корректности; заводится отдельно,
   когда появится вторая нода.

6. Порядок этапов 4 и 5 взаимозаменяем — сессия может быть быстрее вычищена, если параллельно
   идёт работа по другим модулям.
