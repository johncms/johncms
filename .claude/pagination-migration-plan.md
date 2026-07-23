# План миграции на новую пагинацию (`Johncms\Http\Pagination`)

Дата анализа: 2026-06-11. Продолжение этапа 4 из `guestbook-testing-plan.md`.

Цель: убрать все использования старого форка `johncms/johncms-pagination`
(Laravel `LengthAwarePaginator` / `->paginate()`) и легаси `Tools::displayPagination`,
после чего удалить форк из composer и легаси-ветку шаблона пагинации.

Гайдлайны миграции — в AGENTS.md (раздел «Pagination»). Эталонная реализация — модуль guestbook
(коммит `c4c08ce7`): репозиторий `count*()` + `get*(…, $limit, $offset)`, use case `count()` + `getPage()`,
в контроллере `PaginationFactory` + `PaginationGuard` (guard — только на GET-ветке), `$pagination->render()`.

## Паттерны старого кода (что искать в каждом модуле)

1. `->paginate(...)` в репозиториях/контроллерах + `LengthAwarePaginator` в интерфейсах, use cases и DTO;
   в шаблон обычно уходит `$paginator->render()` (HTML-строка) — для шаблона замена прозрачна.
2. `$this->tools->displayPagination($url, $start, $total, $kmess)` в контроллерах со «старым» стилем
   (ручной `$start` из query + limit/offset запросы) — здесь замена проще: `PaginationFactory::create($total)`
   уже даёт `getOffset()`/`getPerPage()`.
3. DTO с полем-пагинатором (`*ResultDTO`) — заменить на `total` + готовые данные, пагинация остаётся в контроллере.

Не забывать: `PageMeta` для `title`/`description`, редирект через `PaginationGuard` на GET,
`composer cs-check` + `composer test` перед коммитом.

## Помодульный план (порядок: от простого к сложному)

Статус: `—` не начато, `✅` готово.

### ✅ 0. guestbook — пилот (закоммичен, `c4c08ce7`)

### ✅ 1. online (S) — готово (полный рефакторинг: 2 репозитория + 4 use cases + 4 контроллера)

- `modules/online/src/Application/Controllers/IndexController.php` — `->paginate()`
- `modules/online/src/Application/Controllers/HistoryController.php` — `->paginate()`
- `modules/online/src/Application/Controllers/GuestController.php` — `->paginate()`
- `modules/online/src/Application/Controllers/IpController.php` — `displayPagination`

Запросы прямо в контроллерах — по правилам AGENTS.md заодно вынести в use cases/repository
(если объём растёт — допустимо мигрировать пагинацию без полного рефакторинга, отдельным решением).

### ✅ 2. help (S) — готово (полный рефакторинг: 4 use cases + 4 контроллера, ФС/конфиг)

- `modules/help/src/Application/Controllers/MySmiliesController.php`
- `modules/help/src/Application/Controllers/AdminSmiliesController.php`
- `modules/help/src/Application/Controllers/UserSmiliesController.php`
- `modules/help/src/Application/Controllers/AvatarListController.php`

### ✅ 3. notifications (S) — готово (репозиторий `countAll()`/`getPage()` + интерфейс + use case `count()`/`getPage()` + контроллер)

- `modules/notifications/src/Infrastructure/Persistence/Repository/EloquentNotificationRepository.php` — `->paginate()`
- `modules/notifications/src/Domain/Repository/NotificationRepositoryInterface.php` — `LengthAwarePaginator`

### ✅ 4. community (S) — готово (репозиторий: 4 `paginate*` → пары `countApproved*`/`getApproved*`; 4 use case `count()`/`getPage()`; 4 контроллера; удалены 2 result-DTO, заголовки в контроллерах)

- `modules/community/src/Infrastructure/Persistence/Repository/CommunityUserRepository.php`
- `modules/community/src/Domain/Repository/CommunityUserRepositoryInterface.php`

### ✅ 5. news (M) — готово (Article count/getArticles; Section/Search/Admin контроллеры; CommentsController отдаёт плоский Laravel-shape JSON для Vue из нового Pagination; 4 шаблона)

- `modules/news/src/Application/Controllers/SearchController.php` — 2× `->paginate()`
- `modules/news/src/Application/Controllers/CommentsController.php` — 2× `->paginate()`
- `modules/news/src/Application/Controllers/Admin/AdminController.php` — 2× `->paginate()`
- `modules/news/src/Application/Article.php` — `->paginate()` + `LengthAwarePaginator`
- `modules/news/templates/admin/sections.phtml` — типизация/обращения к `LengthAwarePaginator`

### ✅ 6. album (M) — готово (2 репозитория: paginate* → count*/get* c общими query-builder и reorder() в countTop; use cases count/getPage, guard в Album/PhotoView через loadAccessibleAlbum; удалены TopResultDTO/UsersListResultDTO; 4 контроллера; ShowPhoto — perPage=1 c явным currentPage)

- `modules/album/src/Infrastructure/Persistence/Repository/EloquentAlbumRepository.php` — `->paginate()`
- `modules/album/src/Infrastructure/Persistence/Repository/EloquentAlbumPhotoRepository.php` — 2× `->paginate()`
- `modules/album/src/Domain/Repository/{AlbumRepositoryInterface,AlbumPhotoRepositoryInterface}.php`
- `modules/album/src/Application/DTO/{TopResultDTO,UsersListResultDTO}.php` — поле-пагинатор
- Контроллеры с `displayPagination`: `TopController`, `ShowAlbumController`, `ShowPhotoController`, `UsersListController`

### ✅ 7. profile (M) — готово (4 репозитория paginate* → count*/get*; 5 use cases split count()/getPage() c guard-проверками в обоих; 4 контроллера; убраны total/pagination из 4 DTO; переиспользован countVotesReceivedAfter)

- `modules/profile/src/Infrastructure/Persistence/Repository/EloquentProfileActivityRepository.php` — 3× `->paginate()`
- `modules/profile/src/Infrastructure/Persistence/Repository/EloquentKarmaRepository.php` — 2× `->paginate()`
- `modules/profile/src/Infrastructure/Persistence/Repository/EloquentIpHistoryRepository.php` — `->paginate()`
- `modules/profile/src/Infrastructure/Persistence/Repository/EloquentBanRepository.php` — `->paginate()`
- `modules/profile/src/Domain/Repository/{ProfileActivityRepositoryInterface,KarmaRepositoryInterface,IpHistoryRepositoryInterface,BanRepositoryInterface}.php`
- `modules/profile/src/Application/UseCases/GetActivityUseCase.php` — `LengthAwarePaginator`

### ✅ 8. mail (M/L) — готово (2 репозитория paginate* → count*/get*; conversations через distinct count + hydrateConversationUsers; удалён мёртвый getIncoming/OutgoingGrouped; 5 use cases split count()/getPage(); 5 контроллеров; убраны total/pagination из 4 DTO)

- `modules/mail/src/Infrastructure/Persistence/Repository/EloquentMailMessageRepository.php` — 6× `->paginate()`
- `modules/mail/src/Infrastructure/Persistence/Repository/EloquentContactRepository.php` — `->paginate()`
- `modules/mail/src/Domain/Repository/{MailMessageRepositoryInterface,ContactRepositoryInterface}.php`
- Use cases: `GetIncomingConversationsUseCase`, `GetOutgoingConversationsUseCase`, `GetConversationUseCase`,
  `GetContactListUseCase`, `GetAttachedFilesUseCase`

### ✅ 9. library (M) — готово (6 контроллеров: displayPagination → PaginationFactory/Guard + render(); Section/NewArticles/Tags/Premod/Search — offset/limit из пагинации, репозиторий forPage оставлен page-based; ArticleController — perPage=1 c явным currentPage)

- `SectionController`, `ArticleController`, `SearchController`, `NewArticlesController`,
  `TagsController`, `PremodController` (все в `modules/library/src/Application/Controllers/`)

### ✅ 10. downloads (L) — готово (репозиторий: 6 paginate* → count*/get*; topUsers через distinct count + hydration; 6 use cases split count()/getPage(); 6 result-DTO paginator→Collection; 9 контроллеров: 6 на use cases + Index/DownloadCategory/FilesModeration на inline-запросах, PaginationFactory/Guard)

- `modules/downloads/src/Infrastructure/Persistence/Repository/DownloadFileRepository.php` — 6× `->paginate()`
- `modules/downloads/src/Domain/Repository/DownloadFileRepositoryInterface.php`
- DTO с пагинатором: `CommentsReviewResultDTO`, `NewFilesResultDTO`, `UserFilesResultDTO`,
  `FavoritesResultDTO`, `TopUsersResultDTO`, `SearchFilesResultDTO`
- Контроллеры с `displayPagination`: `IndexController`, `DownloadCategoryController`, `SearchController`,
  `NewFilesController`, `UserFilesController`, `FavoritesController`, `TopUsersController`,
  `CommentsReviewController`, `FilesModerationController`

### ✅ 11. forum (L) — готово (2 репозитория paginate* → count*/get*; topic/section use cases считают total+слайс без рендера, Pagination строит контроллер из total+page (без guard — сохранён ЧПУ/«к последнему сообщению»); 6 displayPagination-контроллеров + ViewForumFilesUseCase (рендер убран в контроллер))

- `modules/forum/src/Infrastructure/Persistence/Repository/ForumTopicRepository.php` — `->paginate()`
- `modules/forum/src/Infrastructure/Persistence/Repository/ForumMessageRepository.php` — `->paginate()`
- `modules/forum/src/Domain/Repository/{ForumTopicRepositoryInterface,ForumMessageRepositoryInterface}.php`
- `displayPagination`: `ForumSearchController`, `UnreadTopicsController`, `TopicsPeriodController`,
  `PollVotersController`, `ViewTopicVisitorsController`, `ViewForumVisitorsController`,
  `ViewForumFilesUseCase` (use case знает про рендер — заодно поправить)
- Особенность: в темах форума URL страниц участвуют в «перейти к последнему сообщению» и ЧПУ — проверить руками.

### ✅ 12. admin (L) — готово (7 репозиториев paginate* → count*/get*; banlist/ipsearch — joinSub-запросы с общими query-builder; 7 use cases split count()/getPage(); UserList/IpSearch DTO paginator→Collection; 8 контроллеров PaginationFactory/Guard; JSON-для-Vue не встретился)

- Репозитории с `->paginate()` (+ их интерфейсы в `Domain/Repository/`):
  `EloquentUserListRepository`, `EloquentBanListRepository`, `EloquentIpBanRepository`,
  `EloquentIpSearchRepository`, `EloquentRegistrationModerationRepository`, `EloquentAdRepository`,
  `EloquentHiddenForumRepository`
- Use cases с `LengthAwarePaginator`: `GetBanListUseCase`, `GetIpBanListUseCase`,
  `GetPendingRegistrationsUseCase`, `GetAdListUseCase`, `ManageHiddenForumUseCase`
- DTO: `UserListResultDTO`, `IpSearchResultDTO`
- Контроллеры с `displayPagination`: `Users/UserListController`, `Users/BanListController`,
  `Users/RegistrationModerationController`, `Ip/IpBanController`, `Ip/IpSearchController`,
  `Settings/AdsController`, `Forum/HiddenTopicsController`, `Forum/HiddenPostsController`
- Если где-то пагинация уходит в JSON для Vue — использовать `$pagination->getItems()`
  (возможно понадобится добавить `toArray()` в `Pagination`).

### ✅ 13. Системное ядро — готово

- `system/src/Comments.php` — `displayPagination` заменён на `Johncms\Http\Pagination` (через `di(PaginationFactory)`,
  currentPage из global `$start`/kmess); удалён неиспользуемый `queryBase()`.

### ✅ 14. Финал — удаление форка (готово)

Все модули мигрированы (grep по `paginate(`/`displayPagination`/`LengthAwarePaginator` пуст). Выполнено:
1. ✅ `composer remove johncms/johncms-pagination`, удалён path-репозиторий из `composer.json` и папка `system/third-party/johncms-pagination`.
2. ✅ Удалён `Tools::displayPagination` (+ неиспользуемый импорт `Render`) в `system/src-legacy/Tools.php`.
3. ✅ Удалена легаси-ветка (`! isset($item['type'])`) из `themes/default` и `themes/admin` шаблонов `system/app/pagination`.
4. ✅ Обновлён CHANGELOG (docs-гайд уже описывает новый компонент).

---

### Исходные шаги (для истории)

Делается только когда `grep -r "paginate(\|displayPagination\|LengthAwarePaginator" modules system/src` пуст:

1. `composer remove johncms/johncms-pagination` + удалить path-репозиторий из `composer.json`
   (`repositories` → `system/third-party/johncms-pagination`) и папку `system/third-party/johncms-pagination`.
2. Удалить `Tools::displayPagination` (`system/src-legacy/Tools.php:190`).
3. Удалить легаси-ветку (без `isset($item['type'])`) из шаблонов
   `themes/default/templates/system/app/pagination.phtml` и `themes/admin/templates/system/app/pagination.phtml`.
4. Обновить CHANGELOG и при необходимости docs.

## Правила выполнения

- Один модуль = один PR/коммит (`refactor(<module>): migrate to new pagination component`).
- Поведение сохранять 1:1 (размер страницы, фильтры, сортировки); канонизация URL через `PaginationGuard` —
  ожидаемое улучшение, отметить в CHANGELOG один раз.
- Проверка живьём: рендер, активная страница, редиректы `?page=1`/`?page=999`/`?page=abc`.
- По возможности добавлять юнит-тесты на use cases по образцу `tests/Unit/Modules/Guestbook/`.
