# План рефакторинга модуля `profile` под новую архитектуру

Локальный рабочий файл (gitignored). Отмечай прогресс галочками `[x]` по мере выполнения.

**Источник истины: `MODULE_REFACTORING_GUIDE.md` (корень репо) + `AGENTS.md`.** Этот файл — конкретизация гайда под модуль `profile`. При расхождениях приоритет у гайда.
Эталоны: `modules/forum/`, `modules/mail/`, `modules/downloads/` (Application / Domain / Infrastructure, PSR-4, REST-роуты, UseCase + Controller + RepositoryInterface).

## Процесс работы (из гайда — обязательно)

- **Одна страница за один заход.** Агент рефакторит одно действие → пользователь ревьюит → пользователь коммитит → только потом следующее. Не рефакторить несколько страниц за раз.
- Controller + UseCase держать в одном заходе (не дробить).
- Если страница тянет много файлов — сначала ядро функциональности.
- Параллелить независимые операции: чтение файлов, прогон cs-check/psalm.

---

## Текущее состояние (legacy)

`modules/profile/` — query-param диспетчер, без `src/`, без `composer`/PSR-4.

- `index.php` — фронт-контроллер. Авторизация обязательна. Параметры `?act=…&mod=…&user=…&id=…`.
  Глобальные `is_contact()`, список `$mods`, ручной `require includes/<act>.php`.
- `skl.php` — восстановление пароля (**публично, без авторизации**). `?act=sent|set`.
- `includes/` (14 файлов, ~2100 строк) — по одному на `act`.
- `templates/` (Plates), `locale/`, `Install/Installer.php`, `config/routes.php`.

### Инвентаризация действий

| act / файл              | Назначение                          | Доступ                | Подрежимы `mod`            |
|-------------------------|-------------------------------------|-----------------------|----------------------------|
| `index`                 | Просмотр профиля                    | auth                  | —                          |
| `office`                | Личный кабинет                      | только владелец       | —                          |
| `stat`                  | Статистика пользователя             | auth                  | —                          |
| `ip`                    | История IP                          | admin или владелец    | —                          |
| `guestbook`             | Гостевая пользователя (Comments)    | auth                  | —                          |
| `reset`                 | Сброс настроек пользователя         | rights>=7             | —                          |
| `confirm_new_email`     | Подтверждение смены email по коду   | по коду               | —                          |
| `activity`              | Активность (комменты/темы)          | auth                  | `comments`, `topic`        |
| `ban`                   | Управление баном                    | admin                 | `do`,`cancel`,`delete`,`delhist` |
| `edit`                  | Редактирование профиля              | владелец/admin        | POST-сохранение            |
| `images`                | Аватар/фото                         | владелец/admin        | `vote`,`delete`,`clean`,`new` |
| `karma`                 | Карма (голосование/история)         | auth                  | `vote`,`delete`,`clean`,`new` |
| `password`              | Смена пароля                        | владелец              | POST-сохранение            |
| `settings`              | Настройки                           | владелец              | `mail`, `forum`            |
| `skl.php`               | Восстановление пароля               | **public**            | `sent`, `set`              |

---

## Целевая структура

```
modules/profile/src/
  Application/
    Controllers/        # по контроллеру на действие; в конструкторе $controllerContext->initModule('profile')
    DTO/                # контекст/результаты (exclude из autoload — только если каталог не пуст)
    Exceptions/         # доменные исключения приложения (exclude из autoload)
    Middlewares/        # AuthorizedUserMiddleware (auth-группа). NB: в гайде Middleware, в mail — Middlewares; следовать mail
    UseCases/           # бизнес-логика: Get*ContextUseCase / *UseCase / Ensure*AccessUseCase
    Services/           # PasswordRecoveryService, LegacyRedirectResolver и т.п.
  Domain/
    Models/             # Eloquent-модели, если профильные таблицы ещё не покрыты
    Repository/         # *RepositoryInterface
    Entities/ Enums/    # по необходимости
  Infrastructure/
    Persistence/Repository/  # Eloquent*Repository
  Install/
    Installer.php       # перенести из modules/profile/Install/, namespace → Johncms\Modules\Profile\Install
config/
  routes.php            # REST-роуты вместо ?act= (+ auth-группа с middleware)
  services.php          # DI: load Application/Infrastructure + bind интерфейсов
```

Namespace: `Johncms\Modules\Profile\…`

**Trailing slash:** роуты определять *без* завершающего слэша (`/profile/stat`), а в ссылках шаблонов/контроллеров — *со* слэшем (`/profile/stat/`). `index.php` нормализует URI через `rtrim`, работают оба варианта.

**Clean URL mapping** (черновик, уточнить при переносе каждого действия):
- `?act=index&user={id}` → `/profile/{id:number}/` (или `/profile/` для себя)
- `?act=stat&user={id}`  → `/profile/{id:number}/stat/`
- `?act=ip&user={id}`    → `/profile/{id:number}/ip/`
- `?act=office`          → `/profile/office/`
- `?act=edit`            → `/profile/edit/`
- `?act=password`        → `/profile/password/`
- `?act=settings&mod=…`  → `/profile/settings/…`
- `skl.php?act=…`        → `/profile/restore/…`

**Названия URL не догма.** Старые имена `act`/`mod` подобраны исторически и местами неудачны
(аббревиатуры, неочевидные слова — напр. `skl`, `ip`, `office`). Раз обратную совместимость не держим
(редиректов нет), при формировании новых маршрутов **подбирать более понятные и точные названия**:
- осмысленные английские слова вместо сокращений (`skl` → `restore`/`password-recovery`);
- единообразие с остальными модулями (forum/mail/downloads);
- избегать двусмысленностей (`office` → `account`/`dashboard`? — решить при переносе).
Конкретное имя согласовывать с пользователем при переносе соответствующей страницы.

---

## Фаза 0. Анализ (Step 1 гайда)

- [ ] Зафиксировать все `act`/`mod` и их include-файлы (см. таблицу выше — уже сделано).
- [ ] Изучить схему таблиц: `SHOW CREATE TABLE` для `users`, `cms_contact`, `cms_users_iphistory`,
      `cms_album_files`, `cms_users_guestbook`, таблиц кармы/бана.
      `docker exec $(docker ps -q -f name=johncms9.php-fpm) php -r '…'` или через MySQL-клиент контейнера.
- [ ] Свериться с forum/mail/downloads на предмет готовых паттернов и переиспользуемых моделей.

## Фаза 1. Scaffold структуры (Step 2 гайда) ✅ ВЫПОЛНЕНО

- [x] Добавить PSR-4 в корневой `composer.json`: `"Johncms\\Modules\\Profile\\": "modules/profile/src/"`
- [x] Создать на диске три верхних каталога (+ подкаталоги по мере необходимости).
- [x] Перенести Installer → `src/Install/Installer.php`, namespace → `Johncms\Modules\Profile\Install`. Старый `Install/` удалён.
- [x] Создать `config/services.php` (instanceof Console tag; load Application с exclude Exceptions; load Infrastructure; bind `ProfileUserRepositoryInterface`).
- [x] `composer dump-autoload` (6909 классов).
- [x] Smoke: консоль/router:list работают, контейнер компилируется, роут зарегистрирован.

## Фаза 1. Домен и инфраструктура (репозитории)

Цель — убрать прямые `$db->query()` / сырой SQL из логики.

- [ ] Определить, какие таблицы трогает модуль: `users`, `cms_contact`, `cms_users_iphistory`,
      `cms_album_files`, `cms_users_guestbook`, карма/бан таблицы, восстановление пароля (`rest_code`,`rest_time`).
- [ ] Переиспользовать существующие модели/репозитории (User, Mail Contact/MailMessage, Ban, Karma) где возможно — не дублировать.
- [ ] Создать недостающие `*RepositoryInterface` в `Domain/Repository` и `Eloquent*Repository` в `Infrastructure`:
      - [ ] `IpHistoryRepositoryInterface` (история IP)
      - [ ] `ProfileStatsRepositoryInterface` или переиспользовать существующие счётчики
      - [ ] `PasswordRecoveryRepositoryInterface` (skl: поиск по `name_lat`, запись `rest_code`/`rest_time`)
      - [ ] прочие по факту анализа includes
- [ ] Репозитории — тонкие: `Model::query()->…`, без бизнес-правил/escape/HTTP.
- [ ] **Именование методов:** `find*` — для одиночной сущности с возможным `null` (`findById(): ?User`);
      `get*` — для гарантированного результата / `Collection` / пагинатора. Правило: тип `?Entity` → `find*`.
      Решение «что если null» принимает use case (например, бросает `*NotFoundException`).

## Фаза 2. Перенос действий «чтение» (низкий риск)

Простые GET-страницы. Для каждой: `Get*ContextUseCase` (+ guard внутри, если тривиально) → Controller → шаблон.

- [x] `stat`  → `StatisticsController` (URL `/profile/{id:number}/statistics`, name `profile.statistics`).
      Файлы: `GetProfileStatisticsUseCase`, `ProfileUserRepositoryInterface`/`ProfileUserRepository`,
      `ProfileNotFoundException`, `AuthorizedUserMiddleware`, шаблон `statistics.phtml`.
      Legacy `includes/stat.php` + `templates/stat.phtml` удалены, `stat` убран из `$mods`, ссылки обновлены
      (office.phtml, index.phtml). Исправлено двойное экранирование title/breadcrumbs. cs-check + test зелёные.
      ⏳ ожидает ревью и коммита пользователем.
- [x] `index` → `ProfileController` (URL `/profile/{id:number}`, name `profile.view`).
      Файлы: `GetProfileViewUseCase` + `ProfileViewDTO`, `KarmaRepositoryInterface`/`EloquentKarmaRepository`,
      переиспользование mail `ContactRepositoryInterface` (контакты/игнор), карма/баны/кнопки/уведомления в use case.
      Core-аксессор `profile_url` → `/profile/{id}`; обновлены ВСЕ ссылки на просмотр профиля по проекту
      (admin, album, downloads, forum, library, mail, system/Comments.php, UserProperties.php) и nav/back_url в legacy includes.
      Legacy `includes/index.php` + `templates/index.phtml` + функция `is_contact()` удалены, `index` убран из `$mods`.
      cs-check + test зелёные; use case и шаблон проверены рендером. ⏳ ожидает ревью и коммита.
- [x] `ip`    → `IpHistoryController` (URL `/profile/{id:number}/ip-history`, name `profile.ip-history`).
      Файлы: `GetIpHistoryUseCase` + `IpHistoryDTO`, `IpHistoryRepositoryInterface`/`EloquentIpHistoryRepository`,
      `ProfileAccessForbiddenException` (403). Guard «админ или владелец» в use case; пагинация через Laravel `paginate()`.
      Переиспользован шаблон `ip_history.phtml`. Ссылка `ip_history_url` в ProfileView обновлена. Legacy `includes/ip.php` удалён, `ip` убран из `$mods`.
      cs-check + test зелёные; use case (владелец/forbidden) и шаблон проверены. ⏳ ожидает ревью и коммита.
- [x] `activity` → `ActivityController` (под-пути: `/profile/{id}/activity`, `/activity/topics`, `/activity/comments`;
      names `profile.activity[.topics|.comments]`). Файлы: `GetActivityUseCase` + `ActivityDTO`,
      `ActivityType` enum, `ProfileActivityRepositoryInterface`/`EloquentProfileActivityRepository`,
      `ForumActivityPreviewService`. Переиспользованы форумные модели (ForumMessage/Topic/Section с eager-load) и
      `GuestbookEntry::post_text`, `ForumTopicPathService`. Шаблон `activity.phtml` + партиалы без изменений.
      Ссылки в view/statistics обновлены. Legacy `includes/activity.php` удалён, `activity` убран из `$mods`.
      NB: режим comments теперь использует `post_text` (smilies по правам автора, а не зрителя — выравнивание с модулем guestbook).
      cs-check + test зелёные; все три режима проверены рендером. ⏳ ожидает ревью и коммита.
- [x] `confirm_new_email` → `ConfirmNewEmailController` (URL `/profile/confirm-email/{id:number}/{code}`, name `profile.confirm-email`).
      **Публичный** маршрут (ВНЕ auth-группы): пользователь переходит по ссылке из письма, может быть не залогинен;
      граница доступа — код `uniqid('email_', true)`, сверяемый с id (legacy шёл через index.php с auth — поведение изменено осознанно по плану).
      Файлы: `ConfirmNewEmailUseCase` (без DTO — возвращает `bool`; решение о совпадении кода в use case),
      метод `confirmNewEmail(int $id, string $newEmail)` в `ProfileUserRepository`/Interface (Eloquent `update`: mail←new_email, обнуление new_email/confirmation_code).
      Контроллер по образцу StatisticsController (ControllerContext + Render + NavChain, use case инъектируется). Шаблон `confirm_new_email.phtml`
      упрощён до `bool $confirmed` (убран неиспользуемый `confirm_user`; сохранены исходные строки переводов).
      Ссылка генерации письма в `includes/edit.php` (legacy, строка 160) обновлена на новый clean URL. Legacy `includes/confirm_new_email.php` удалён, `confirm_new_email` убран из `$mods`.
      cs-check + test зелёные; роут зарегистрирован; use case (неверный код → false, без мутации) и оба ветвления шаблона проверены рендером. ⏳ ожидает ревью и коммита.

## Фаза 3. Личный кабинет и гостевая

- [x] `office` → `AccountController` (URL `/profile/account`, name `profile.account`; URL-имя выбрано пользователем взамен неудачного `office`).
      Файлы: `GetAccountUseCase` + `AccountDTO`, `AlbumPhotoRepositoryInterface`/`EloquentAlbumPhotoRepository`
      (счётчик фото через `Capsule::table('cms_album_files')` — у модуля album нет моделей в src/).
      Mail/Contact-счётчики переиспользованы через существующие интерфейсы. Без id в URL — кабинет всегда текущего пользователя
      (аутентификация гарантируется `AuthorizedUserMiddleware`). Шаблон `office.phtml` → `account.phtml` (на DTO вместо `$data['counters']`).
      Обновлены ВСЕ ссылки `act=office` по проекту (mail-контроллеры/usecases, login logout, themes sidebar, admin amnesty,
      legacy-шаблоны настроек профиля) → `/profile/account`. Legacy `includes/office.php` + `templates/office.phtml` удалены, `office` убран из `$mods`.
      cs-check + test зелёные; use case (admin id=1) и шаблон проверены рендером; unauth → 404. ⏳ ожидает ревью и коммита.
- [x] `guestbook` → `GuestbookController` (URL `/profile/{id:number}/guestbook`, name `profile.guestbook`, GET+POST — имя выбрано пользователем).
      Обёртка над `Johncms\Comments` (рендерит полную страницу → `ob_start`/`ob_get_clean`, паттерн downloads `FileCommentsController`).
      `script` — чистый путь без `sub_id_name` (Comments::buildUrl сам ставит `?`/`&`); `sub_id`/`owner` = id профиля, owner_delete/reply = true.
      Файлы: `GetGuestbookContextUseCase` (guard «профиль виден» как в statistics), `MarkGuestbookReadUseCase`
      (сброс `comm_old` при просмотре своей гостевой и `! $mod`), метод `markGuestbookSeen()` в ProfileUserRepository.
      Глобалы `$mod`/`$start` выставляются как в downloads. Ссылки `act=guestbook` обновлены: account.phtml, view.phtml,
      notifications `GetNotificationListUseCase` → `/profile/{id}/guestbook`. Legacy `includes/guestbook.php` удалён, `guestbook` убран из `$mods`.
      cs-check + test зелёные; рендер (admin, профиль id=1) проверен; unauth → 404. ⏳ ожидает ревью и коммита.

## Фаза 4. Действия «запись» (Access Guard паттерн)

guard → context → action. Action только на POST.

- [x] `password` → `ChangePasswordController` (`form` GET / `change` POST; URL `/profile/{id:number}/password`,
      names `profile.password` + `profile.password.change`). Guard свёрнут в `GetChangePasswordContextUseCase`
      (тривиальный, один контроллер): «владелец или (rights≥7 и target.rights ≤ user.rights)» → `ProfileAccessForbiddenException` (403),
      not-found как в statistics. Action — `ChangePasswordUseCase` + `ChangePasswordCommand`, валидация 1:1 с legacy
      (old/new/confirm required, проверка старого пароля для self, mismatch, мин. 3 символа), ошибки через
      `ChangePasswordException::withErrors()`. Метод `updatePassword(int,string)` в repo/Interface (хэш `md5(md5())` сохранён).
      Куки `cups` для self ставит контроллер (HTTP-слой). Шаблон `password.phtml` переведён на чистые URL (form_action/back_url).
      Ссылки обновлены: account.phtml, edit.phtml (legacy) → `/profile/{id}/password`. Legacy `includes/password.php` удалён, `password` убран из `$mods`.
      **NB (фикс фреймворка):** безымянные роуты в группе получают `legacy_route_<idx>` с локальным счётчиком и коллизируют
      с авто-именем top-level роута → POST-роут обязательно именовать. cs-check + test зелёные (остаточный fail
      `RouteCollectorFactoryTest` — предсуществующий, про `routes.local.php`); DI-резолв контроллера/use case проверен. ⏳ ожидает ревью и коммита.
- [x] `edit` → `EditProfileController` (GET `form` / POST `save` / POST `deleteAvatar` / POST `deletePhoto`;
      URL `/profile/{id:number}/edit` + `/edit/delete-avatar` + `/edit/delete-photo`; names `profile.edit[.save|.delete-avatar|.delete-photo]`).
      Guard свёрнут в `GetEditContextUseCase` (тривиальный, один контроллер): «владелец или (rights≥7 и target.rights ≤ user.rights)»
      → `ProfileAccessForbiddenException('You cannot edit profile of higher administration')`; забаненный редактор → `ProfileAccessForbiddenException` (Access forbidden); not-found как в statistics. Все forbidden → 403 (осознанное выравнивание с password; legacy отдавал 200).
      Action — `UpdateProfileUseCase` + `UpdateProfileCommand`: валидация 1:1 с legacy через `Johncms\Validator\Validator`
      (NotEmpty/StringLength/Between/InArray/ModelNotExists/Csrf + EmailAddress с MX при `user_email_confirmation`),
      клемпинг rights, снятие админ-полей для rights<7, флоу отложенной смены email (new_email/confirmation_code + 2 письма через `EmailMessage`,
      ссылка на `/profile/confirm-email/{id}/{code}`). Ошибки → `EditProfileException` (keyed errors) → ре-рендер формы с введёнными значениями.
      Метод `updateProfile(int, array)` в repo/Interface (через загруженную модель — касты admin_notes/mailvis). Удаление аватара/фото —
      отдельные `DeleteAvatarUseCase`/`DeletePhotoUseCase` (unlink), кнопки в шаблоне переведены на POST (`formaction`/`formnovalidate`,
      т.к. внутри формы редактирования). PRG: успех/удаление → `$_SESSION['success_message']` + `redirect()` на `/profile/{id}/edit`.
      **Escape:** сохранён вход-фильтр FILTER_SANITIZE_FULL_SPECIAL_CHARS как в legacy (шаблон выводит без экранирования) — escape-аудит вынесен в Фазу 6.
      **NB:** аксессор `photo` не в `$appends`, поэтому в legacy `toArray()` его не было (блок фото был мёртв) — теперь `photo` передаётся явно и блок работает.
      Ссылки `act=edit` обновлены: view.phtml, account.phtml, `GetProfileViewUseCase`, legacy `images.php` (back_url), help `SetAvatarController` (back_url) → `/profile/{id}/edit`;
      ссылки загрузки аватара/фото абсолютизированы на legacy `/profile/?act=images...` (модуль images ещё не мигрирован). Legacy `includes/edit.php` удалён, `edit` убран из `$mods`.
      cs-check + test (61/61) зелёные; 4 маршрута компилируются, DI резолвит контроллер/use cases, ветки guard (403/404) и smoke-рендер шаблона проверены.
      ✅ Закоммичено `df53038a`. Пост-ревью cleanup'ы (вошли в тот же коммит): CSRF-проверка delete-аватара/фото
      (`isCsrfValid()` через `Validator`+`Csrf`, как forum `DeletePostFileController`); guard свёрнут в `resolveContext(): DTO|string`
      (убран 4× копипаст try/catch); `UpdateProfileCommand::toFormData()` — единый источник карты полей (было 3 копии),
      `formDataFromCommand` удалён; `UpdateProfileUseCase::execute(command, User $profileUser)` принимает загруженного юзера
      из guard-контекста (убран повторный `findById` + `ProfileNotFoundException`), поле `profileUserId` удалено из Command.
- [x] `settings` → `SettingsController` (8 маршрутов: general/forum/mail × form+save, +general/forum reset; names `profile.settings[.save|.reset|.forum(.save|.reset)|.mail(.save)]`).
      Всегда настройки текущего пользователя (self-only, как account; id в URL нет). По решению пользователя: формы и сбросы — отдельные маршруты,
      **сбросы переведены на POST** (submit-кнопки `formaction`/`formmethod=post`/`formnovalidate` внутри форм, как edit/reset-settings; CSRF не используется — как в reset-settings).
      Три cohesive use case (read+save[+reset] на свой блок): `UserSettingsUseCase` (save→`?string` lng, reset),
      `ForumSettingsUseCase` (getCurrent/save/reset, `DEFAULT_SETTINGS` const), `MailSettingsUseCase` (getCurrent/save).
      DTO: `UpdateUserSettingsCommand`/`UpdateForumSettingsCommand`/`UpdateMailSettingsCommand`. Repo: `saveUserSettings`/`saveForumSettings`/`saveMailSettings`
      (через загруженную модель — касты UserSettings/Serialize). Клемпинги 1:1 с legacy (timeshift ±12, kmess 5-99, fieldHeight 1-9, postclip 0-2→1, access 0-2→0).
      General — PRG через `$_SESSION` (set_ok/reset_ok, выбор языка → `$_SESSION['lng']`); forum/mail рендерятся inline с success (как legacy).
      Шаблоны `settings/forum_settings/mail_settings.phtml` переиспользованы (только reset-ссылки → POST-кнопки, buttons/form_action на чистые URL).
      Ссылка `account.phtml` (System Settings) → `/profile/settings`. Legacy `includes/settings.php` удалён, `settings` убран из `$mods`.
      cs-check (765) + test (61/61) зелёные; 8 маршрутов зарегистрированы (имена уникальны), DI резолвит контроллер/use cases,
      рендер general/forum/mail и юнит-логика клемпингов/reset проверены. ✅ Закоммичено `b4b78b90`.
- [x] `images` (режимы avatar/up_photo — это **загрузка**; удаление аватара/фото уже в edit) → `AvatarController` + `PhotoController`
      (по решению пользователя — два контроллера; URL вложены под edit: GET/POST `/profile/{id:number}/edit/avatar` и `/edit/photo`;
      names `profile.edit.avatar[.upload]` / `profile.edit.photo[.upload]`). Guard переиспользует `GetEditContextUseCase`
      (его условие математически эквивалентно legacy-guard images: для self ветка `target.rights > user.rights` всегда ложна;
      **отличие — добавлена проверка бана редактора**, которой в legacy images не было → выравнивание с edit). resolveContext дублируется в обоих
      контроллерах (осознанно, т.к. выбраны два контроллера). Action — `UploadAvatarUseCase`/`UploadPhotoUseCase` (инъекция `Intervention\Image\ImageManager`):
      валидация размера (flsz) и обработка изображения в use case, ошибки → `ImageUploadException` (RuntimeException) → ре-рендер result-страницы с back на форму.
      Аватар: resize 150×150 → png; фото: resize 1024×960 → jpg + превью 400×300 → `_small.jpg` (1:1 с legacy, aspectRatio+upsize). CSRF не используется (как в legacy images).
      Шаблон `images.phtml` переиспользован без изменений (form_action/back_url). Ссылки загрузки в `edit.phtml` (строки 103/126) с legacy `/profile/?act=images...`
      → `/profile/{id}/edit/avatar|photo`. Legacy `includes/images.php` удалён, `images` убран из `$mods`.
      cs-check (769) + test (61/61) зелёные; DI резолвит оба контроллера и use cases, 4 маршрута зарегистрированы, guard (403 для неавторизованного)
      и прямой рендер шаблона (multipart/action/file input) проверены. ✅ Закоммичено `c9671c2a`.
- [x] `karma` (list/new/vote/delete/clean) → `KarmaController` (8 маршрутов под `/profile/{id:number}/karma`;
      names `profile.karma[.new|.vote(.submit)|.clean(.confirm)|.delete(.confirm)]`). URL-схема согласована с пользователем.
      Use cases: `GetKarmaListUseCase` (список + фильтр type 0/1/2, guard видимости профиля как statistics),
      `GetNewKarmaUseCase` (новые отзывы текущего пользователя за 24ч), `GetVoteContextUseCase` (все проверки доступности голоса →
      `KarmaVoteException` с массивом сообщений), `VoteKarmaUseCase` (вставка + обновление karma_plus/minus + уведомление),
      `DeleteKarmaVoteUseCase` (getVote/delete, rights 9), `CleanKarmaUseCase` (rights 9). DTO: `KarmaListDTO`/`VoteContextDTO`/`VoteKarmaCommand`.
      Репозитории: переиспользована Eloquent-модель `Johncms\Users\Karma`; `KarmaRepository` расширен (paginateReceived/paginateReceivedAfter/
      addVote/findVote/deleteVote/deleteAllForTarget), `ProfileUserRepository` — счётчики кармы (addKarmaPoints/subtractKarmaPoints с floor 0/resetKarmaTotals).
      Пагинация через Laravel paginator (`render()`/`appends(['type'=>...])`) как ip-history; текст через Tools smilies/checkout/displayDate.
      **Отличия от legacy (осознанные):** (1) `delete_token` → стандартный `csrf_token` + Csrf-валидатор (как forum delete); шаблон `karma_delete.phtml` обновлён;
      (2) удаление/очистка для не-rights-9 → 403 (legacy отдавал пустую страницу); (3) опущен `OPTIMIZE TABLE` после очистки (нефункц. обслуживание);
      (4) IP-проверка читерства использует `currentUser->ip` (как `GetProfileViewUseCase`), а не live Environment::getIp.
      4 ссылки на karma в `GetProfileViewUseCase` (positive/negative/vote/new) → новые чистые URL. Legacy `includes/karma.php` удалён, `karma` убран из `$mods`.
      cs-check (779) + test (61/61) зелёные; DI резолвит контроллер + 6 use cases, 8 маршрутов зарегистрированы, рендер трёх шаблонов проверен. ✅ Закоммичено `d518f29f`.
- [x] `reset` → `ResetSettingsController` (`__invoke`, POST `/profile/{id:number}/reset-settings`, name `profile.reset-settings`).
      Guard свёрнут в `GetResetSettingsContextUseCase` (тривиальный, один контроллер — по AGENTS.md «When NOT to create Ensure*AccessUseCase»):
      «rights≥7 и user.rights > target.rights» → `ProfileAccessForbiddenException` (403), not-found как в statistics → `ProfileNotFoundException`.
      Action — `ResetUserSettingsUseCase` + `ResetSettingsContextDTO`. Метод `resetSettings(int)` в repo/Interface:
      сброс через загруженную модель (`User::query()->find($id)?->update(['set_user'=>[], 'set_forum'=>[]])`) — билдер не сериализует
      касты `UserSettings`/`Serialize`, поэтому именно загруженная модель (как в legacy). Action только на POST (Фаза 4).
      Legacy GET-ссылка `?act=reset` в `templates/edit.phtml` (внутри формы редактирования) → submit-кнопка с
      `formaction`/`formmethod="post"`/`formnovalidate` (вложенную форму делать нельзя; тело контроллер игнорирует). CSRF не используется (как в миграции password).
      Legacy `includes/reset.php` удалён, `reset` убран из `$mods`. cs-check + test (61/61) зелёные;
      маршрут компилируется (POST), DI резолвит контроллер/use cases, ветки guard (guest→403, big id→404) и null-safe repo проверены. ⏳ ожидает ревью и коммита.
- [x] `ban` (do/cancel/delete/delhist + история) → `BanController` (9 маршрутов под `/profile/{id:number}/bans`;
      names `profile.bans[.new(.submit)|.clear(.confirm)|.cancel(.confirm)|.delete(.confirm)]`). URL-схема согласована с пользователем (`bans`).
      Use cases: `GetBanHistoryUseCase` (список+форматирование+кнопки, guard видимости профиля как statistics),
      `GetBanFormContextUseCase` (guard формы бана: rights<1 / rights<6 при target.rights / rights<=target.rights → `ProfileAccessForbiddenException` 403),
      `BanUserUseCase` + `BanUserCommand` (валидация 1:1 с legacy: required-данные, rights-term соответствие 14/12/11/16/15, «Ban already active»,
      расчёт длительности с кэпами по rights, штраф кармы при `karma.on`), `CancelBanUseCase` (getCancelableBan: self→Wrong data, !active→`BanException`),
      `DeleteBanUseCase` (откат штрафа кармы: deleteSystemPenalty по ban_while + subtractKarmaPoints + удаление), `ClearBanHistoryUseCase`.
      Репозитории: `BanRepositoryInterface`/`EloquentBanRepository` (переиспользована модель `Johncms\Users\Ban`); `KarmaRepository` расширен
      `deleteSystemPenalty(targetId, time)`; переиспользованы `addVote`(user_id=0,System)/`addKarmaPoints`/`subtractKarmaPoints`.
      **Отличия от legacy (осознанные):** (1) добавлен CSRF (`csrf_token`+Csrf-валидатор) на все 4 write-действия — шаблоны `ban`/`ban_cancel` обновлены;
      (2) rights-guard (cancel rights<7 / delete+clear rights!==9) → 403 (legacy отдавал «Wrong data»/пустую страницу) — выравнивание с karma;
      (3) единый `$now` для ban_while и времени штрафа кармы (надёжнее матчинг при откате). Пагинация через Laravel paginator.
      Ссылки обновлены: view.phtml (User is banned/Violations → `/profile/{id}/bans`), `GetProfileViewUseCase` (кнопка Ban → `/bans/new`),
      `GetNotificationListUseCase` (→ `/profile/{currentUserId}/bans`), admin `ban_panel.php` (→ `/profile/{id}/bans`). Legacy `includes/ban.php` удалён,
      каталог `includes/` пуст и удалён, `index.php` упрощён до `pageNotFound()` ($mods убран). cs-check (790) + test (61/61) зелёные;
      DI резолвит контроллер + 6 use cases + репозиторий, 9 маршрутов зарегистрированы (имена уникальны), 3 шаблона проверены рендером. ✅ Закоммичено `ffdad433`.

## Фаза 5. Восстановление пароля (`skl.php`, публичный)

- [x] `RestorePasswordController` (публичный, ВНЕ auth-группы; 4 маршрута). URL-схема согласована с пользователем: `password-recovery`.
      `form` GET `/profile/password-recovery` (капча) / `send` POST `/profile/password-recovery` (отправка письма со ссылкой) /
      `setForm` GET `/profile/password-recovery/set/{id:number}/{code}` (форма подтверждения) /
      `set` POST `.../set/{id}/{code}` (генерация нового пароля + письмо). По решению пользователя set-флоу через **форму+POST**
      (отступление от legacy GET-мутации; Access Guard: action только на POST).
      Use cases: `SendPasswordRecoveryUseCase` (валидация required/поиск по name_lat через `Tools::rusLat`/проверка email/лимит 1/сутки/письмо/
      запись rest_code+rest_time), `GetRecoveryContextUseCase` (guard кода+TTL 1ч, чистит код при истечении → `PasswordRecoveryException`),
      `CompletePasswordRecoveryUseCase` (passgen→письмо→completePasswordRecovery). DTO `SendRecoveryCommand`; exception `PasswordRecoveryException`.
      `passgen()` вынесен в `Application/Services/PasswordGenerator` (random_int). Капча через `Validator(['Captcha'])` (как registration);
      ошибки и успех — `system::pages/result`. Repo расширен: `findByNameLat`/`startPasswordRecovery`/`clearPasswordRecovery`/`completePasswordRecovery`.
      Шаблоны: `restore_password.phtml` (action → чистый URL), новый `restore_password_set.phtml`; `restore_password_result.phtml` удалён (→ pages/result).
      Ссылка «Forgot password?» в `login.phtml` → `/profile/password-recovery`. Legacy `skl.php` удалён, роут `profile.skl` убран.
      cs-check (796) + test (61/61) зелёные; 4 маршрута зарегистрированы, DI резолвит контроллер + 3 use case + сервис, оба шаблона проверены рендером.
      Пост-фикс: имя поля капчи в форме — `code` (не `captcha`), контроллер читает `getPost('code')`. ✅ Закоммичено `b452d4b6`.

## Фаза 6. Роуты и чистка (Step 6 гайда)

- [x] Переписать `config/routes.php` на REST-роуты с auth-группой + `AuthorizedUserMiddleware`.
      Сделано по ходу миграции: все `profile.*` в одной auth-группе (`$profileGroup`), публичные `confirm-email` и
      `password-recovery*` — вне группы. Роуты без trailing slash; именованные маршруты сохранены.
- [x] `restore` (skl.php) — публичные роуты ВНЕ auth-группы (Фаза 5).
- [x] **Legacy-редиректы НЕ делаем** — внутренние ссылки полностью обновлены по ходу миграции, редиректов нет.
- [x] **Абсолютизация / поиск старых URL** — финальный греп по всему проекту чист: ни `profile/index.php`,
      ни `profile/skl.php`, ни `?act=<profile>`/`?user=` не осталось (совпадения в admin — собственные admin-экшены `ipban`/`ban_panel`).
      Ссылка «Forgot password?» (login.phtml) обновлена; меню/аксессоры (`profile_url`, `/profile/account`) уже на чистых URL.
- [x] Удалить `index.php`, `skl.php`, `includes/`. Все три удалены; legacy-роуты `profile.index`/`profile.skl` убраны.
      Голый `/profile` нигде не использовался (меню → `/profile/account`, профили → `profile_url` = `/profile/{id}`),
      поэтому отдельный controller/redirect для него не вводился. Сигнатура routes-замыкания упрощена до `(RouteCollection $router)`
      (убран неиспользуемый `User $user`).
- [x] Шаблоны `templates/` (namespace `profile::`): регистрируются через `ControllerContext::initModule('profile')`
      (addFolder + addTranslationDomain), который вызывают все контроллеры. Отдельной регистрации после удаления index.php/skl.php не требуется.
- [ ] **Escape-аудит — ОТЛОЖЕН (решение пользователя 2026-06-05): известный долг, оформить отдельной проектной задачей.**
      Точки на входе: `EditProfileController::buildCommand()` (15 полей через `FILTER_SANITIZE_FULL_SPECIAL_CHARS`/`htmlspecialchars`),
      `VoteKarmaUseCase` (`htmlspecialchars` имени для уведомления). Точки на выводе без `$this->e()`: `view.phtml` (status/live/mibile/
      mail/skype/website/admin_notes/imname/name), `edit.phtml` (те же 15 полей в value/textarea).
      **Почему не делаем точечно (выводы аудита):**
      1. Нет единого инварианта хранения: регистрация (`RegisterUserUseCase`) пишет name/imname/about **сырыми**, а legacy-профиль —
         **экранированными** → в таблице `users` данные смешанные построчно.
      2. Потребители выводят непоследовательно: `community/user_row.phtml` экранирует, `online/user_row.phtml` — нет (пред-существующий баг,
         не связан с профилем).
      3. Подводный камень с данными: «store raw + escape on output» вызовет **двойное отображение сущностей** для старых profile-строк
         (`O&#39;Brien`) → нужна одноразовая миграция БД `html_entity_decode` затронутых колонок.
      Корректный полный фикс = проектная задача (все модули + миграция БД), несоразмерная рефакторингу одного модуля.
- [x] Документация `docs/`: обновлять нечего — `docs/` это пользовательский GitBook о возможностях CMS, отдельной страницы
      по модулю profile нет, рефакторинг внутренний и не меняет пользовательское поведение. `docs/SUMMARY.md` без изменений.

## Фаза 7. Финальная проверка

- [ ] `docker exec $(docker ps -q -f name=johncms9.php-fpm) composer cs-check` (фикс: `composer cs-fix`)
- [ ] `docker exec $(docker ps -q -f name=johncms9.php-fpm) composer test`
- [ ] Сборка фронта, если менялись assets.
- [ ] Ручной прогон всех страниц профиля и восстановления пароля.
- [ ] Репозитории/сервисы инъектируются через интерфейсы; контроллеры не зависят от инфраструктуры.

---

## Принципы (из AGENTS.md)

- Малые безопасные шаги, поведение сохраняется. Не трогать несвязанные модули.
- Репозитории тонкие: только запросы (`Model::query()->…`), без бизнес-правил/escape/HTTP.
- DI через интерфейсы. `declare(strict_types=1)`, типизация, `final`, property promotion, `readonly` для новых DTO/сервисов.
- Исключения не регистрировать как DI-сервисы; `Application/Exceptions` исключить из autoload.
- Без коммитов без явной просьбы пользователя.

## Открытые вопросы

- ~~URL-совместимость~~ → решено гайдом: REST + `*LegacyRedirectResolver` для старых `?act=`.
- Объём переиспользования существующих `Johncms\Users\*` (User/Ban/Karma) vs новые репозитории.
- Нужны ли отдельные Domain-модели, или достаточно существующих в `system/src`.
- С какого действия начать первую итерацию (рекомендация: `stat` — самое простое, чистое чтение).
