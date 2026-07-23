# Модуль Collections — план реализации

> Аналог инфоблоков Битрикса для JohnCMS: универсальное хранилище «разделы + элементы»
> с настраиваемыми полями и единым API запросов. На его основе можно собрать новости,
> блог, каталог, страницы и любой другой структурированный раздел сайта.

## 1. Зафиксированные решения

| Вопрос | Решение |
|---|---|
| Название модуля | **Collections** (`Johncms\Modules\Collections`) |
| Названия таблиц | `collections`, `collection_fields`, `collection_sections`, `collection_items`, `collection_item_values` |
| Публичный URL | Каждая коллекция владеет **корневым сегментом** = её `code`; общего префикса нет (`/c/` убран). Перехват — низкоприоритетным резолвером `/{route}` → `CollectionRouterController` (см. §9, итерация 4). Админка `/admin/collections/...` — обычный роут. Примеры: `/blog`, `/blog/tech`, `/catalog/phones/iphone-15.html` |
| Имена моделей | Семейство сущностей с префиксом `Content` — чтобы не конфликтовать с `Illuminate\…\Collection`: `ContentCollection` (контейнер), `ContentCollectionField`, `ContentCollectionSection`, `ContentCollectionItem`, `ContentCollectionItemValue`. Таблицы остаются `collection_*` (каждая модель задаёт `protected $table` явно). Алиас для Laravel `Collection` больше не нужен. Репозитории и query-объект несут тот же префикс; module-level классы (контроллеры, фасад, Symfony `RouteCollection`) префикс не получают |
| Хранилище кастомных полей | **EAV-гибрид**: базовые поля — колонки в `collection_items`, произвольные свойства — строки в `collection_item_values` (с типизированными колонками для SQL-фильтрации/сортировки). Типизированные колонки, а не JSON — ради портируемости между версиями/сборками MySQL/MariaDB (не завязываемся на нативный JSON и функциональные/multi-valued индексы). Динамические реальные колонки через `ALTER TABLE` — при необходимости позже (§10) |
| Объём MVP (итерация 1) | **Только ядро**: схема БД + Eloquent-модели + Installer + Domain (entities/enums/repository interfaces) + Infrastructure (репозитории). UI и публичный программный API (фасад для сторонних модулей) — отдельными итерациями |

## 2. Терминология (маппинг Битрикс → Collections)

| Битрикс | Модель | Таблица | Смысл |
|---|---|---|---|
| Тип инфоблока / инфоблок | **ContentCollection** | `collections` | Логический контейнер: «Новости», «Блог», «Каталог». Хранит настройки и набор полей |
| Свойство инфоблока | **ContentCollectionField** | `collection_fields` | Определение произвольного поля коллекции (тип, обязательность, множественность) |
| Раздел | **ContentCollectionSection** | `collection_sections` | Иерархическая категория внутри коллекции |
| Элемент | **ContentCollectionItem** | `collection_items` | Запись/элемент. Базовые поля — колонки, кастомные — в values |
| Значение свойства | **ContentCollectionItemValue** | `collection_item_values` | EAV-строка со значением поля для конкретного элемента |

## 3. Схема БД

> **FK-колонки — `integer unsigned`.** `increments('id')` создаёт unsigned int, поэтому все ссылающиеся
> колонки (`collection_id`, `section_id`, `item_id`, `field_id`, `parent`) объявляем `unsigned`, иначе
> MySQL откажет в создании внешнего ключа по несовпадению типов.

### 3.1 `collections` — коллекции (типы контента)

| Колонка | Тип | Примечание |
|---|---|---|
| `id` | increments | |
| `code` | string, unique | машинный код для URL и программного API (`blog`, `catalog`) |
| `name` | string | |
| `description` | text, nullable | |
| `settings` | json, nullable | `has_sections`, `per_page`, дефолтная сортировка, шаблон вывода |
| `sort` | integer, default 100 | |
| `active` | boolean, default 1 | вкл/выкл коллекции |
| `public` | boolean, default 1 | доступна ли по публичному URL/в sitemap; приватная коллекция управляется и читается через API, но не порождает фронт-URL |
| `timestamps` | | |

### 3.2 `collection_fields` — определения полей

| Колонка | Тип | Примечание |
|---|---|---|
| `id` | increments | |
| `collection_id` | integer unsigned, index, FK → collections | |
| `code` | string | машинный код; unique в паре `(collection_id, code)` |
| `name` | string | |
| `type` | string | значение из enum `FieldType` |
| `required` | boolean, default 0 | |
| `multiple` | boolean, default 0 | допускает несколько значений |
| `sort` | integer, default 100 | |
| `settings` | json, nullable | опции select, целевая коллекция для relation, дефолт, правила валидации |
| `timestamps` | | |

Индексы: unique `(collection_id, code)`.

### 3.3 `collection_sections` — разделы (иерархия)

| Колонка | Тип | Примечание |
|---|---|---|
| `id` | increments | |
| `collection_id` | integer unsigned, index, FK → collections | |
| `parent` | integer unsigned, nullable, index, self-FK → collection_sections | **только `NULL` = корень** (без легаси-0) |
| `name` | string | |
| `code` | string, index | |
| `description` | text, nullable | |
| `active` | boolean, default 1 | |
| `sort` | integer, default 100 | |
| `timestamps` | | |

Индексы: unique `(collection_id, parent, code)`.

> Кавет уникальности: `parent = NULL` в MySQL считается различным — корневые разделы с одинаковым
> `code` не отсекаются индексом. Уникальность `code` в пределах родителя дополнительно проверяем в use case.

### 3.4 `collection_items` — элементы

| Колонка | Тип | Примечание |
|---|---|---|
| `id` | increments | |
| `collection_id` | integer unsigned, index, FK → collections | |
| `section_id` | integer unsigned, nullable, index, FK → collection_sections | основной раздел (single); many-to-many — в будущем через pivot |
| `name` | string | |
| `code` | string, index | |
| `active` | boolean, default 1 | |
| `active_from` | dateTime, nullable | окно публикации |
| `active_to` | dateTime, nullable | |
| `sort` | integer, default 100 | |
| `preview_text` | text, nullable | |
| `detail_text` | longText, nullable | |
| `view_count` | integer, nullable | |
| `created_by` | integer, nullable | |
| `updated_by` | integer, nullable | |
| `timestamps` | | |

Индексы:
- unique `(collection_id, section_id, code)`;
- составной под листинг `findItems` — `(collection_id, active, sort)` (фильтр по коллекции + активность + сортировка).

> Кавет уникальности (как в §3.3): `section_id = NULL` различается в MySQL — дубли `code` в корне
> не отсекаются; контроль `code` дублируем в use case.

### 3.5 `collection_item_values` — EAV-значения кастомных полей

| Колонка | Тип | Примечание |
|---|---|---|
| `id` | increments | |
| `item_id` | integer unsigned, index, FK → collection_items (onDelete cascade) | |
| `field_id` | integer unsigned, index, FK → collection_fields (onDelete cascade) | |
| `value_string` | string, nullable | строки, select, file-path, relation-code |
| `value_int` | bigInteger, nullable | integer, boolean (0/1), relation-id |
| `value_double` | double, nullable | |
| `value_date` | dateTime, nullable | date/datetime |
| `value_text` | longText, nullable | text/html |
| `sort` | integer, default 0 | порядок для multiple-значений |

Индексы:
- `(item_id, field_id)` — выборка всех значений элемента;
- **композитные под EAV-фильтрацию** — `(field_id, value_int)`, `(field_id, value_string)`, `(field_id, value_date)`.
  Одиночный индекс по `value_*` неселективен: центральный запрос всегда фильтрует пару «поле + значение».
  Для `value_string` (varchar 255, utf8mb4) проверить лимит длины индекса InnoDB (3072 байта); при необходимости — префиксный индекс.

Значение пишется в колонку, соответствующую `FieldType` (см. маппинг ниже).

**Маппинг тип поля → колонка значения:**

| FieldType | колонка |
|---|---|
| `string`, `select`, `file` | `value_string` |
| `text`, `html` | `value_text` |
| `integer`, `boolean` | `value_int` |
| `double` | `value_double` |
| `date`, `datetime` | `value_date` |
| `relation` | `value_int` (id) |

## 4. Файловая структура модуля

По `MODULE_REFACTORING_GUIDE.md`. Директории создаём **по мере необходимости** — каждую вложенную папку (`Models`, `Enums`, `Query`, `Repository`, …) заводим, когда появляется её первый класс. Единственное исключение: путь из `services->load(...)` (`src/Application`) должен существовать на диске сразу, иначе Symfony DI упадёт при загрузке.

```
modules/collections/
├── composer.json                # опционально; PSR-4 регистрируем в корневом composer.json
├── config/
│   ├── routes.php               # заглушка на MVP (пустой RouteCollection), заполняется позже
│   └── services.php             # autowire Application + tag'и
├── locale/                      # .pot/.po/.lng, наполняется по мере UI
└── src/
    ├── Application/             # (пусто на MVP; далее Controllers/UseCases/DTO, напр. Controllers/CollectionRouterController.php — публичный URL-резолвер)
    ├── Domain/
    │   ├── Models/
    │   │   ├── ContentCollection.php
    │   │   ├── ContentCollectionField.php
    │   │   ├── ContentCollectionSection.php
    │   │   ├── ContentCollectionItem.php
    │   │   └── ContentCollectionItemValue.php
    │   ├── Enums/
    │   │   ├── FieldType.php
    │   │   ├── FilterOperator.php
    │   │   └── SortDirection.php
    │   ├── Query/
    │   │   ├── ContentCollectionItemQuery.php
    │   │   ├── FieldFilterDTO.php
    │   │   └── OrderByDTO.php
    │   └── Repository/
    │       ├── ContentCollectionRepositoryInterface.php
    │       ├── ContentCollectionFieldRepositoryInterface.php
    │       ├── ContentCollectionSectionRepositoryInterface.php
    │       └── ContentCollectionItemRepositoryInterface.php
    ├── Infrastructure/
    │   └── Persistence/
    │       └── Repository/
    │           ├── ContentCollectionRepository.php
    │           ├── ContentCollectionFieldRepository.php
    │           ├── ContentCollectionSectionRepository.php
    │           └── ContentCollectionItemRepository.php
    └── Install/
        └── Installer.php
```

## 5. Domain-слой (итерация 1)

### 5.1 Enum `FieldType`

`enum FieldType: string` со значениями: `String_`/`string`, `Text`, `Html`, `Integer`, `Double`, `Boolean`, `Date`, `Datetime`, `File`, `Select`, `Relation`.
Методы-помощники:
- `valueColumn(): string` — возвращает имя EAV-колонки (`value_string` и т.п.) по маппингу из §3.5 (единый источник маппинга).
- `cast(mixed $raw): mixed` — приведение сырого значения к PHP-типу при чтении. `mixed → mixed` — компромисс; при росте логики приведение лучше вынести в accessor модели `ContentCollectionItemValue` или отдельный mapper, оставив на enum только `valueColumn()`.

### 5.2 Eloquent-модели (`Domain/Models`)

Правила из AGENTS.md: `$fillable`, `$casts` (использовать `Johncms\Casts\FormattedDate` для дат), `@property`-PHPDoc, отношения через `HasMany`/`HasOne`/`BelongsTo`. Не эскейпить на запись — эскейп только на выводе.

> **Политика эскейпинга.** Следуем правилу AGENTS.md «escape on output»: `SpecialChars`-каст на текстовые
> поля **не вешаем**. Текстовые поля хранятся и отдаются сырыми, эскейп — в шаблонах через `$this->e(...)`
> (это исключает двойной эскейп).

- **ContentCollection**: `hasMany` fields, sections, items. `settings` cast в `array`.
- **ContentCollectionField**: `belongsTo` collection. `settings` cast в `array`. `type` cast в `FieldType`.
- **ContentCollectionSection**: `belongsTo` collection, self-referencing `parentSection`/`childSections`.
- **ContentCollectionItem**: `belongsTo` collection и section, `hasMany` values. Computed-атрибуты `url`, `display_date` — позже, на этапе публичного вывода.
- **ContentCollectionItemValue**: `belongsTo` item, field.

### 5.3 Repository interfaces (`Domain/Repository`)

Тонкие контракты, только чтение/запись данных (бизнес-правила — в use cases). Все методы возвращают типизированные `Collection`/модель или `int` для `count*`. Пагинация — через `limit`/`offset` (см. правила пагинации в AGENTS.md), без `->paginate()`.

- `ContentCollectionRepositoryInterface`: `findByCode(string): ?ContentCollection` (используется и резолвером URL для корневого сегмента), `findById(int): ?ContentCollection`, `getAll(int $limit, int $offset): Collection`, `countAll(): int`.
- `ContentCollectionFieldRepositoryInterface`: `getByCollection(int $collectionId): Collection`, `findByCode(int $collectionId, string $code): ?ContentCollectionField`.
- `ContentCollectionSectionRepositoryInterface`: `getByCollection(int, ?int $parent, int $limit, int $offset)`, `countByCollection(...)`, `findByCode(...)`, `getPathTo(int $sectionId): Collection` (для хлебных крошек/URL).
- `ContentCollectionItemRepositoryInterface`: **центральный** метод `findItems(ContentCollectionItemQuery): Collection` + `countItems(ContentCollectionItemQuery): int`, а также `findById`, `findByCode`.

### 5.4 Чтение значений (read-модель)

Запись значений в типизированные колонки описана в §3.5, но нужен и обратный контракт — получить элемент
с его кастомными полями в виде карты `field_code => приведённое значение`. Даже если полная реализация
откладывается на итерацию 2, контракт фиксируем в ядре, чтобы Domain не был однобоким (только запись):

- `ItemWithValuesDTO` (или accessor `ContentCollectionItem::values` → keyed-map) — элемент + `array<string, mixed>`
  значений, приведённых через `FieldType::cast()`; для `multiple`-полей значение — массив, упорядоченный по `sort`.
- Хайдрация: одним запросом тянем `collection_item_values` по списку `item_id` (без N+1), группируем по элементу и полю.

## 6. Единое API запросов — `ContentCollectionItemQuery` (ядро идеи «единой БД»)

Аналог `CIBlockElement::GetList`. Иммутабельный value-object (DTO), который собирает условия выборки и передаётся в репозиторий. Именно он даёт «единый API» к любой коллекции. Публичный фасад для сторонних модулей строится поверх него (см. §9, итерация 5).

Поля запроса:
- `collectionId: int`
- `sectionId: ?int` (+ флаг `includeSubsections: bool`)
- `filters: FieldFilterDTO[]` — **типизированные** условия вместо сырого массива. Каждый `FieldFilterDTO(fieldCode: string, operator: FilterOperator, value: mixed)`, где `FilterOperator` — enum-allowlist (`Eq`, `Gt`, `Lt`, `Gte`, `Lte`, `Like`, `In`). Работает и по базовым колонкам, и по кастомным полям.
- `onlyActive: bool` — учитывает `active` + окно `active_from/active_to` по `now()`
- `orderBy: OrderByDTO[]` — `OrderByDTO(fieldCode: string, direction: SortDirection)` (enum `Asc`/`Desc`); сортировка по базовым колонкам или по значению поля
- `limit`, `offset`

> **Безопасность (обязательно).** Динамические операторы и сортировка по имени поля — прямой вектор SQL-инъекции.
> Правила построителя запроса:
> - операторы и направления берём **только** из enum (`FilterOperator`/`SortDirection`), в SQL строку не интерполируем;
> - `fieldCode` никогда не подставляем в SQL: резолвим в `field_id` через `ContentCollectionFieldRepository`, а имя колонки значения берём из `FieldType::valueColumn()` (закрытый набор `value_*`);
> - имена базовых колонок сверяем с allowlist разрешённых к фильтру/сортировке колонок.

Реализация в `ContentCollectionItemRepository` (Infrastructure, итерация ядра — сигнатуры; полная фильтрация по EAV — следующей итерацией):
- Стартуем с `ContentCollectionItem::query()->where('collection_id', ...)`.
- Фильтр/сортировка по кастомному полю → `join`/`whereExists` на `collection_item_values` с алиасом на каждое поле и сопоставлением по типизированной колонке (`FieldType::valueColumn()`).
- Предпочитать SQL-выражения (join/exists/where) вместо выборки широких наборов и фильтрации в PHP (правило репозиториев из AGENTS.md).

> На MVP-итерации ядра фиксируем контракт `ContentCollectionItemQuery` и заглушку `findItems`
> с базовой фильтрацией (collection/section/active/sort). EAV-фильтрация по произвольным
> полям — первая задача следующей итерации.

## 7. Infrastructure-слой (итерация 1)

Реализации интерфейсов в `Infrastructure/Persistence/Repository`. Только построение и выполнение запросов; никаких проверок прав, парсинга URL, презентации. `Model::query()->...` как стартовая точка (правило IDE-автокомплита).

## 8. Регистрация модуля

1. **`composer.json`** (корень) — добавить PSR-4:
   `"Johncms\\Modules\\Collections\\": "modules/collections/src/"` → затем `composer dump-autoload` в контейнере.
2. **`config/autoload/modules.global.php`** — добавить `collections` в `installed_modules`.
3. **`modules/collections/config/services.php`** — `services->load('Johncms\\Modules\\Collections\\Application\\', MODULES_PATH . 'collections/src/Application')` + привязка интерфейсов репозиториев к реализациям (`$services->set(Interface::class)` → implementation). Исключить `Domain/Models`, `Install`, `Application/Exceptions` из автозагрузки сервисов.
4. **`modules/collections/config/routes.php`** — на MVP пустой `RouteCollection` (роуты появятся с публичным выводом и админкой).
5. **`Install/Installer.php extends \Johncms\Modules\Installer`** — `install()` создаёт 5 таблиц (см. §3), `installDemoData()` заводит демо-коллекцию «Blog» с парой полей, разделом и элементом (строки через `d__('collections', ...)`).

## 9. Дорожная карта итераций

1. **Ядро — ✅ ГОТОВО:** схема БД + модели + Installer + Domain (enum, модели, repository interfaces) + Infrastructure (репозитории) + `ContentCollectionItemQuery` (контракт). Регистрация модуля. Тесты ядра: smoke-тест Installer (создаются 5 таблиц + демо-данные в типизированных колонках) и базовый тест `findItems` по `collection/section/active/sort`, чтобы контракт застыл до EAV-итерации. `composer cs-check` / `composer test` зелёные.
   - DB-тесты используют in-memory SQLite через новый трейт `Tests\Support\BootsInMemoryDatabase` (первый DB-харнесс в репозитории; остальные тесты остаются pure-unit с моками).
   - Read-модель (`ItemWithValuesDTO`, §5.4) намеренно перенесена в итерацию 2 — там, где значения реально хайдрируются; в roadmap итерации 1 она не значилась.
2. **EAV-выборка — ✅ ГОТОВО:** полная реализация `findItems`/`countItems` с фильтрацией и сортировкой по кастомным полям. Юнит-тесты на запросы (реальный SQLite).
   - Логика построения вынесена в `Infrastructure/Persistence/Query/ContentCollectionItemQueryCompiler.php` (`*Compiler` из AGENTS.md); репозиторий остаётся тонким и лишь резолвит поля (`ContentCollectionFieldRepositoryInterface`, только для referenced-кодов — base-only запросы не делают лишний запрос) и делегирует применение фильтров/сортировки компилятору.
   - **Фильтрация** кастомных полей — через `whereExists` (correlated subquery), а не `join`: `exists` предпочтителен по AGENTS.md и не размножает строки для `multiple`-полей и в `countItems`.
   - **Сортировка** по кастомному полю — через correlated sub-select (`orderBy(closure)`, первое значение по `sort`), тоже без join → нет дублей для multiple-полей.
   - **Безопасность (§6) соблюдена:** операторы/направления только из enum; base-колонки — по allowlist (`FILTERABLE_COLUMNS`/`SORTABLE_COLUMNS`); `fieldCode` кастома резолвится в `field_id`, имя колонки — из `FieldType::valueColumn()`; неизвестные коды молча игнорируются (в SQL не попадают). Прецедент: base-колонка выигрывает у одноимённого кастом-поля.
   - **Read-модель (§5.4)** реализована как метод-аксессор `ContentCollectionItem::getValuesMap(): array<string,mixed>` (вариант «accessor → keyed-map» из плана): читает eager-загруженную связь `values.field`, кастует через `FieldType::cast()`, multiple → массив по `sort`. Батч-гидрация — eager-load `values.field` (один `whereIn`, без N+1). Отдельные DTO/mapper/repo не заводились — лишний код без потребителя до фасада (итерация 5).
   - **Отложено:** `includeSubsections` (раскрытие дерева разделов) — требует резолва потомков; естественно ляжет с публичным выводом (итерация 4). DI: `ContentCollectionItemRepositoryInterface` → `->autowire()->public()`.
3. **Админка (в работе, постранично):** CRUD коллекций → полей → разделов → элементов (динамическая форма полей по `FieldType`). По одной странице за запрос (правило гайда).
   - **✅ Инкремент 1 — CRUD коллекций.** Роуты `/admin/collections[...]` в `modules/collections/config/routes.php` под guard `rights >= 9 && isValid()` (как news). Контроллер `Application/Controllers/Admin/CollectionsAdminController` (`initModule('collections')`): index (пагинация `PaginationFactory`/`PaginationGuard` + flash `success_message`), newForm/editForm, store (create/update, CSRF, валидация code-формата и required, уникальность code через `CollectionCodeAlreadyExistsException`), deleteConfirm/delete (FK-каскад чистит поля/разделы/элементы/значения). Use cases `List/Save/DeleteCollectionUseCase`, DTO `CollectionFormDTO`/`CollectionListItemDTO`. Репозиторий +`create/update/delete` (update через model `fill()->save()` ради каста `settings`). Шаблоны `templates/admin/{index,form,delete_confirm}.phtml` (эскейп только на выводе). Пункт «Collections» добавлен в `themes/admin/.../sidebar-admin-menu.phtml` (иконка `database`, ключ `module_menu['collections']`). `services.php`: `exclude` DTO+Exceptions. Тесты use case (моки). Проверено: DI компилируется, 6 роутов регистрируются для админа, шаблоны линтятся.
   - **✅ Инкремент 2 — CRUD полей коллекции.** Вложенные роуты `/admin/collections/{collection_id}/fields[...]`. `CollectionFieldsAdminController`: index (список полей коллекции, без пагинации — их мало), new/edit форма с `<select>` типа (`FieldType::cases()` + локализованные labels в контроллере), store (CSRF, валидация code-формата/required/валидности типа, уникальность code в паре `(collection_id, code)` через `CollectionFieldCodeAlreadyExistsException`), delete (переиспользует шаблон `admin/delete_confirm`). Проверка владения: поле должно принадлежать коллекции из URL (`findOwnedField`). Use cases `List/Save/DeleteCollectionFieldUseCase`, DTO `CollectionFieldFormDTO`/`CollectionFieldListItemDTO`. Репозиторий полей +`findById/create/update/delete`. `settings` (опции select, relation-target) в форме пока не редактируется — вместе с формой элементов. Ссылка «Fields» добавлена в строку списка коллекций. Тесты use case. Проверено: 12 admin-роутов, DI, линт, cs-check.
   - **✅ Инкремент 3 — CRUD разделов (дерево).** Вложенные роуты `/admin/collections/{collection_id}/sections[...]`. `CollectionSectionsAdminController`: навигация по уровням (`?parent=`, как forum structure) с хлебными крошками пути (`getPathTo`), пагинация уровня, счётчик дочерних (`withCount('childSections')`), badge/кнопка «Open». Форма (name/code/description/active/sort), `parent` фиксируется на редактировании (берётся из раздела; на создании — из контекста и валидируется на принадлежность коллекции), уникальность code в паре `(collection_id, parent)` через `CollectionSectionCodeAlreadyExistsException`. Удаление: дочерние разделы каскадятся (self-FK), элементы получают `section_id = NULL`. Use cases `List/Save/DeleteCollectionSectionUseCase`, DTO `CollectionSectionFormDTO`/`CollectionSectionListItemDTO`. Section-репозиторий +`findById/create/update/delete` и `withCount`. Ссылка «Sections» в списке коллекций — **условно по `has_sections`** (в `CollectionListItemDTO` добавлен `hasSections`). Тесты use case + DB-smoke репозитория (withCount/path/cascade). Проверено: 18 admin-роутов, DI, линт, cs-check.
   - **✅ Инкремент 4 — CRUD элементов (динамическая форма по `FieldType`).** Вложенные роуты `/admin/collections/{collection_id}/items[...]`. `CollectionItemsAdminController`: список (пагинация, `onlyActive:false` — админ видит всё, фильтр `?section=`), форма с базовыми полями (name/code/section-select при `has_sections`/active/active_from/active_to/sort/preview/detail) **и динамическими виджетами по типу поля** (textarea для text/html, number для integer/double, checkbox для boolean, textarea-построчно для `multiple`, text для остальных), store (CSRF, валидация, уникальность code в `(collection_id, section_id)` через `CollectionItemCodeAlreadyExistsException`). **EAV read/write:** чтение значений формы через `getValuesMap` (item `findWithValues`), запись — `SaveCollectionItemUseCase` полностью пересобирает значения (delete + insert) в типизированные колонки (`prepareValue` кастует по `FieldType`, boolean всегда 0/1, пустые single пропускаются, date нормализуется). Новый `ContentCollectionItemValueRepository` (deleteByItem/insertMany), item-репозиторий +`findWithValues/create/update/delete`, section-репозиторий +`getAllByCollection` (пикер). Ссылка «Items» в списке коллекций. **Тесты:** DB-тест записи EAV (типизированные колонки, full-replace на update, пустые/дубликаты) на реальном SQLite + мок-тесты list/delete. Проверено: 24 admin-роута, DI, линт, cs-check/cs-fix.
   - **✅ Пост-доработка — авто-slug элемента.** Поле `code` элемента теперь необязательно: при пустом `code` `SaveCollectionItemUseCase::resolveCode()` генерирует slug из `name` (`Str::slug`, транслитерирует кириллицу: «Привет мир» → `privet-mir`; пустой результат → fallback `item`) и делает его уникальным в паре `(collection_id, section_id)` числовым суффиксом (`base`, `base-2`, `base-3`…). Явно указанный `code` не суффиксуется — дубликат по-прежнему кидает `CollectionItemCodeAlreadyExistsException` (буквальное прочтение задачи: авто-суффикс только для сгенерированного). Валидация контроллера ослаблена (`code` опционален, требуется лишь `name`; формат-regex — только при заполненном `code`); подсказка в форме обновлена. Поведение общее для админки и фасада (`addItem` при пустом `code` генерирует, PHPDoc обновлён). Тесты: генерация из имени + суффикс при коллизии. Проверено: `test`/`cs-check`, транслитерация через реальный `Str::slug`.
   - **Отложенная доработка админки:** редактирование `settings` полей (опции для `select`, целевая коллекция для `relation`) — виджеты select/relation/file пока рендерятся как text-input. Reserved-code валидация — с URL-резолвером (итерация 4).
4. **Публичный вывод (URL-резолвер) — в работе, инкремент 1 ✅.**
   - **✅ Инкремент 1 — резолвер + листинг + детальная страница.** Публичный catch-all `/{route}` (`requirements(['route' => '[\w/.+-]+'])->priority(-1000)`, вне guard прав). `CollectionRouterController` (`__invoke(string $route)`, публичный `ControllerContext`): парсит сегменты → первый = code коллекции (через кэш), `.html` → деталь, иначе → листинг раздела/корня; хлебные крошки через `NavChain` (collection → путь разделов), `PageMeta` + `Pagination` (per_page из `settings`). Проверка «нет коллекции/раздела/элемента» → `pageNotFound()`. Видимость элемента — `findVisibleByCode` (active + окно публикации, eager `values.field`). Значения детали строятся по полям (label→values) через `getValuesMap`. **Кэш** `code → collectionId` — сервис `CollectionCodeCache`(`Interface`) на `Johncms\Cache` (`rememberForever`), инвалидация в `Save/DeleteCollectionUseCase` (быстрый 404-путь без БД). Use cases `ListPublicItemsUseCase`/`GetPublicItemUseCase`, DTO `PublicItemDTO`/`PublicItemDetailDTO`, репозитории +`getActiveCodeMap`/`findVisibleByCode`. Публичные шаблоны `templates/public/{listing,detail}.phtml` (эскейп + `nl2br`). **Дизайн безопасен:** несопоставленные URL и так шли в `pageNotFound()` (легаси-fallback нет), поэтому catch-all перехватывает только промахи; admin/чужие роуты выигрывают по приоритету (проверено матчером: `/admin/collections`→admin, `/blog[/…][.html]`→public, `/`→ нет матча). Тесты: листинг (мок), деталь (DB: видимость/значения). DI, линт, cs-check.
   - **✅ Инкремент 2 — reserved-code валидация.** `ReservedCodeChecker`(`Interface`) инжектит скомпилированный `Symfony\…\RouteCollection` и собирает занятые top-level сегменты: литеральный первый сегмент каждого роута (плейсхолдеры `/{route}` пропускаются) + статический список ФС-путей (`admin`, `assets`, `upload`, `system`, `data`, `install`, `vendor`, `index.php`). `SaveCollectionUseCase` проверяет `isReserved()` до сохранения и бросает `CollectionCodeReservedException` → контроллер показывает ошибку в форме. Проверено на реальных роутах: `admin/news/forum/mail/downloads` → reserved, `blog/catalog` → free; тесты (checker на sample-роутах + reserved-ветка use case).
   - **✅ Инкремент 3 — навигация по дочерним разделам в листинге.** Section-репозиторий +`getActiveChildren(collectionId, ?parent)` (active + scope по parent + сортировка). `CollectionRouterController::renderListing` выводит активные дочерние разделы текущего уровня ссылками (`basePath/{code}`) над списком элементов; корень показывает корневые разделы, раздел — свои подразделы → дерево обходимо публично. Шаблон листинга: блок `list-group` с разделами; «список пуст» — только когда нет ни разделов, ни элементов. DB-тесты репозитория (active-фильтр, parent-scope, порядок).
   - **✅ Инкремент 4 — sitemap-провайдер.** `CollectionsUrlsProvider implements SitemapUrlProviderInterface` (`groupName()='collections'`, тег DI `johncms.sitemap_provider`): для каждой активной коллекции (`getActiveCodeMap`) отдаёт URL корня, достижимых активных разделов (пути строятся обходом дерева, недостижимые из-за неактивного предка пропускаются) и опубликованных элементов (`getVisibleForSitemap` — ленивый `cursor()` по окну публикации), `lastmod` из `updated_at` (`gmdate('c')`); элементы под неактивным разделом пропускаются. Репозитории +`getVisibleForSitemap`. Проверено на реальной БД (демо: `/blog`, `/blog/technology`, `/blog/technology/hello-world.html` + lastmod), DB-тест провайдера (активные-только, пропуск недостижимых).
   - **✅ Инкремент 5 — rich-контент + санитизация (итерация 4 завершена).** `detail_text` элемента теперь rich-HTML: в admin-форме — CKEditor через модульный партиал `templates/admin/ckeditor.phtml` (зеркало news; admin-тема содержит `CkeditorInputComponent.vue`, а `system::app/ckeditor` есть только в default-теме — поэтому свой партиал). Хранение — сырое (escape-on-output). На выводе `GetPublicItemUseCase` санитизирует через `ItemContentFormatter` (инжектит `\HTMLPurifier`), `detail.phtml` рендерит `detailText` как есть (уже очищено); `previewText` остаётся plain (`nl2br`+escape). Проверено: `ItemContentFormatter` (реальный HTMLPurifier: вырезает `<script>`/`onclick`, сохраняет безопасные теги) + end-to-end через контейнер; тест `GetPublicItemUseCase` обновлён (passthrough-purifier). CKEditor-фронтенд — зеркало рабочей интеграции news (в браузере не проверялся).
   - **✅ Пост-фикс — URL элемента из раздела в корневом листинге.** Корневой листинг (`/blog`) показывает элементы всех разделов (при `sectionId = null` фильтр по разделу не применяется), но URL строился из `basePath` текущего листинга → элемент из раздела получал `/blog/{code}.html` без сегмента раздела → 404. Теперь URL строится из **фактического** пути раздела элемента: `PublicItemDTO` несёт `sectionId`, `CollectionRouterController::renderListing` резолвит `section_id → путь` (`getPathTo`, мемоизация по элементам страницы против N+1) и собирает `/{collection}/{section-path}/{code}.html`. Проверено на демо: `hello-world` (раздел `technology`) в корне → `/blog/technology/hello-world.html`. Тест маппинга `sectionId` в use case.
   - **✅ Пост-доработка — CKEditor и на `preview_text`.** «Preview text» в admin-форме элемента переведён с plain-textarea на тот же CKEditor-партиал (скрипты грузятся на нём — `load_scripts => true`, у `detail_text` теперь `false`, чтобы CKEditor JS подключался один раз на два редактора). Соответственно на выводе `previewText` теперь тоже rich-HTML: санитизируется через `ItemContentFormatter` и в `GetPublicItemUseCase` (деталь), и в `ListPublicItemsUseCase` (листинг — форматтер добавлен в конструктор); шаблоны `detail.phtml`/`listing.phtml` рендерят preview как очищенный HTML вместо `nl2br`+escape. Тесты: `ListPublicItemsUseCase` обновлён (passthrough-purifier в конструкторе). Проверено: `test`/`cs-check` зелёные, DI резолвит use case с новой зависимостью, HTMLPurifier вырезает `<script>`/`onclick` в preview.

   Историческая формулировка задачи: `CollectionRouterController` за низкоприоритетным catch-all роутом
   `/{route}` (`->requirements(['route' => '[\w/.+-]+'])->priority(-1000)`) — перехватывает корневые URL
   коллекций (`/blog`, `/blog/tech`, `/catalog/phones/iphone-15.html`), не задевая роуты других модулей
   (они выше по приоритету; порядок задаём **только** через `priority()`, не через порядок загрузки `glob`).
   Разбор в контроллере: первый сегмент → `ContentCollectionRepository::findByCode()`; нет коллекции →
   `pageNotFound()`; суффикс `.html` → детальная страница элемента, иначе → листинг раздела.
   `PageMeta` + `Pagination` (по правилам AGENTS.md), sitemap-провайдер.
   - **Кэш типов коллекций:** карту `code → collectionId` кэшируем — резолвер работает и как 404-путь
     для всех неразобранных URL, без кэша это лишний запрос к БД на каждый промах.
   - **Reserved-code валидация** при создании/редактировании коллекции: `code` не должен совпадать
     с занятыми top-level сегментами (скомпилированный `RouteCollection` + reserved-list `admin`,
     `news`, `forum`, `mail`, `downloads`, …), иначе коллекция окажется недостижимой (её перекроет реальный роут).
   - `.html`/точки: базовый пресет `path` (`[\w/+-]+`) точку не пропускает — поэтому кастомный
     `requirements(['route' => '[\w/.+-]+'])`. Трейлинг-слэш нормализуется в `index.php` (`rtrim`).
5. **Публичный программный API (фасад для сторонних модулей) — ✅ ГОТОВО.** Стабильный сервис-фасад
   `CollectionsApi implements CollectionsApiInterface` (`Application/Api/`), зарегистрирован в DI как
   `->autowire()->public()` — сторонние модули инжектят интерфейс и работают с коллекциями как с инфоблоками
   Битрикса. Тонкая обёртка над репозиториями и item-use-cases (без HTTP-логики):
   - Чтение: `findCollection(string $code)`, `getItems(ContentCollectionItemQuery): Collection`,
     `countItems(...)`, `getItem(int $id)` (через `findWithValues` — значения eager-загружены под `getValuesMap`),
     `getItemByCode(collectionId, ?sectionId, code)`.
   - Запись: `addItem(CollectionItemFormDTO): int` (возвращает id нового элемента, аналог `CIBlockElement::Add`),
     `updateItem(int $id, dto): void`, `deleteItem(int $id): void` — делегируют `SaveCollectionItemUseCase`/
     `DeleteCollectionItemUseCase`, переиспользуя проверку уникальности code и пересборку EAV-значений.
   - **Рефактор для id-возврата:** `SaveCollectionItemUseCase::execute` теперь возвращает `int` (item id) вместо
     `bool`; admin-контроллер вычисляет create/update-флеш из `$id !== null` (guard выше гарантирует существование).
   - Только PHP-контракт, без HTTP; REST при необходимости навешивается отдельным модулем поверх фасада.
   - Тесты: `CollectionsApiTest` (DB, реальный SQLite) — resolve по code, add→id+значения, дубликат code,
     update-пересборка, delete + каскад значений, `getItems`/`countItems` по query. Проверено: `composer test`
     (82 теста), `cs-check`, резолв `CollectionsApiInterface` из скомпилированного контейнера.

> **Итерация «Виджеты/places» удалена.** Отдельный механизм виджетов не нужен: шаблоны и так дёргают
> сервисы через `di(Service::class)`, поэтому тема/модуль инжектит `CollectionsApiInterface` и выводит
> элементы коллекции в любом нужном месте (`getItems(ContentCollectionItemQuery)`). Публичный фасад
> (итерация 5) закрывает эту задачу; на нём же навешивается всё дальнейшее (REST, интеграции).
> С удалением этой итерации дорожная карта модуля завершена.

### Пост-roadmap: флаг публичности коллекции — ✅ ГОТОВО

Колонка `collections.public` (boolean, default 1) отделяет «доступность по URL» от `active` («вкл/выкл»).
Приватная коллекция (`public = 0`) остаётся полностью управляемой в админке и читаемой через фасад
(`findByCode`/`getItems` игнорируют флаг) — она лишь не порождает публичный URL и не попадает в sitemap.
Сценарий: хранилище данных для вывода в произвольной части сайта через API, без лишнего адреса.

- URL-резолвер и sitemap ходят через `getPublicCodeMap()` (`active AND public`; переименован из
  `getActiveCodeMap`) — единый источник «публично достижимых» коллекций для обоих потребителей.
  `CollectionCodeCache` кэширует уже отфильтрованную карту.
- Reserved-code проверка **не** зависит от флага: `code` уникален и резервируется всегда (в т.ч. для
  приватных), чтобы приватную коллекцию можно было сделать публичной без коллизии сегмента.
- Админ-форма коллекции: чекбокс «Public (accessible by URL)» + подсказка; `CollectionFormDTO.public`,
  `SaveCollectionUseCase` пишет колонку.
- Тесты: sitemap-провайдер (приватная активная коллекция и её элементы исключены), `SaveCollectionUseCase`.
  Проверено end-to-end на реальной БД: приватная коллекция выпадает из карты резолвера (→ 404), API её
  по-прежнему отдаёт. Схема Installer'а обновлена; на dev-БД колонка добавлена одноразовым `ALTER TABLE`.

## 10. Отложено / будущее

- Множественная привязка элемента к разделам (pivot `collection_section_items`).
- Права доступа на уровне коллекции/раздела (кто видит/редактирует).
- Типы полей `relation` с обраткой и `file` с интеграцией медиа-хранилища.
- SEO-поля (`keywords`/`meta_description` для разделов и элементов, мета для листингов коллекций) — отдельной итерацией.
- Кэширование выборок и путей разделов (кэш пути от раздела до корня — для хлебных крошек/URL).
- Версионирование/черновики элементов.
- Кастомный публичный URL, отвязанный от `code` (переименование адреса без ломки API/relation), и root-mounted коллекции («страницы» в корне) — при необходимости через ключ в `settings` (JSON, без миграции).

## Референсы в кодовой базе

- `modules/forum/`, `modules/mail/`, `modules/downloads/` — layered-архитектура (UseCases/DTO/Repository), эталон структуры.
- `MODULE_REFACTORING_GUIDE.md` — процедура scaffold и правила слоёв.
