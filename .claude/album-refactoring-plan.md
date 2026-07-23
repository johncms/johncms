# План рефакторинга модуля `album` под новую архитектуру

Локальный рабочий файл (gitignored). Отмечай прогресс галочками `[x]` по мере выполнения.

**Источник истины: `MODULE_REFACTORING_GUIDE.md` (корень репо) + `AGENTS.md`.** Этот файл — конкретизация гайда под модуль `album`. При расхождениях приоритет у гайда.
Эталоны: `modules/profile/` (свежий рефакторинг по тому же гайду), `modules/forum/`, `modules/downloads/`, `modules/mail/` (Application / Domain / Infrastructure, PSR-4, REST-роуты, UseCase + Controller + RepositoryInterface).

## Процесс работы (из гайда — обязательно)

- **Одна страница за один заход.** Агент рефакторит одно действие → пользователь ревьюит → пользователь коммитит → только потом следующее. Не рефакторить несколько страниц за раз.
- Controller + UseCase держать в одном заходе (не дробить).
- Если страница тянет много файлов — сначала ядро функциональности.
- Параллелить независимые операции: чтение файлов, прогон cs-check/psalm.
- Без коммитов без явной просьбы пользователя.

---

## Текущее состояние (legacy)

`modules/album/` — query-param диспетчер, без `src/`, без PSR-4. Доступ только для авторизованных (`index.php` отбивает гостей единым `result`-экраном). Роут — один catch-all:
`/album/{action}` → `modules/album/index.php` (`album.index`).

- `index.php` — фронт-контроллер. Грузит пользователя по `?user=` (по умолчанию текущий), список `$actions`, ручной `require includes/<action>.php`. Класс `Albums\Photo` подключается через `Aura\Autoload`.
- `includes/` (16 файлов) — по одному на действие.
- `lib/Photo.php` — обёртка-«модель» над массивом строки `cms_album_files` с ленивыми аксессорами (URL, картинки, рейтинг, `can_vote`, форматирование). **Переписать на Eloquent-модель + presenter/DTO.**
- `templates/` (Plates, namespace `album::`), `locale/`, `Install/Installer.php` (namespace `Album\Install`), `config/routes.php`.

### Инвентаризация действий

| action / файл       | Назначение                                           | Доступ                                         | Метод / подрежимы                                              |
|---------------------|------------------------------------------------------|------------------------------------------------|---------------------------------------------------------------|
| `index`             | Лендинг альбомов (счётчики м/ж/новые)                | auth                                           | GET                                                           |
| `list`              | Список альбомов пользователя                         | auth; приватные скрыты (owner / rights≥6)      | GET; `?user=`                                                 |
| `users`             | Список пользователей, у кого есть фото               | auth                                           | GET; `mod=boys\|girls\|''`                                    |
| `top`               | Топ/ленты фото                                       | auth (приватные по правам)                     | GET; `mod=` `''`(new)/`last_comm`/`views`/`downloads`/`comments`/`votes`/`trash`/`my_new_comm` |
| `show`              | Просмотр альбома и отдельной фото (`view=1`)         | по `access` альбома; пароль для `access=2`      | GET (+POST пароль); `al`,`img`,`view`,`profile`              |
| `edit`              | Создать / изменить альбом                            | owner (не бан) / rights≥7                       | GET+POST; `al`(edit) / без `al`(create), `?user=`           |
| `delete`            | Удалить альбом (+файлы/голоса/комменты)              | owner / rights≥6                                | GET (форма) + POST (`delete_token`)                          |
| `image_upload`      | Загрузка фото в альбом                               | owner (не бан) / rights≥7                       | GET+POST; `al`                                               |
| `image_edit`        | Редактировать описание фото                          | owner / rights≥6                                | GET+POST; `img`                                              |
| `image_move`        | Переместить фото в другой альбом                     | owner / rights≥6                                | GET (форма) + POST; `img`,`al`                              |
| `image_delete`      | Удалить фото (+файлы/голоса/комменты)                | owner / rights≥6                                | GET (форма) + POST (`delete_token`); `img`                  |
| `image_download`    | Отдать файл + счётчик скачиваний                     | по `access` альбома                             | GET (redirect на файл); `img`                                |
| `vote`              | Голос за фото (plus/minus)                           | `can_vote` (не свой, не бан, postforum>5, рег>3д) | GET (redirect на Referer); `mod=plus\|minus`,`img`         |
| `comments`          | Комментарии к фото (`Johncms\Comments`)             | по `access` альбома                             | GET+POST; `img`                                              |
| `sort`              | Переместить альбом в сортировке вверх/вниз           | owner / rights≥7                                | GET (redirect); `mod=up\|down`,`al`                         |

### Таблицы БД

- `cms_album_cat` — альбомы (`id`,`user_id`,`sort`,`name`,`description`,`password`,`access`).
- `cms_album_files` — фото (описание, `img_name`,`tmb_name`,`time`,`comm_count`,`access`,`vote_plus/minus`,`views`,`downloads`,`unread_comments`).
- `cms_album_comments` — комментарии (через `Johncms\Comments`).
- `cms_album_votes` — голоса (`user_id`,`file_id`,`vote`).
- `cms_album_views` — уникальные просмотры (`user_id`,`file_id`,`time`).
- `cms_album_downloads` — уникальные скачивания (`user_id`,`file_id`,`time`).
- `users` — владелец/пол/онлайн.

`access` (значения из legacy): `1` — приватный (только владелец/админ), `2` — по паролю, `3` — (трактуется как закрытый, наравне с 1 в `show`), `4` — публичный. **Уточнить семантику 3 при переносе `show`/`list` — в `index`/счётчиках «новое» считается только `access=4`.**

---

## Целевая структура

```
modules/album/src/
  Application/
    Controllers/        # по контроллеру на действие; в конструкторе $controllerContext->initModule('album')
    DTO/                # контекст/команды/результаты (exclude из autoload)
    Exceptions/         # доменные исключения приложения (exclude из autoload)
    Middlewares/        # AuthorizedUserMiddleware (весь модуль под auth) — переиспользовать паттерн profile
    UseCases/           # Get*ContextUseCase / *UseCase / Ensure*AccessUseCase
    Services/           # презентер фото (бывш. Photo), обработка изображений
  Domain/
    Models/             # Eloquent: Album (cms_album_cat), AlbumPhoto (cms_album_files), AlbumVote/View/Download при необходимости
    Repository/         # *RepositoryInterface
    Enums/              # AlbumAccess (1/2/3/4), TopFilter (для top)
  Infrastructure/
    Persistence/Repository/  # Eloquent*Repository
  Install/
    Installer.php       # перенести из modules/album/Install/, namespace → Johncms\Modules\Album\Install
config/
  routes.php            # REST-роуты вместо ?act= (+ auth-группа с middleware)
  services.php          # DI: load Application/Infrastructure + bind интерфейсов
```

Namespace: `Johncms\Modules\Album\…`

**Trailing slash:** роуты определять *без* завершающего слэша, в ссылках шаблонов — единообразно (как в profile). `index.php` нормализует URI через `rtrim`.

**Clean URL mapping** (черновик, уточнять при переносе каждого действия — имена согласуются с пользователем):
- `?act=index`                      → `/album` (лендинг)
- `?act=users&mod=…`                → `/album/users[/boys|/girls]` (или query-фильтр — решить)
- `?act=top&mod=…`                  → `/album/top[/new|/views|…]`
- `?act=list&user={id}`             → `/album/user/{id:number}` (список альбомов пользователя)
- `?act=show&al={al}&user={id}`     → `/album/user/{id}/{al:number}` (альбом) и `…/{al}/photo/{img}` (одно фото, `view=1`)
- `?act=edit&user={id}[&al=]`       → `/album/user/{id}/create` и `/album/{al}/edit`
- `?act=delete&al=&user=`           → POST `/album/{al}/delete`
- `?act=sort&mod=&al=&user=`        → POST `/album/{al}/move-up` · `/move-down`
- `?act=image_upload&al=&user=`     → `/album/{al}/upload`
- `?act=image_edit&img=&user=`      → `/album/photo/{img:number}/edit`
- `?act=image_move&img=&user=`      → `/album/photo/{img}/move`
- `?act=image_delete&img=&user=`    → POST `/album/photo/{img}/delete`
- `?act=image_download&img=`        → `/album/photo/{img}/download`
- `?act=vote&mod=&img=`             → POST `/album/photo/{img}/vote/{type}`
- `?act=comments&img=`              → `/album/photo/{img}/comments`

**Названия URL не догма** — подбирать осмысленные английские слова, единообразие с forum/downloads, согласовывать конкретную схему с пользователем при переносе. Обратную совместимость (`?act=`) не держим — внутренние ссылки обновляем по ходу.

---

## Фаза 0. Анализ (Step 1 гайда) ✅ ВЫПОЛНЕНО

- [x] Зафиксированы все `action` и их include-файлы (таблица выше).
- [x] Схема таблиц снята из `Install/Installer.php` (см. раздел «Таблицы БД»).
- [x] Сверка с profile/forum/downloads: Comments-обёртка (как guestbook/downloads `FileCommentsController`), загрузка изображений через `Intervention\Image\ImageManager` (как profile Avatar/Photo), пагинация Laravel paginator.
- [x] Внешние ссылки на album по проекту (обновить в Фазе 6):
      - `modules/profile/templates/account.phtml:36` — `/album/?act=list`
      - `modules/profile/templates/view.phtml:272` — `/album/list?user=`
      - `modules/notifications/src/Application/UseCases/GetNotificationListUseCase.php:109` — `/album/?act=top&mod=my_new_comm`
      - `system/src/Counters.php:72` — `/album/?act=top` (+ блок счётчиков фото на строках 52/450)
      - `system/src/Sitemap/CoreUrlsProvider.php:23` — `/album/`
- [x] **Переиспользование:** в profile уже есть `AlbumPhotoRepositoryInterface`/`EloquentAlbumPhotoRepository` (только счётчик фото через `Capsule::table('cms_album_files')`). НЕ конфликтует — это репозиторий модуля profile. В album заводим собственные модели/репозитории.

## Фаза 1. Scaffold структуры (Step 2 гайда) ✅ ВЫПОЛНЕНО

- [x] Добавить PSR-4 в корневой `composer.json`: `"Johncms\\Modules\\Album\\": "modules/album/src/"`.
- [x] Создать три верхних каталога (+ подкаталоги по мере необходимости).
- [x] Перенести Installer → `src/Install/Installer.php`, namespace → `Johncms\Modules\Album\Install`. Старый `Install/` удалён.
- [x] Создать `config/services.php` (instanceof Console tag; load Application с exclude DTO+Exceptions; load Infrastructure). Bind интерфейсов добавим в Фазе 1b.
- [x] `composer dump-autoload`.
- [x] Smoke: `console list` / контейнер компилируется, `cache:clear` ОК, `cs-check` зелёный.

## Фаза 1b. Домен и инфраструктура (модели + репозитории)

Цель — убрать прямые `$db->query()` / сырой SQL из логики; заменить `lib/Photo.php`.

**Подход к объёму:** В 1b делаем только неспекулятивный фундамент — Eloquent-модели и enum `AlbumAccess`. Презентер, репозитории (их методы) и `TopFilter` имеют API, диктуемый конкретными потребителями (шаблоны/роуты действий), поэтому наращиваются per-action в Фазе 2+ (принцип гайда: репозитории тонкие, растут под потребность). `lib/Photo.php` и `Aura\Autoload` удаляются в Фазе 5 после переноса всех потребителей.

- [x] Eloquent-модели в `Domain/Models`:
      - [x] `Album` (`cms_album_cat`) — касты (int), связи `photos`/`user`.
      - [x] `AlbumPhoto` (`cms_album_files`) — касты (int/bool), связи `album`/`user`/`votes`, аксессор `rating`. Презентация (URL/описание/картинки/дата) → презентер в Фазе 2; `can_vote` → use case.
      - [x] `AlbumVote` (`cms_album_votes`). `cms_album_views`/`cms_album_downloads` (составной PK без `id`) — операции через query builder в репозитории без модели.
- [x] `Enums`: `AlbumAccess` (Private=1, Password=2, Public=4; `fromStored()` нормализует 3/unknown → Private, fail-closed). `TopFilter` для лент `top` — в Фазе 2 (`top`), под реальный SQL.
- [ ] `*RepositoryInterface` в `Domain/Repository` + `Eloquent*Repository` в `Infrastructure` — **наращиваются per-action в Фазе 2+**:
      - [ ] `AlbumRepositoryInterface` (альбомы юзера, поиск, сортировка, CRUD категории).
      - [ ] `AlbumPhotoRepositoryInterface` (фото в альбоме, ленты top, CRUD, счётчики views/downloads/votes). **NB:** имя совпадёт с profile-интерфейсом, но в другом namespace (`Johncms\Modules\Album\…`) — конфликта нет.
      - [ ] голоса/просмотры/скачивания — методы добавить в photo-репозиторий или отдельные интерфейсы.
- [ ] Презентер фото для шаблонов (бывш. аксессоры `Photo`): `Application/Services/PhotoPresenter` или DTO — **Фаза 2**, под первый потребитель (`top`/`list`/`show`).
- [ ] Репозитории тонкие: `Model::query()->…`, без бизнес-правил/escape/HTTP. Сложные ленты `top` — query builder (joins/subqueries), пост-фильтрацию в PHP не тащить.
- [ ] **Именование методов:** `find*` → `?Entity` (решение о null в use case); `get*` → гарантированный результат / `Collection` / paginator.
- [ ] Bind интерфейсов репозиториев в `config/services.php` (по мере появления).

## Фаза 2. Перенос действий «чтение» (низкий риск)

Простые GET-страницы. Для каждой: `Get*ContextUseCase` (+ guard внутри, если тривиально) → Controller → шаблон.

- [x] `index` → `AlbumIndexController` (лендинг, счётчики м/ж/новые). Роут `GET /album` (`album.index`) top-level + per-route `AuthorizedUserMiddleware`; legacy catch-all → `album.legacy`. UseCase `GetAlbumIndexUseCase` + `AlbumIndexDTO`; репозитории `AlbumRepositoryInterface::countOwnersBySex` / `AlbumPhotoRepositoryInterface::countNewPublicSince` + Eloquent-реализации + bind в services.php. Шаблон `index.phtml` → DTO + абсолютные ссылки. Удалён `includes/index.php`, убран `index` из legacy-диспетчера. **NB по роутингу:** матчер — plain `UrlMatcher` (первое совпадение по порядку, не статика-первой), а группы компилируются после top-level роутов → мигрированные роуты держим top-level до Фазы 5.
- [x] `users` → `UsersListController` (фильтр boys/girls, пагинация). **URL-схема: path-сегменты** — `/album/users` (`album.users`), `/album/users/boys|girls` (`album.users.filter`, requirement `boys\|girls`). UseCase `GetUsersListUseCase` + `UsersListResultDTO` (LengthAwarePaginator). Репо: `AlbumRepositoryInterface::paginateOwnersBySex` (User-модели + dynamic `count_albums` через whereExists + отдельный grouped count) и `AlbumPhotoRepositoryInterface::countByUsers` (map user_id→photo count). Видимость через приватный хелпер `applyVisibility` (access in [2,4] OR own). Пагинация — Laravel paginator + `Tools::displayPagination`. `album_url` пока ведёт на legacy `/album/list?user=` (обновить при миграции `list`). Шаблон `users.phtml` — дискретные переменные + `per_page`, упрощённый `layout()`. Удалён `includes/users.php`, убран из диспетчера. **Прецедент URL path-сегментов принят и для `top`.**
- [x] `top` → `TopController` (8 лент через `TopFilter` enum; `MyComments`/my_new_comm — owner-scoped, всегда текущий пользователь). **URL path-сегменты:** `/album/top` (New) + `/album/top/{recent-comments|views|downloads|comments|votes|worst|my-comments}`. Видимость top = **Public OR own** (строже, чем list/users: `access = 4`), модераторы (rights≥6) видят всё. Репо `AlbumPhotoRepositoryInterface::paginateTop` (match по фильтру; `recent-comments` — joinSub на max(time) за 24ч; `my-comments` — коррелированный подзапрос max(time); eager-load album/user; `select cms_album_files.*`). Новый `AlbumVoteRepositoryInterface::filterVotedPhotoIds` (батч-проверка голосов). **Презентер `PhotoPresenter` + `PhotoViewDTO`** заменяют аксессоры `lib/Photo` (URL/preview/picture/rating); URL временно ведут на legacy show/list/comments/vote (обновятся при их миграции). `can_vote` — в use case (eligibility: не бан, postforum>5, рег>3д; + не свой + не голосовал). Шаблон `top.phtml` под DTO с экранированием на выводе (`$this->e`). Обновлены внешние ссылки: notifications `/album/top/my-comments`, Counters `/album/top`. Удалён `includes/top.php`, убран из диспетчера. **NB:** константа `MODERATOR_RIGHTS = 6` теперь в 3 use case (index/users/top) — кандидат на вынос в доменный хелдер позже.
- [x] `list` → `UserAlbumsController` (список альбомов пользователя; кнопки create/edit/sort по правам). **URL: path-сегмент `/album/user/{id}` (`album.user`, requirement `id=\d+`).** UseCase `GetUserAlbumsUseCase` + `UserAlbumsResultDTO` (owner/albums Collection/canCreate/canManage). Репо: `AlbumRepositoryInterface::findUserById` (целевой юзер) + `getUserAlbums` (Album-модели с `withCount('photos')`, `orderBy(sort)`, видимость через `applyVisibility`). Права в use case: MODERATOR=6 (видит всё/manage), ADMIN=7 (create без лимита), MAX_ALBUMS=20 (create для владельца не-бан). Целевой юзер не найден → `AlbumOwnerNotFoundException` (extends ValidationException) → `system::pages/result`. Описание альбома: `checkout(desc, 0, 0)` plain + `$this->e` в шаблоне (как в `top`). URL действий (show/sort/edit/delete/create) пока на legacy. Шаблон `list.phtml` под дискретные переменные с экранированием. Контроллер чистит `$_SESSION['ap']` (как legacy). Обновлены ссылки: UsersListController `album_url`, PhotoPresenter `userAlbumsUrl`, album `index.phtml` «My Album» (через `my_albums_url` из контроллера), profile `view.phtml`/`account.phtml` → `/album/user/{id}`. Удалён `includes/list.php`, убран из диспетчера.
- [x] `show` → разбит на два контроллера: `ShowAlbumController` (сетка фото альбома) и `ShowPhotoController` (одиночное фото). **URL: плоская схема** — `/album/{al}` (`album.show`) и `/album/photo/{img}` (`album.photo`), оба `GET+POST` (POST = форма пароля), requirement `\d+`. Гард `EnsureAlbumAccessUseCase` (приват/пароль, сессия `ap`, bypass только rights≥7 как в legacy) переиспользуем в comments/download — кидает `AlbumNotFoundException`/`AlbumAccessDeniedException(ownerId)`/`AlbumPasswordRequiredException(albumId,ownerId,incorrect)`. UseCases `GetAlbumViewUseCase` (+`AlbumViewResultDTO`) и `GetPhotoViewUseCase` (+`PhotoPageResultDTO`/`PhotoDetailDTO`). Одиночное фото — навигатор «1 на страницу» по офсету (img задаёт стартовый офсет, `?page` листает); счётчик просмотров — отдельный write через `hasUserView`/`addView`/`refreshViewsCount` (значение в DTO — до инкремента, как legacy); копирование в анкету — `?profile` → `copyToProfile` в use case. `PhotoPresenter::presentDetail` + `PhotoDetailDTO` для детальной; `present()` обновлён: `detailUrl→/album/photo/{id}`, `userAlbumUrl→/album/{album_id}` (затрагивает и `top`). Шаблоны `show.phtml`/`show_one.phtml`/`enter_password.phtml` переписаны под DTO/дискретные переменные с экранированием. `mod_down_comm` читается через `config('johncms')`. Репо: `AlbumRepositoryInterface::findById`; `AlbumPhotoRepositoryInterface::findById`/`countByAlbum`/`countPhotosAfter`/`paginatePhotosByAlbum`/`getPhotoByAlbumOffset`/`hasUserView`/`addView`/`refreshViewsCount`. Обновлены внутренние ссылки на `show` в ещё-legacy includes (image_edit/delete/move/upload, comments) → `/album/{al}`, `UserAlbumsController` album_url → `/album/{id}`. Удалён `includes/show.php`, убран из диспетчера. **NB:** `lib/Photo.php` теперь используется только в `vote.php` (редирект) — удалится в Фазе 4/5.
- [x] `image_download` → `DownloadPhotoController` + `DownloadPhotoUseCase`. **URL: `GET /album/photo/{img}/download` (`album.photo.download`)**, requirement `\d+`. Переиспользует `EnsureAlbumAccessUseCase`, но bypass на уровне **модератора (rights≥6)**, не админа — legacy download был мягче show (≥7). В гард добавлен параметр `$bypassRights` (default `ADMIN_RIGHTS=7`; download передаёт `MODERATOR_RIGHTS=6`); обе константы вынесены в `public const`. Флоу: `findById` → guard (locked password / private → `AlbumAccessDeniedException`/`AlbumPasswordRequiredException`, оба → «Access forbidden») → проверка файла на диске (`AlbumPhotoFileMissingException` → «File does not exist») → счётчик `hasUserDownload`/`addDownload`/`refreshDownloadsCount` → редирект `302` на `pathToUrl(...)` (паттерн downloads `LoadFileController`). Репо доращён download-методами. `PhotoPresenter::presentDetail` `downloadUrl → /album/photo/{id}/download`. Удалён `includes/image_download.php`, убран из диспетчера. **NB:** `lib/Photo.php` всё ещё ссылается на legacy `image_download`, но используется только в `vote.php` (редирект) — удалится в Фазе 4/5.

## Фаза 3. Комментарии

- [x] `comments` → `PhotoCommentsController` — обёртка над `Johncms\Comments` (`ob_start`/`ob_get_clean`, паттерн downloads `FileCommentsController`). **URL: `GET+POST /album/photo/{img}/comments` (`album.photo.comments`)**, requirement `\d+`. Path-based `script` (без `sub_id_name`, как в downloads) — `Comments::buildUrl` сам добавляет `?`/`&`. `Comments` сам обновляет `comm_count` через `object_table='cms_album_files'`. Guard доступа — `GetPhotoCommentsContextUseCase` → `EnsureAlbumAccessUseCase` (bypass админ ≥7, как show/legacy comments; password/private → `AlbumAccessDeniedException`/`AlbumPasswordRequiredException` → «Access forbidden», back `/album/user/{owner}`). `unread_comments`: сброс при открытии владельцем (до рендера) и установка при добавлении чужим (`$comm->added`, после рендера) — оба в контроллере через `AlbumPhotoRepositoryInterface::setUnreadComments` (контроллер инжектит интерфейс домена, как downloads; не плодим тривиальные use case вопреки черновику плана). `$mod`/`$start` глобалы выставляются в контроллере; `PageMeta` для заголовка. `$_SESSION['ref']` (legacy-cruft, не используется `Comments`) пропущен. `PhotoPresenter` `commentsUrl → /album/photo/{id}/comments` (present+presentDetail). Удалён `includes/comments.php`, убран из диспетчера.

## Фаза 4. Действия «запись» (Access Guard паттерн)

guard → context → action. Action только на POST (формы с GET-показом + POST-сабмитом).

- [x] `edit` → `EditAlbumController` (create/edit; 4 метода: `createForm`/`createSave`/`editForm`/`editSave`). **URL:** `GET+POST /album/user/{id}/create` (`album.album.create[.save]`, req `id=\d+`) и `GET+POST /album/{al}/edit` (`album.album.edit[.save]`, req `al=\d+`). Guard в `GetEditAlbumContextUseCase` (тривиален, один контроллер — без отдельного `Ensure*`): `forCreate(ownerId)` (юзер существует → иначе `AlbumOwnerNotFoundException`) / `forEdit(albumId)` (альбом существует → иначе `AlbumNotFoundException`, owner = `album.user_id`), проверка «(owner && не бан) ‖ rights≥7» → `AlbumEditForbiddenException` (403). `EditAlbumContextDTO` (ownerId + ?Album + `isEdit()`). `SaveAlbumUseCase` + `SaveAlbumCommand` (name/description/password/access) — валидация 1:1 с legacy (name 2–150, description trim+≤500, пароль для access=2: 3–15 и общий >15, access 1–4, уникальность имени только на create через `existsByNameForUser`) → `AlbumValidationException(list<string> errors)`. При edit — каскад access на фото через `AlbumPhotoRepositoryInterface::setAccessForAlbum` + `AlbumRepositoryInterface::update`; при create — `AlbumRepositoryInterface::create` (sort = max+1 внутри репо). Успех → `system::pages/result` (как legacy, не редирект) с `back_url=/album/user/{owner}`. Шаблон `album_form.phtml` переведён на экранирование `$this->e()` (action_url/back_url/значения формы), `layout()` без дублей title. Ссылки в `UserAlbumsController`: `edit_url → /album/{id}/edit`, `create_url → /album/user/{owner}/create`. Удалён `includes/edit.php`, убран из диспетчера. CSRF не добавлялся (legacy без него; CSRF только для delete/image_delete по плану).
- [x] `delete` → `DeleteAlbumController` (`confirm` GET + `delete` POST). **URL:** `GET /album/{al}/delete` (`album.album.delete`) + `POST /album/{al}/delete` (`album.album.delete.submit`), req `al=\d+`. **CSRF:** `delete_token` заменён на стандартный `csrf_token` + `Validator(['Csrf'])` (как profile karma/ban). Guard в `GetDeleteAlbumContextUseCase` (тривиален, один контроллер): альбом существует (`findById` → иначе `AlbumNotFoundException`, 403) + «owner ‖ rights≥6» (legacy не проверяет бан на удаление) → иначе `AlbumEditForbiddenException` (403). `DeleteAlbumContextDTO` (Album). `DeleteAlbumUseCase` каскадно: грузит фото (`getByAlbum`), `@unlink` img+tmb по пути `UPLOAD_PATH users/album/{ownerId}/`, удаляет голоса/комменты по photoIds, фото по альбому, затем сам альбом. Успех → `system::pages/result` (как legacy) + `back_url=/album/user/{owner}`. **Не удаляет** `cms_album_views`/`downloads` — 1:1 с legacy (оставляет, как было). Новый шаблон `confirm_delete.phtml` (csrf + `$this->e()`); legacy `image_delete.phtml` оставлен для ещё-legacy `image_delete.php`. Новые репо-методы (переиспользуются в `image_delete`): `AlbumPhotoRepositoryInterface::getByAlbum`/`deleteByAlbum`, `AlbumVoteRepositoryInterface::deleteByPhotoIds`, новый `AlbumCommentRepositoryInterface::deleteByPhotoIds` (+ модель `AlbumComment`, `EloquentAlbumCommentRepository`, bind в services.php), `AlbumRepositoryInterface::delete`. Ссылка `delete_url` в `UserAlbumsController` → `/album/{id}/delete`. Удалён `includes/delete.php`, убран из диспетчера.
- [x] `sort` → `SortAlbumController` (`moveUp`/`moveDown`). **URL:** `POST /album/{al}/move-up` (`album.album.move-up`) / `POST /album/{al}/move-down` (`album.album.move-down`), req `al=\d+`. **Legacy GET → POST** (Access Guard) + **CSRF** (`csrf_token` + `Validator(['Csrf'])`). Guard в `GetSortAlbumContextUseCase` (возвращает `Album`): альбом существует (`findById` → иначе `AlbumNotFoundException`) + «owner ‖ rights≥7» (legacy без проверки бана) → иначе `AlbumEditForbiddenException` (403). Обмен `sort` с соседом — в `MoveAlbumUseCase` (moveUp/moveDown → приватный `swapSort`): сосед через новые репо-методы `AlbumRepositoryInterface::findPreviousBySort` (sort< DESC) / `findNextBySort` (sort> ASC), запись через `setSort`. Редирект на `/album/user/{owner}` (как legacy). Ссылки Up/Down в `list.phtml` переведены с `<a>` на инлайн POST-формы (`d-inline`, `btn btn-link`, hidden `csrf_token`); `UserAlbumsController` `up_url`/`down_url` → `/album/{id}/move-up|move-down`. Удалён `includes/sort.php`, убран из диспетчера.
- [x] `image_upload` → `UploadPhotoController` (`form` GET + `upload` POST). **URL:** `GET+POST /album/{al}/upload` (`album.album.upload[.submit]`), req `al=\d+`. Guard в `GetUploadPhotoContextUseCase` (возвращает `Album`): альбом существует + «owner не бан ‖ rights≥7» (как edit) → `AlbumNotFoundException`/`AlbumEditForbiddenException` (403). `UploadPhotoUseCase`: проверка размера (`flsz`) → `ImageUploadException` (новый, extends `RuntimeException`, как profile); создание каталога; обработка через `Intervention\Image\ImageManager` — оригинал resize 1920×1080 + превью 400×300 с блюр-подложкой (`fit`+`blur(20)`+`insert center`), 1:1 с legacy; запись через новый `AlbumPhotoRepositoryInterface::create` (access = access альбома, description `mb_substr` ≤1500). Ошибки загрузки → ре-рендер формы с `error_message` (как legacy); успех → `system::pages/result` + `back_url=/album/{al}` («Continue»). Шаблон `add_photo.phtml` — экранирование `$this->e()`, `layout()` без дублей (CSRF нет, как в legacy/profile-фото). Ссылка `upload_url` в `ShowAlbumController` → `/album/{al}/upload`. Удалён `includes/image_upload.php`, убран из диспетчера.
- [x] `image_edit` → `EditPhotoController` (`form` GET + `save` POST; только описание ≤1500). **URL:** `GET+POST /album/photo/{img}/edit` (`album.photo.edit[.save]`), req `img=\d+`. Guard в `GetEditPhotoContextUseCase` (возвращает `AlbumPhoto`): фото существует + «owner ‖ rights≥6» → `AlbumPhotoNotFoundException`/`AlbumEditForbiddenException` (403). `EditPhotoUseCase`: `trim`+`mb_substr` ≤1500 → новый `AlbumPhotoRepositoryInterface::updateDescription`. Успех → `system::pages/result` + `back_url=/album/{album_id}` («Continue»). Превью в форме — `pathToUrl(... tmb_name)`. Шаблон `edit_photo.phtml` — экранирование `$this->e()`, `layout()` без дублей, убраны неиспользуемые `form_data`/error-блок/`enctype`. Презентер `editUrl → /album/photo/{id}/edit` (`show_one`). Удалён `includes/image_edit.php`, убран из диспетчера.
- [x] `image_move` → `MovePhotoController` (`form` GET + `move` POST). **URL:** `GET+POST /album/photo/{img}/move` (`album.photo.move[.submit]`), req `img=\d+`. Guard в `GetMovePhotoContextUseCase` (возвращает `AlbumPhoto`): фото существует + «owner ‖ rights≥6» → `AlbumPhotoNotFoundException`/`AlbumEditForbiddenException` (403). Форма: список альбомов владельца (кроме текущего) через новый `AlbumRepositoryInterface::getUserAlbumsExcept` (orderBy sort); если пусто → info-результат «создайте ещё один альбом» (back `/album/user/{owner}`). `MovePhotoUseCase::execute(photo, targetId)`: целевой альбом существует и принадлежит владельцу фото → иначе `AlbumNotFoundException` («Wrong data»); перенос через новый `AlbumPhotoRepositoryInterface::moveToAlbum` (album_id + access = access целевого альбома); возвращает targetId для редиректа. Успех → `system::pages/result` + `back_url=/album/{target}` («Continue»). Шаблон `move_photo.phtml` — экранирование `$this->e()` (имена альбомов передаются сырыми), `layout()` без дублей. Презентер `moveUrl → /album/photo/{id}/move` (`show_one`). Удалён `includes/image_move.php`, убран из диспетчера.
- [x] `image_delete` → `DeletePhotoController` (`confirm` GET + `delete` POST). **URL:** `GET /album/photo/{img}/delete` (`album.photo.delete`) + `POST /album/photo/{img}/delete` (`album.photo.delete.submit`), req `img=\d+`. **CSRF: `delete_token` → `csrf_token`** (+ `Validator(['Csrf'])`, как DeleteAlbum). Guard в `GetDeletePhotoContextUseCase` (тривиален, один контроллер; возвращает `AlbumPhoto`): фото существует (`findById` → иначе `AlbumPhotoNotFoundException`) + «owner ‖ rights≥6» → иначе `AlbumEditForbiddenException` (оба → 403). `DeletePhotoUseCase::execute(photo)`: `@unlink` img+tmb по `UPLOAD_PATH users/album/{ownerId}/`, `voteRepository->deleteByPhotoIds([id])`, `commentRepository->deleteByPhotoIds([id])`, новый `AlbumPhotoRepositoryInterface::deleteById`. **Не удаляет** views/downloads (1:1 с legacy). Успех → `system::pages/result` + `back_url=/album/{album_id}`. **Переиспользует** generic-шаблон `confirm_delete.phtml` (как DeleteAlbum). Презентер `deleteUrl → /album/photo/{id}/delete` (`presentDetail`/`show_one`). Удалены `includes/image_delete.php` и legacy-шаблон `templates/image_delete.phtml`, убран из диспетчера. Новая строка `Image successfully deleted` (стиль `Image successfully changed/moved`, добавится при регенерации `.pot`).
- [x] `vote` → `VotePhotoController` (`__invoke(img, type)`, POST plus/minus). **URL:** `POST /album/photo/{img}/vote/{type}` (`album.photo.vote`), req `img=\d+`, `type=plus|minus`. **Legacy GET+redirect на Referer → POST + CSRF** (`csrf_token` + `Validator(['Csrf'])`, как sort) + редирект на детальную `/album/photo/{img}` (без открытого редиректа по Referer). Флоу guard → context → action: `GetVotePhotoContextUseCase` (фото существует → `AlbumPhotoNotFoundException`, без access-проверок) → `EnsureVoteAccessUseCase` (нетривиальная политика: не своё фото, не бан, postforum>5, рег>3 суток, не голосовал ранее → `VoteNotAllowedException` → «You cannot vote for this photo.») → `VotePhotoUseCase` (`addVote` + атомарный `incrementVotePlus`/`incrementVoteMinus`). Новый enum `Domain/Enums/VoteType` (Plus/Minus, backed string для URL + `storedValue()` → 1/-1). Новые репо-методы: `AlbumVoteRepositoryInterface::hasUserVote`/`addVote`, `AlbumPhotoRepositoryInterface::incrementVotePlus`/`incrementVoteMinus`. Презентер `likeUrl`/`dislikeUrl` → `/album/photo/{id}/vote/plus|minus` (present + presentDetail); устаревший doc-комментарий о немигрированных URL удалён (все photo-действия мигрированы). Шаблоны `top.phtml`/`show.phtml`/`show_one.phtml`: vote-`<a>` → инлайн POST-формы (`d-inline`, `btn btn-link`, hidden `csrf_token` из глобального шаблонного `$csrf_token`). Удалён `includes/vote.php`, `vote` убран из диспетчера `index.php` (остался только орфанный `new_comm` без файла — чистка `index.php`/`lib/Photo.php`/Aura в Фазе 5). **NB:** `lib/Photo.php` теперь полностью не используется — удаляется в Фазе 5.

## Фаза 5. Роуты и чистка (Step 6 гайда)

- [x] Переписать `config/routes.php` на REST-роуты в auth-группе с `AuthorizedUserMiddleware` (весь модуль требует авторизации). Все 27 роутов в одной `$router->group('', …)` + `addMiddleware`, числовые параметры через inline `{id:number}`/`{al:number}`/`{img:number}` (как profile); enum-параметры `filter`/`type` — через `->requirements()`. Порядок регистрации сохранён (статические `users`/`top`/`user` до числовых catch-all), числовые requirements исключают коллизии. Проверено `router:list` (27 роутов) + `cache:clear`.
- [x] **Legacy-редиректы НЕ делаем** — внутренние ссылки обновлялись по ходу миграции.
- [x] Обновить внешние ссылки (Фаза 0 список): account.phtml/view.phtml (`/album/user/{id}`), notifications (`/album/top/my-comments`), Counters.php (`/album/top`) — обновлены по ходу Фаз 2–4; Sitemap `CoreUrlsProvider` `/album/` → `/album` — обновлён здесь. (Counters `album()`/`albumCounters()` — сырой SQL Counters-модуля, вне области album.)
- [x] Удалить `index.php`, `includes/`, `lib/Photo.php` + legacy catch-all роут (`album.legacy`). `Aura\Autoload`-регистрация удалена вместе с `index.php`. Шаблоны/переводы покрываются `ControllerContext::initModule('album')` во всех контроллерах.
- [x] Шаблоны `templates/` (namespace `album::`) регистрируются через `ControllerContext::initModule('album')`.
- [x] **Escape-аудит** (полный input→output, согласовано 2026-06-06). **Модель:** ввод хранится сырым (в save use cases нет `htmlspecialchars`/`checkout` — проверено); вывод экранируется ровно один раз через `$this->e()` в шаблонах; rich-контент (`formattedDescription`) = `checkout(mode 1)`+`smilies` → безопасный HTML, выводится сырым; navChain получает сырые имена (breadcrumbs-шаблон сам экранирует через `$this->e()`). **Найдено и исправлено:**
      - **XSS:** `user_row.phtml` выводил `nick` (имя пользователя) и `album_url` без экранирования (затрагивало страницы `list` и `users`) → добавлен `$this->e()`. Для единообразия экранирован и фильтр в `users.phtml`.
      - **Двойное экранирование** (`checkout()` делает `htmlentities` безусловно — стр. 125 Tools.php — + повторный `$this->e()`/breadcrumbs `$this->e()` → видимый `&amp;amp;`): убран `checkout` из `PhotoPresenter::previewText` (теперь сырой trim+truncate), `PhotoPresenter::presentDetail` albumName, `UserAlbumsController` description, navChain в `ShowAlbumController` и `PhotoCommentsController`. Неиспользуемая инъекция `Tools` удалена из `UserAlbumsController`/`PhotoCommentsController`.

## Фаза 6. Финальная проверка

- [ ] `docker exec $(docker ps -q -f name=johncms9.php-fpm) composer cs-check` (фикс: `composer cs-fix`)
- [ ] `docker exec $(docker ps -q -f name=johncms9.php-fpm) composer test`
- [ ] Сборка фронта, если менялись assets.
- [ ] Ручной прогон всех страниц альбома (список, просмотр, пароль, загрузка/редактирование/удаление/перемещение фото, голос, комментарии, топы, сортировка).
- [ ] Репозитории/сервисы инъектируются через интерфейсы; контроллеры не зависят от инфраструктуры.

---

## Принципы (из AGENTS.md)

- Малые безопасные шаги, поведение сохраняется. Не трогать несвязанные модули.
- Репозитории тонкие: только запросы (`Model::query()->…`), без бизнес-правил/escape/HTTP.
- DI через интерфейсы. `declare(strict_types=1)`, типизация, `final`, property promotion, `readonly` для новых DTO/сервисов.
- Исключения не регистрировать как DI-сервисы; `Application/Exceptions` (+ `DTO`) исключить из autoload.
- Без коммитов без явной просьбы пользователя.

## Соглашения по шаблонам

- **Не дублировать `title`/`page_title` в `$this->layout(...)`.** Если контроллер уже передал их через `Render::addData([...])`, они доступны глобально всем шаблонам, включая layout (`Template::__construct` сеет данные из `engine->getData()`). В шаблоне достаточно `$this->layout('system::layout/default');` без массива. Передавать данные в `layout()` только если они специфичны именно для layout и не заданы через `addData`.

## Решения (согласовано с пользователем 2026-06-05)

- **`lib/Photo.php`** → Eloquent-модель `AlbumPhoto` + репозиторий; вычисляемую/презентационную логику (URL, рейтинг, форматирование) разносим по сервисам (`Application/Services`), бизнес-логику (`can_vote`) — в use case. (Подход «Eloquent + Presenter».)
- **`access=3`** — мёртвое legacy-значение: форма создаёт только 1/2/4, в БД данных нет. Нормализуем: enum `AlbumAccess {Private=1, Password=2, Public=4}` без `3`; `tryFrom(3) === null` трактуется как **приватный** (fail-closed, как в `show.php`). Семантика видимости сохраняется: список/users/счётчик-альбомов = `access != Private`; `top`/счётчик «новое» = `access == Public`.
- **`vote`/`sort`** → перевести с GET на POST (Access Guard), как в profile.
- **`delete`/`image_delete`** → `delete_token` заменить на стандартный `csrf_token` + Csrf-валидатор, как в profile.

## Открытые вопросы (согласовать при переносе соответствующих действий)

- URL-схема: вложение фото под `/album/photo/{img}` vs под альбом; нужны ли `user/{id}` в URL списка/альбома. (Уточнять в Фазе 2/4.)
- С какого действия начинать (рекомендация: `index` — чистое чтение, минимум зависимостей).
