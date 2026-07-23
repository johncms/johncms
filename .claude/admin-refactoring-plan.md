# План рефакторинга модуля `admin` под новую архитектуру

Локальный рабочий файл (gitignored). Отмечай прогресс галочками `[x]` по мере выполнения.

**Источник истины: `MODULE_REFACTORING_GUIDE.md` (корень репо) + `AGENTS.md`.** Этот файл — конкретизация гайда под модуль `admin`. При расхождениях приоритет у гайда.
Эталоны: `modules/album/` и `modules/profile/` (свежие рефакторинги по тому же гайду), плюс `modules/forum/`, `modules/downloads/`, `modules/mail/` (Application / Domain / Infrastructure, PSR-4, REST-роуты, UseCase + Controller + RepositoryInterface).

## Процесс работы (из гайда — обязательно)

- **Одна страница за один заход.** Агент рефакторит одно действие → пользователь ревьюит → пользователь коммитит → только потом следующее. Не рефакторить несколько страниц за раз.
- Controller + UseCase держать в одном заходе (не дробить).
- Если страница тянет много файлов — сначала ядро функциональности.
- Параллелить независимые операции: чтение файлов, прогон cs-check/psalm.
- Без коммитов без явной просьбы пользователя.

---

## Текущее состояние (legacy + частичный scaffold)

`modules/admin/` — query-param диспетчер админпанели. **Scaffold частично уже есть** (отличие от album/profile, где начинали с нуля):

- `src/` уже создан, PSR-4 `Johncms\\Modules\\Admin\\` → `modules/admin/src/` в `composer.json` подключён.
- Уже мигрированы 2 действия:
  - `login` → `Application/Controllers/Users/UsersController::login` (роут `admin.login`, **публичный**, без авторизации — экран входа в админку).
  - `system_check` → `Application/Controllers/System/SystemCheckController::index` (роут `admin.system_check`, rights≥6).
- `Application/Languages.php` — статический сервис (списки/установка/удаление языков); используется в legacy `includes/languages/*`. Перевести в нормальный сервис/use case при миграции `languages`.
- `Application/Controllers/BaseAdminController.php` — **`@deprecated`**, заменён на `Johncms\Http\Controller\AdminControllerContext`. Новые контроллеры используют `AdminControllerContext::initModule('admin')`.
- `Install/Installer.php` — установщик (namespace уже `Johncms\Modules\Admin\Install`).

Legacy-ядро:
- `index.php` — фронт-контроллер. Грузит счётчики в sidebar, гейт **`$user->rights < 7` → `exit`**, список `$actions`, ручной `require includes/<action>.php`. Гость → редирект на `/admin/login/`.
- `includes/` (21 файл + 2 подкаталога) — по одному файлу на действие, диспетч по `?act=` (route param `action`), подрежимы по `?do=`/`?mod=`/`?action=`.
  - `includes/forum/` (8 файлов) — управление структурой форума (разделы/категории).
  - `includes/languages/` (5 файлов) — управление языками.
- `templates/` (Plates, namespace `admin::`), `locale/`, `config/routes.php`, `config/services.php`.

### `AdminControllerContext` (общий контекст админки)

`Johncms\Http\Controller\AdminControllerContext::initModule('admin')` уже делает то, что legacy `index.php` делал руками:
- регистрирует папку шаблонов модуля + translation-домен модуля + домен `admin`;
- кладёт в Render счётчики sidebar (`regtotal`/`countusers`/`countadm`/`bantotal`) + шаблон `system::app/sidebar-admin-menu`;
- добавляет в nav chain `Admin Panel` → `/admin/`.

**NB:** `AdminControllerContext` **НЕ проверяет права** (rights≥7). Сейчас гейт живёт в `index.php` (rights<7 → exit) и частично в `config/routes.php` (`$user->rights >= 6`). При миграции нужен единый механизм — см. «Доступ» ниже.

### Инвентаризация действий

Глобальный гейт legacy: **rights ≥ 7** (`index.php`). Часть действий внутри поднимает планку до **rights ≥ 9** (отмечено).

| action / файл       | Назначение                                            | Доступ        | Подрежимы (`?do=` / `?mod=` / `?action=`)                     | Таблицы                         |
|---------------------|-------------------------------------------------------|---------------|--------------------------------------------------------------|---------------------------------|
| `index`             | Дашборд админки (статистика за сутки)                 | rights≥7      | —                                                            | `users`, `forum_messages`       |
| `adminlist`         | Список администрации по уровням прав                  | rights≥7      | —                                                            | `users`                         |
| `userlist`          | Список пользователей (сортировка nick/ip)             | rights≥7      | `mod=nick\|ip`                                              | `users`                         |
| `reg`               | Модерация регистраций (`preg=0`)                      | rights≥7      | `do=approve\|massapprove\|del\|massdel\|delip`             | `users`, `cms_users_iphistory`  |
| `search_ip`         | Поиск пользователей по IP/диапазону                   | rights≥7      | `mod=history`                                               | `users`, `cms_users_iphistory`  |
| `ip_whois`          | WHOIS по IP                                           | rights≥7      | —                                                            | — (внешний whois)               |
| `usr_clean`         | Очистка неактивных пользователей                      | rights≥7      | подтверждение                                              | `users`, `cms_forum_rdm`        |
| `usr_del`           | Удаление пользователя (каскад)                        | **rights≥9**  | `do=del` (+ подсчёт связей по многим таблицам)            | `users`, форум/коммент-таблицы  |
| `ban_panel`         | Список банов + амнистия                               | rights≥7 (амнистия **rights≥9**) | `do=amnesty`                            | `cms_ban_users`, `users`        |
| `ipban`             | Бан IP/диапазонов (CRUD)                              | **rights≥9**  | `do=new\|insert\|clear\|detail\|del`                       | `cms_ban_ip`                    |
| `karma`             | Управление кармой (сброс)                             | **rights≥9**  | `do=clean`                                                 | `users`                         |
| `access`            | Включение/выключение модулей сайта                    | rights≥7      | POST-сохранение config                                     | — (config)                      |
| `settings`          | Глобальные настройки системы                          | **rights≥9**  | POST-сохранение config                                     | — (config)                      |
| `antiflood`         | Настройки антифлуда                                   | rights≥7      | POST-сохранение config                                     | — (config)                      |
| `antispy`           | Сканер целостности файлов (снимки)                    | **rights≥9**  | `do=snap\|snapscan`                                        | — (файлы/снимок)                |
| `counters`          | Внешние счётчики (CRUD + сортировка)                  | **rights≥9**  | `do=view\|up\|down\|del\|add\|edit`                        | `cms_counters`                  |
| `ads`               | Рекламные блоки (CRUD + сортировка)                   | rights≥7      | `do=edit\|add\|up\|down\|del\|clear`                       | `cms_ads`                       |
| `emoticons`         | Управление смайлами                                  | rights≥7      | —                                                          | config/файлы смайлов            |
| `forum`             | Управление структурой форума (подкаталог)            | rights≥7      | `mod=index\|del\|add\|edit\|cat\|htopics\|hposts\|settings` | `forum_sections`, `forum_topic`, `forum_messages`, `cms_forum_*` |
| `languages`         | Управление языками (подкаталог)                       | **rights≥9**  | `action=index\|manage\|delete\|install\|update`            | — (файлы локалей, config)       |
| `login`             | Вход в админку                                        | **public**    | —                                                          | `users` ✅ **мигрировано**       |
| `system_check`      | Проверка системы                                      | rights≥6      | —                                                          | — ✅ **мигрировано**             |
| `mail`              | (в `$actions` есть, но `includes/mail.php` отсутствует) | —           | —                                                          | **уточнить — мёртвая запись?**  |

### Таблицы БД (по модулю)

- `users` — пользователи (модерация регистрации, списки, удаление, карма, бан).
- `cms_ban_users` — баны пользователей (ban_panel, амнистия).
- `cms_ban_ip` — баны IP/диапазонов (ipban).
- `cms_ads` — рекламные блоки.
- `cms_counters` — внешние счётчики.
- `cms_users_iphistory` — история IP (search_ip, reg/delip, usr_del).
- `forum_sections` / `forum_topic` / `forum_messages` / `cms_forum_rdm` / `cms_forum_files` / `cms_forum_vote` / `cms_forum_vote_users` — структура форума (forum subdir, usr_clean/usr_del).
- `cms_library_comments`, `download__comments`, `cms_users_guestbook`, `cms_album_comments`, `guest` — счётчики связей при удалении пользователя (usr_del).
- config (`config/autoload/system.local.php`) — access/settings/antiflood/emoticons/languages пишут в конфиг, не в БД.

**Переиспользование моделей/репозиториев из соседних модулей:**
- `User` — уже есть Eloquent-модель в `system/src` (проверить актуальный namespace). Не дублировать.
- Бан-таблицы: проверить, есть ли модели в profile (там был `ban`-функционал) — переиспользовать/вынести в общий домен.
- Форум-таблицы: модели в `modules/forum/src` — переиспользовать, **не плодить** дубликаты в admin.

---

## Целевая структура

```
modules/admin/src/
  Application/
    Controllers/        # по контроллеру на действие (группировать в подпапки: Users/, System/, Forum/, Languages/, Ads/, Ban/, …)
                        #   в конструкторе $controllerContext->initModule('admin')
    DTO/                # контекст/команды/результаты (exclude из autoload)
    Exceptions/         # доменные исключения приложения (exclude из autoload)
    Middlewares/        # AdminAccessMiddleware (rights≥7) — см. «Доступ»
    UseCases/           # Get*ContextUseCase / *UseCase / Ensure*AccessUseCase
    Services/           # Languages (уже есть → причесать), ImageProcessing для смайлов и т.п.
  Domain/
    Models/             # Eloquent: Ads (cms_ads), Counter (cms_counters), BanIp (cms_ban_ip), ForumSection и т.д. — только то, чего ещё нет в системе/соседях
    Repository/         # *RepositoryInterface
    Enums/              # уровни прав / типы рекламных мест и т.п. по необходимости
  Infrastructure/
    Persistence/Repository/  # Eloquent*Repository
  Install/
    Installer.php       # уже на месте (namespace Johncms\Modules\Admin\Install)
config/
  routes.php            # REST-роуты вместо ?act=/?do= (+ admin-группа с middleware)
  services.php          # DI: load Application/Infrastructure + bind интерфейсов (сейчас грузит только Controllers)
```

Namespace: `Johncms\Modules\Admin\…`

**Доступ (ключевое отличие admin от album/profile).** Album/profile — `AuthorizedUserMiddleware` (просто авторизация). Здесь доступ ступенчатый:
- весь модуль — **rights ≥ 7**;
- часть действий — **rights ≥ 9** (usr_del, ipban, karma, settings, antispy, counters, languages, ban-амнистия);
- `login` — **публично**.

**Решение (согласовано 2026-06-06): Вариант A.** `AdminAccessMiddleware` (rights≥7) на группу роутов + `Ensure*AccessUseCase`/guard в use case для действий rights≥9 (HTTP 403 при провале). `login` — публичный, вне группы. Логика доступа живёт в Application, не в конфиге.

**Trailing slash:** роуты — *без* завершающего слэша; в ссылках шаблонов — единообразно (как в album/profile). `index.php` нормализует URI через `rtrim`.

**Clean URL mapping** (черновик, уточнять при переносе каждого действия — имена согласуются с пользователем):
- `?act=index`                        → `/admin` (дашборд)
- `?act=adminlist`                    → `/admin/staff` (или `/admin/administrators`)
- `?act=userlist&mod=`                → `/admin/users[/by-nick|/by-ip]`
- `?act=reg&do=`                      → `/admin/registrations` + POST-экшены (`/approve`, `/delete`, …)
- `?act=search_ip&mod=history`        → `/admin/ip-search[/history]`
- `?act=ip_whois&ip=`                 → `/admin/ip-whois`
- `?act=usr_clean`                    → `/admin/users/cleanup`
- `?act=usr_del&id=&do=del`           → `GET/POST /admin/users/{id}/delete`
- `?act=ban_panel&do=amnesty`         → `/admin/bans` + POST `/admin/bans/amnesty`
- `?act=ipban&do=`                    → `/admin/ip-bans` + REST (`/new`, `/{id}`, `/{id}/delete`, …)
- `?act=karma&do=clean`               → `/admin/karma` + POST `/admin/karma/reset`
- `?act=access`                       → `GET/POST /admin/modules-access`
- `?act=settings`                     → `GET/POST /admin/settings`
- `?act=antiflood`                    → `GET/POST /admin/antiflood`
- `?act=antispy&do=`                  → `/admin/file-integrity` + POST (`/snapshot`, `/scan`)
- `?act=counters&do=`                 → `/admin/counters` + REST
- `?act=ads&do=`                      → `/admin/ads` + REST
- `?act=emoticons`                    → `/admin/emoticons`
- `?act=forum&mod=`                   → `/admin/forum/...` (подмаршруты по структуре форума)
- `?act=languages&action=`           → `/admin/languages/...`

**Названия URL не догма** — подбирать осмысленные английские слова, единообразие с forum/downloads/album. Обратную совместимость (`?act=`) не держим — внутренние ссылки (sidebar-меню, шаблоны) обновляем по ходу.

---

## Фаза 0. Анализ (Step 1 гайда) ✅ ВЫПОЛНЕНО (этот документ)

- [x] Зафиксированы все `action`/подрежимы и их include-файлы (таблица выше).
- [x] Снята карта таблиц БД (см. «Таблицы БД»).
- [x] Зафиксирован уже существующий scaffold (PSR-4, services.php, AdminControllerContext, мигрированные login/system_check).
- [ ] **Уточнить статус `mail`** в `$actions`: файла `includes/mail.php` нет — мёртвая запись или обрабатывается модулем `mail`? Убрать из `$actions` либо смигрировать.
- [ ] Снять `SHOW CREATE TABLE` для `cms_ads`, `cms_counters`, `cms_ban_ip`, `cms_ban_users` (через контейнер) перед заведением Eloquent-моделей.
- [ ] Найти все внешние ссылки на admin-URL по проекту (sidebar-меню `system::app/sidebar-admin-menu`, прочие модули) — список обновлять в Фазе 6.
- [ ] Свериться с forum/profile: какие модели бан/форум/юзер уже есть — переиспользовать, не дублировать.

## Фаза 1. Scaffold структуры (Step 2 гайда) ✅ В ОСНОВНОМ ВЫПОЛНЕНО

- [x] PSR-4 `Johncms\\Modules\\Admin\\` → `modules/admin/src/` в корневом `composer.json`.
- [x] `src/Application/Controllers` + `src/Install` существуют; Installer на месте.
- [x] `config/services.php` грузит `Application\Controllers` (autowire/autoconfigure/public).
- [x] Дорастить `services.php`: load всего `Application\` с **exclude `DTO` + `Exceptions`**, load `Infrastructure\`, bind интерфейсов (по мере появления) — как в album/profile.
- [x] Создать каталоги `Domain/`, `Infrastructure/`, `Application/{DTO,Exceptions,Middlewares,UseCases,Services}` по мере необходимости. (созданы Domain/{Enums,Repository}, Infrastructure/Persistence/Repository, Application/{DTO,Middlewares,UseCases})
- [x] `composer dump-autoload` + smoke (`router:list`, `cache:clear`, `cs-check`). (psalm падает по предсуществующей причине — отсутствует путь `modules/rss` в psalm.xml.dist)

## Фаза 1b. Доступ и общий фундамент

- [x] **Определить механизм доступа** (Вариант A/B выше) — согласовать с пользователем. При A — создать `Application/Middlewares/AdminAccessMiddleware` (rights≥7, гость → редирект `/admin/login`), переиспользовать паттерн `AuthorizedUserMiddleware` из profile/album. (создан `AdminAccessMiddleware`: гость → `redirect('/admin/login')`; авторизованный с rights<7 → **403 на guest-layout без админ-сайдбара**. NB: тема admin включается `RenderEngineFactory::isAdmin()` по URL-префиксу `/admin/` ДО авторизации, поэтому `pageNotFound()` рисовал бы 404 с админ-обвесом. Решение: новый шаблон `themes/admin/templates/system/error/403.phtml` на `system::layout/guest`; middleware регистрирует translation-домен `admin` и рендерит его (`d__('admin','Access denied')` → «Доступ закрыт», кнопка `__('Home')`). Новых i18n-строк не вводил — локали компилируются в `*.lng.php`.)
- [x] Завести доменный хелпер уровней прав (констант `MODERATOR=6`/`ADMIN=7`/`SUPER_ADMIN=9`) — в album эти константы расползлись по use case; здесь сразу вынести в один enum/класс. (создан `Domain/Enums/UserRights` — backed int enum)
- [ ] Eloquent-модели по необходимости (не спекулятивно, наращиваем per-action): `Ads`, `Counter`, `BanIp`, `BanUser`. `User`/форум-модели — переиспользовать существующие.
- [ ] `*RepositoryInterface` + `Eloquent*Repository` — **наращиваются per-action в Фазе 2+** (тонкие репозитории: `Model::query()->…`, без бизнес-логики/escape/HTTP).

## Фаза 2. Действия «чтение» (низкий риск)

Простые GET-страницы. Для каждой: `Get*ContextUseCase` (+ guard внутри, если тривиально) → Controller → шаблон. Рекомендуемый порядок (от простого к сложному):

- [x] `index` → `DashboardController` (статистика за сутки: `users.lastdate`, `forum_messages.date`). Роут `GET /admin` (`admin.index`). Самое чистое чтение — начать с него. (`__invoke` → `GetDashboardContextUseCase` → `DashboardContextDTO`; `DashboardRepositoryInterface` + `EloquentDashboardRepository`: countActiveUsersSince/countRegisteredUsersSince/countForumMessagesSince — переиспользует модели `Johncms\Users\User` и `Forum\Domain\Models\ForumMessage`. Legacy catch-all переименован `admin.index` → `admin.legacy`. registered_users теперь прямой запрос `approved()+datereg` вместо кэш-сервиса `Counters`.)
- [x] `adminlist` → `StaffListController` (списки админов по уровням rights). Чтение `users`. URL `/admin/staff` (`admin.staff`). `GetStaffListUseCase` группирует по UserRights (Supervisors=9/Administrators=7/Super Moderators=6/Moderators=1–5, rights 8 не попадает — как в legacy), `AdminUserRowMapper` маппит `User` → строку `admin::user_row` (по образцу forum `ForumVisitorRowMapper`). `StaffRepositoryInterface`/`EloquentStaffRepository` тонкие. Удалён `includes/adminlist.php` + запись `adminlist` из `$actions`; href в обоих sidebar (`system::app/...` и `admin::...`) → `/admin/staff`. user_row не трогал (общий с не-мигрированными — escape-аудит в Фазе 6).
- [x] `userlist` → `UserListController` (пагинация + сортировка nick/ip). Path-сегменты для `mod`. URL: `/admin/users` (id), `/admin/users/by-nick`, `/admin/users/by-ip` (`admin.users`/`admin.users.sort`, requirements `by-nick|by-ip`). `GetUserListUseCase` → `UserListResultDTO` (Laravel `LengthAwarePaginator`); `UserListRepositoryInterface`/`EloquentUserListRepository::paginateApproved(UserListSort, page, perPage)` — тонкий, `preg=1`, orderBy по whitelisted колонке из `UserListSort` enum (id/name/ip). Строки — `AdminUserRowMapper` (переиспользован). Пагинация — `Tools::displayPagination($baseUrl.'?', offset, total, perPage)` + `PageMeta`. Удалён `includes/userlist.php` + запись в `$actions`; href в обоих sidebar → `/admin/users`. **Отличие от legacy:** `total` теперь считает только `preg=1` (как и список), а не всех пользователей — устранена рассинхронизация пагинации (legacy COUNT(*) по всем).
- [x] `ip_whois` → `IpWhoisController` (внешний whois по IP, без БД). URL `/admin/ip-whois` (`admin.ip_whois`, в admin-группе). Контроллер `Application/Controllers/Ip/IpWhoisController` (новая подпапка `Ip/` для IP-инструментов: сюда же лягут `IpSearchController`/`IpBanController`; валидирует IP через `FILTER_VALIDATE_IP`) → `GetIpWhoisUseCase` → `IpWhoisResultDTO`. Сетевой fsockopen-код вынесен в инфраструктурный адаптер `Infrastructure/Whois/SocketWhoisClient` за доменным интерфейсом `Domain/Services/WhoisClientInterface` (список whois-серверов + дедуп ответов внутри). Подсветка меток (strtr) и `nl2br` перенесены в шаблон и применяются **после** `$this->e()` (escape-on-output; ключи без HTML-спецсимволов переживают экранирование). Отказался от legacy `checkout(...,1,1)` — bbcode-обработка whois-данных бессмысленна. Удалён `includes/ip_whois.php` + запись `ip_whois` из `$actions`; href в обоих sidebar (`system::app/...` и `admin::...`) → `/admin/ip-whois`.
- [x] `search_ip` → `IpSearchController` (поиск по IP/диапазону + режим history). Пагинация. URL `/admin/ip-search` (`admin.ip_search`) + `/admin/ip-search/history` (`admin.ip_search.mode`, requirements `mode=history`), в `Application/Controllers/Ip/`. Разбор IP (одиночный/диапазон `a-b`/маска `*`) вынесен в `Application/Services/IpRangeParser` → `IpRangeParseResult` (from/to + errors). `SearchUsersByIpUseCase` → `IpSearchResultDTO` (Laravel-пагинатор + errors). `IpSearchRepositoryInterface`/`EloquentIpSearchRepository`: `paginateUsers` (whereBetween ip/ip_via_proxy) и `paginateHistory` (User join `cms_users_iphistory` + joinSub MAX(time) per user; исторический IP подставляется alias-ом `hst.ip as ip` поверх `users.*`, каст `Ip` отдаёт dotted). Строки — `AdminUserRowMapper`. Форма переведена POST→**GET** (читаемые/пагинируемые ссылки). **Отличие от legacy:** `total` для history теперь считается по тому же запросу через `paginate()` (legacy COUNT(DISTINCT user_id) расходился с выборкой по most-recent-record). **URL renamed (Вариант B, согласовано):** `/admin/search_ip` → `/admin/ip-search`; обновлены ВСЕ ~15 генераторов ссылок в forum/downloads/mail/news/guestbook/profile/online + центральные мутаторы `UserMutators`/`GuestSessionMutators`/`UserProperties` + `AdminUserRowMapper` + 2 legacy-include форум-админки + оба sidebar. Удалён `includes/search_ip.php` + запись из `$actions`. Смоук: репозиторий выполняет обе ветки без SQL-ошибок (через бутстрап контейнера).
- [x] `ban_panel` (список) → `BanListController` (амнистия — в Фазу 4, write+rights≥9). URL `/admin/bans` (`admin.bans`) + `/admin/bans/by-violations` (`admin.bans.sort`, requirements `sort=by-violations`), в `Application/Controllers/Users/`. `GetBanListUseCase` → `BanListRepositoryInterface`/`EloquentBanListRepository::paginate(BanListSort)`: база — `User`, join `cms_ban_users` + joinSub MAX(ban_time) per user (последний бан), коррелированный подзапрос `bancount`; доп. атрибуты `ban_id`/`bantime`/`bancount` на модели. Сортировка TIME(`bantime`)/VIOLATIONS(`bancount`) — enum `BanListSort`. `BanListRowMapper` композирует `AdminUserRowMapper` + `active` (bantime>now, подсветка) + кнопка «История нарушений (N)». Текущий IP пользователя (без override — в отличие от ip-search/history). **Отличие от legacy:** `total` считается тем же запросом через `paginate()` (legacy COUNT(DISTINCT user_id) включал баны удалённых юзеров; INNER JOIN на `users` их отбрасывает). **Амнистия НЕ мигрирована** (Фаза 4): legacy `includes/ban_panel.php` + запись `ban_panel` в `$actions` СОХРАНЕНЫ ради ветки `?mod=amnesty`; кнопка «Amnesty» (только rights==9) временно ведёт на `/admin/ban_panel/?mod=amnesty`. Оба sidebar → `/admin/bans`. Смоук (через бутстрап): засеял тестовые баны — most-recent на юзера, bancount и обе сортировки верны; данные удалены.

## Фаза 3. Настройки-конфиги (POST в config, без БД)

Группа «форма с GET-показом + POST-сохранением в `config/autoload/system.local.php`». Похожи между собой — общий паттерн сохранения конфига вынести в сервис/use case.

- [x] `access` → `ModulesAccessController` (вкл/выкл модулей сайта). URL `GET/POST /admin/modules-access` (`admin.modules_access` / `admin.modules_access.save`), в `Application/Controllers/Settings/`. **Заведён переиспользуемый config-writer** (для antiflood/settings/emoticons/languages): `Domain/Repository/SystemConfigRepositoryInterface` (getJohncms/saveJohncms) + `Infrastructure/Config/FileSystemConfigRepository` (var_export `['johncms'=>...]` в `system.local.php` + opcache_reset; бросает `Domain/Exceptions/ConfigWriteException` при провале записи). `UpdateModulesAccessUseCase` + `ModulesAccessDTO` (8 полей: mod_reg/forum/guest/lib/lib_comm/down/down_comm/active). Контроллер: `form()` GET / `save()` POST. **Добавлен CSRF** (`Validator(['Csrf'])` + hidden `csrf_token`) — legacy был без него. **PRG**: успех → flash `$_SESSION['success_message']` + `redirect()` (legacy ре-рендерил ту же страницу). Шаблон переписан (sidebar через контекст, escape сообщений, убран мёртвый `$cls`). Удалён `includes/access.php` + запись `access` из `$actions`; оба sidebar → `/admin/modules-access`. Смоук: writer round-trip пишет валидный PHP (`php -l`), бэкап восстановлен.
- [x] `antiflood` → `AntifloodSettingsController`. URL `GET/POST /admin/antiflood` (`admin.antiflood` / `admin.antiflood.save`), в `Application/Controllers/Settings/`. Переиспользует config-writer из `access` (`SystemConfigRepositoryInterface`/`FileSystemConfigRepository`). `UpdateAntifloodSettingsUseCase` + `AntifloodSettingsDTO` (mode/day/night/dayFrom/dayTo). **Клэмпинг диапазонов вынесен в use case** (mode 1–4, day/night 4–300, dayfrom 6–12, dayto 17–23 — было инлайн в legacy). Контроллер: `form()` GET / `save()` POST. **Добавлен CSRF** (`Validator(['Csrf'])` + hidden `csrf_token`) — legacy был без него. **PRG**: успех → flash `$_SESSION['success_message']` + `redirect()`. Шаблон переписан (sidebar через контекст `usr_menu`, escape ошибок/сообщений). Удалён `includes/antiflood.php` + запись `antiflood` из `$actions`; оба sidebar → `/admin/antiflood`. Смоук: `router:list` показывает оба роута, `cs-check` зелёный.
- [x] `settings` → `SystemSettingsController` (**rights≥9**, глобальные настройки). URL `GET/POST /admin/settings` (`admin.settings` / `admin.settings.save`), в `Application/Controllers/Settings/`. **Первое действие rights≥9 — заведён паттерн гейта (согласовано: вложенный middleware).** `SuperAdminAccessMiddleware` (зеркало `AdminAccessMiddleware`, порог `UserRights::SUPER_ADMIN`) — **самодостаточный** (гость→login, rights<9→403), т.к. `RouteCollection::compile()` НЕ пробрасывает middleware родительской группы во вложенные (проверено по коду). В `routes.php` добавлена вложенная `$superGroup` внутри admin-группы с этим middleware; будущие rights≥9 действия (ipban/karma/counters/languages/usr_del/amnesty) кладём туда же. `UpdateSystemSettingsUseCase` + `SystemSettingsDTO` (12 полей). Список тем вынесен за доменный интерфейс `Domain/Services/ThemeListProviderInterface` + `Infrastructure/Theme/FileSystemThemeListProvider` (glob `themes/*`, исключая `admin`) — по образцу whois. Config-writer переиспользован. `homeurl` rtrim('/') — в use case. **CSRF + PRG** добавлены (legacy без CSRF, ре-рендерил страницу). Шаблон переписан (sidebar `sys_menu` через контекст, escape ошибок/сообщений/`$theme`, `==`→`===`). Удалён `includes/settings.php` + запись `settings` из `$actions`; оба sidebar → `/admin/settings`. Смоук: `router:list` + DI-резолв контроллера/middleware/провайдера (темы `default,example`), `cs-check` зелёный.
- [x] `emoticons` → `EmoticonsController` (GET `/admin/emoticons` + POST rebuild). Пересборка кэша смайлов вынесена за `SmiliesScannerInterface` (`FileSystemSmiliesScanner`, инжектит legacy `Tools` для трансли­терации — биндинг через `set(...)->autowire()`) + `SmiliesCacheRepositoryInterface` (`FileSmiliesCacheRepository`, сохраняет существующий serialize-контракт кэша). `RebuildSmiliesCacheUseCase` возвращает total. CSRF + PRG (legacy пересобирал на GET). Контроллер в `System/`. Удалён `includes/emoticons.php` + запись из `$actions`; оба sidebar → `/admin/emoticons`. Смоук: пересборка дала 184 смайла.
- [x] `languages` (подкаталог) → `LanguagesController` (**rights≥9**, super-группа; index/save/manage/install/update/delete). Статический `Application/Languages.php` удалён, логика разнесена: `FileSystemLanguageFilesManager` (скан `.ini`/удаление/доступ к записи) + `HttpLanguageCatalog` (внешний каталог johncms.com + zip-установка) за доменными интерфейсами; запись конфига — `SystemConfigRepositoryInterface`. Use cases: `SaveLanguageSettingsUseCase`/`UpdateLanguagesListUseCase`/`InstallLanguageUseCase`/`RemoveLanguageUseCase`/`GetManagedLanguagesUseCase`. GET-ссылки install/update/delete → POST-формы с CSRF; PRG-флэш. Удалён `includes/languages/` + запись из `$actions`; оба sidebar → `/admin/languages`. Смоук: каталог подтянул 12 языков по HTTP, managed-список собирается.

## Фаза 4. Действия «запись» (Access Guard паттерн)

guard → context → action. Action только на POST. **CSRF** — добавить стандартный `csrf_token` + `Validator(['Csrf'])` (legacy местами без CSRF; приводим к стандарту, как в album delete/sort/vote).

- [x] `reg` → `RegistrationModerationController` (`/admin/registrations` + POST approve/approve-all/delete/delete-all/delete-by-ip). `RegistrationModerationRepositoryInterface` (User+IpHistory), `RegistrationRowMapper` (добавляет integer-IP). CSRF+PRG.
- [x] `usr_clean` → `UserCleanupController` (`/admin/users/cleanup` GET confirm + POST). `InactiveUsersRepositoryInterface` (User+ForumUnread) + системный `UserClean` каскад. CSRF+PRG.
- [x] `usr_del` → `DeleteUserController` (**rights≥9** super, `/admin/users/{id}/delete`). `GetUserDeletionContextUseCase` (guard: не себя/существует/не выше правами + счётчики через Capsule) + `DeleteUserUseCase` (UserClean). Гард повторяется на POST. Ссылка из profile обновлена.
- [x] `ban_panel` амнистия → `AmnestyController` (**rights≥9**, `/admin/bans/amnesty`). `BanAmnestyRepositoryInterface` (Ban): clearAll | unbanActiveShortTerm одним ranged UPDATE. Кнопка в BanList → новый URL. Удалён последний потребитель `includes/ban_panel.php`.
- [x] `ipban` → `IpBanController` (**rights≥9**, `/admin/ip-bans` REST). `BanIp` модель, `IpBanType` enum, `IpBanRepositoryInterface`, `PrepareIpBanUseCase` (переиспользует `IpRangeParser`, конфликты + свой IP)/Store/GetList/Manage. 6 шаблонов переписаны, CSRF, reason/url сырыми.
- [x] `karma` → `KarmaController` (**rights≥9**, `/admin/karma` + `/clear`→`/reset`). `UpdateKarmaSettingsUseCase` (config) + `KarmaRepositoryInterface` (truncate karma_users + обнуление счётчиков User). CSRF+PRG.
- [x] `counters` → `CountersController` (**rights≥9**, `/admin/counters` REST). `Counter` модель, `CounterRepositoryInterface` (CRUD + swap sort), Get/Save/Manage use cases. Код счётчиков сырой в превью, экранирован в атрибутах. 5 шаблонов, CSRF+PRG.
- [x] `ads` → `AdsController` (`/admin/ads` REST, rights≥7). `Ad` модель, `AdRepositoryInterface` (CRUD + swap по type + deleteInactive), Get/Save/Manage + `AdRowMapper` (agreement/remains/styles). Исправлен legacy-баг (в форме edit имя показывало ссылку). 3 шаблона, CSRF+PRG.

## Фаза 5. Управление форумом (подкаталог `forum/`)

`?act=forum&mod=…` — отдельный диспетчер на 8 подрежимов (структура разделов форума). Самостоятельный мини-модуль внутри admin. Мигрировать после остальных, как связный блок:

- [x] `forum/index` → `ForumDashboardController` (`/admin/forum`, счётчики через `ForumAdminRepositoryInterface`).
- [x] `forum/cat` + `forum/add` + `forum/edit` → `ForumStructureController` (list/addForm/add/editForm/edit). `ForumStructureRepositoryInterface`, `ForumSlugGenerator`, Add/Edit use cases. Slug-генерация и проверка цикла родителя — в use case.
- [x] `forum/del` → `ForumStructureController::deleteConfirm/delete` (3 ветки: пустой / перенос подразделов в др. категорию / перенос тем в др. раздел; полное каскадное удаление — rights==9). `DeleteForumSectionUseCase`, файлы стираются физически в use case (репо отдаёт имена).
- [x] `forum/htopics` / `forum/hposts` → `HiddenTopicsController` / `HiddenPostsController` (фильтры по автору/разделу/теме, bulk-purge rights==9). `HiddenForumRepositoryInterface`, `ManageHiddenForumUseCase`, `HiddenTopicRowMapper`/`HiddenPostRowMapper`.
- [x] `forum/settings` → `ForumSettingsController` (**rights≥9** super; пишет `forum.local.php` через `ForumConfigRepositoryInterface`/`FileSystemForumConfigRepository`).
- [x] **Переиспользованы модели `modules/forum/src`** (`ForumSection`/`ForumTopic`/`ForumMessage`/`ForumFile`/`ForumVote`/`ForumVoteUser`/`ForumUnread`) — без дублирования.

## Фаза 4b (вне исходного плана). `antispy`

- [x] `antispy` → `FileIntegrityController` (**rights≥9** super, `/admin/file-integrity`: меню / GET scan / GET snapshot confirm + POST create). Логика CRC32-снимка вынесена в `Infrastructure/Security/CrcFileIntegrityScanner` за `FileIntegrityScannerInterface`; Scan/CreateSnapshot use cases + DTO. Создание снимка — POST+CSRF+PRG.

## Фаза 6. Роуты и чистка (Step 6 гайда)

- [x] REST-роуты в admin-группе с `AdminAccessMiddleware`; `login`/`system_check` публично/условно вне группы. rights≥9 — во вложенной super-группе с `SuperAdminAccessMiddleware`. `{id:number}` inline.
- [x] Legacy-редиректы НЕ делали — внутренние ссылки обновлены по ходу.
- [x] Оба sidebar (`system::app/sidebar-admin-menu` тема + `admin::sidebar-admin-menu`) переведены на новые URL.
- [x] Удалены `index.php` (front-controller), весь `includes/` (+ `forum/`), legacy catch-all `admin.legacy`. `module_lib_loader`-крючков в admin не осталось.
- [x] Удалён `@deprecated BaseAdminController` (потребителей нет).
- [x] Мёртвые записи `mail`/`index` удалены вместе с диспетчером.
- [x] **Escape-аудит**: все мигрированные шаблоны экранируют вывод через `$this->e()` (ника, IP/whois, описания, meta). Сырой вывод оставлен намеренно только там, где это контракт/фича: HTML-код рекламы `cms_ads` и счётчиков `cms_counters` (превью для rights≥9), bbcode-рендер постов (`Tools::checkout`), whois (strtr-подсветка после `e()`). Хранение — сырое (escape-on-output).

## Фаза 7. Финальная проверка

- [x] `composer cs-check` — зелёный (980 файлов).
- [x] `composer test` — зелёный (61 тест, 176 assertions; 1 предсуществующая deprecation).
- [x] Сборка фронта не требуется — менялись только PHP + Plates-шаблоны (server-side), assets не трогали.
- [x] Полный route-collection компилируется (`router:list`, 0 ошибок); каждый контроллер резолвится из DI.
- [x] Репозитории/сервисы инъектируются через интерфейсы; контроллеры не зависят от инфраструктуры; права — в middleware (rights≥7/≥9) + guard в use case (usr_del), не в шаблонах.
- [ ] **Ручной прогон пользователем** под rights 7 и 9 (см. чек-лист ниже) — psalm не запускается из-за предсуществующего бага конфига (`psalm.xml.dist` → несуществующий `modules/rss`).

### Чек-лист ручного теста
rights≥7: `/admin` дашборд · `/admin/staff` · `/admin/users` (+ `/by-nick`,`/by-ip`) · `/admin/users/cleanup` · `/admin/registrations` (approve/delete/by-ip) · `/admin/ip-search` (+ `/history`) · `/admin/ip-whois` · `/admin/bans` · `/admin/modules-access` · `/admin/antiflood` · `/admin/emoticons` · `/admin/ads` (CRUD/up-down/toggle/clear) · `/admin/forum` → structure (add/edit/delete-with-move) + hidden-topics/posts.
rights≥9 (super): `/admin/settings` · `/admin/languages` (install/delete) · `/admin/users/{id}/delete` · `/admin/bans/amnesty` · `/admin/ip-bans` (CRUD) · `/admin/karma` (+reset) · `/admin/counters` (CRUD) · `/admin/forum/settings` · `/admin/file-integrity` (snapshot/scan) · форум: полное каскадное удаление раздела, bulk-purge скрытых.
Проверить: пользователь rights 7 на любом super-URL → 403; гость → редирект `/admin/login`.

---

## Принципы (из AGENTS.md)

- Малые безопасные шаги, поведение сохраняется. Не трогать несвязанные модули.
- Репозитории тонкие: только запросы (`Model::query()->…`), без бизнес-правил/escape/HTTP.
- DI через интерфейсы. `declare(strict_types=1)`, типизация, `final`, property promotion, `readonly` для новых DTO/сервисов.
- Исключения не регистрировать как DI-сервисы; `Application/Exceptions` (+ `DTO`) исключить из autoload.
- Без коммитов без явной просьбы пользователя.
- `private const X = value;` без типа (PHP 8.2, типизированные константы — 8.3+).

## Соглашения по шаблонам

- **Не дублировать `title`/`page_title` в `$this->layout(...)`.** Если контроллер передал их через `Render::addData([...])`, они доступны глобально (включая layout). В шаблоне достаточно `$this->layout('system::layout/default');`.
- Admin-шаблоны используют sidebar `system::app/sidebar-admin-menu` (через `AdminControllerContext`), namespace шаблонов модуля — `admin::`.

## Решения / отличия от album-profile (зафиксировать при старте)

- **Ступенчатый доступ** (rights≥7 / rights≥9 / public `login`) — главное отличие. Механизм (middleware + guard vs гейт в routes) согласовать **до** Фазы 2.
- **Уже есть scaffold** — Фаза 1 почти готова; не переделывать существующие login/system_check.
- **`access`/`settings`/`antiflood`/`emoticons`/`languages` пишут в config-файл**, не в БД — общий паттерн «config writer» вынести в сервис (не дублировать `var_export` в каждый контроллер).
- **GET-мутации → POST + CSRF** (reg/ipban/counters/ads/karma/amnesty/usr_del) — приводим к стандарту, как в album.
- **Уровни прав** — вынести в один enum/класс констант сразу (в album константы расползлись — не повторять).

## Решено (2026-06-06)

- ✅ **Механизм доступа**: middleware (rights≥7) + guard-use case (rights≥9). Вариант A.
- ✅ **GET-мутации → POST + CSRF** (reg/ipban/counters/ads/karma/amnesty/usr_del) — приводим к стандарту проекта.
- ✅ **Старт миграции — `index` (дашборд)**: чистое чтение, обкатать паттерн контроллера/контекста.

## Открытые вопросы (согласовать по ходу)

1. **`mail` в `$actions`**: мёртвая запись (убрать) или требует миграции? — уточнить в Фазе 0.
2. **URL-схема**: одобрить черновик clean-URL (особенно переименования `usr_del`→`users/{id}/delete`, `ban_panel`→`bans`, `ipban`→`ip-bans`, `antispy`→`file-integrity`) — согласовывать per-action.
3. **Группировка контроллеров** по подпапкам (`Users/`, `Ip/`, `Ban/`, `Forum/`, `Languages/`, `Ads/`, `Settings/`) — подтвердить при появлении. ✅ Согласовано: IP-инструменты (`ip_whois`/`search_ip`/`ipban`) → `Ip/` (не `Users/`).
