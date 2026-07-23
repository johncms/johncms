# Анализ модуля `modules/guestbook`

Дата: 2026-06-09. Ветка: `9.x`.

Модуль частично переведён на новую архитектуру (есть `Application/Domain/Infrastructure`, репозиторий, DTO, middleware), но переход не завершён: рядом живут два поколения кода — новый `GuestbookAccess`/`GuestbookMode`/`ListGuestbookEntriesUseCase` и legacy-стилевые `GuestbookService`/`GuestbookForm` с `di()` в конструкторах.

---

## 1. Баги и проблемы безопасности (высокий приоритет)

### 1.1. TypeError в `GuestbookEntryMetaDTO`

`ListGuestbookEntriesUseCase::getMeta()` передаёт `null` в `editUrl` / `deleteUrl` / `replyUrl`, когда `$canManage === false`:

```php
editUrl: $canManage ? '/guestbook/edit?id=' . $entry->id : null,
```

но в DTO эти поля объявлены как ненуллабельные `string`. Когда модератор (rights >= 1) смотрит запись пользователя с правами выше своих — фатальный `TypeError` на всю страницу.

**Фикс:** сделать поля `?string` (в use case уже передаётся `null`, шаблон должен проверять `canManage`).

### 1.2. Несогласованные проверки прав на edit/delete

- В списке: `canManage = $entry->user === null || $currentUser->rights >= $entry->user->rights` — кнопки скрываются.
- В `GuestbookEditAccessMiddleware`: только `rights > 0`.
- В самих `EditEntryController` / `DeleteEntryController` проверки `canManage` нет вообще.

Итог: модератор с rights = 1 может отредактировать или удалить запись администратора, просто открыв `/guestbook/edit?id=N` по прямой ссылке. UI это скрывает, сервер — не запрещает.

**Фикс:** повторить проверку `canManage` в контроллерах (или вынести в `Ensure*AccessUseCase` по Access Guard pattern, т.к. логика используется в 3 местах: edit, delete, reply).

### 1.3. Расхождение `isGuestbook()` между `GuestbookService` и `GuestbookMode`

- `GuestbookService::isGuestbook()` → `! isset($_SESSION['ga'])` — **без** проверки прав.
- `GuestbookMode::isGuestbook()` → `! isAdminClub()` — **с** проверкой прав.

`GuestbookController` берёт заголовок из `$this->guestbook->isGuestbook()` (сервис), а список — из `ListGuestbookEntriesUseCase`, который зовёт `GuestbookService::isAdminClub()` (с правами). Пользователь без прав со «застрявшим» `$_SESSION['ga']` увидит заголовок «Admin Club» при содержимом обычной гостевой. Аналогично `clear()` определяет `adm` через `isGuestbook()` без проверки прав.

**Фикс:** единственный источник истины — `GuestbookMode`, сервисные дубли удалить.

### 1.4. Проглоченные исключения

- `GuestbookService::getCaptcha()` — пустой `catch (Exception $exception) {}` (даже переменная не используется).
- `GuestbookService::deleteAttachedFiles()` — `catch (Throwable) {}` без логирования, при том что тот же сценарий в `DeleteEntryController` логируется через `LoggerInterface`.

Нарушение правила «Do not silently swallow exceptions». Минимум — логировать, как в `DeleteEntryController`.

---

## 2. Дублирование кода

| Что | Где | Действие |
| --- | --- | --- |
| `canWrite` / `canClear` / `isClosed` | `GuestbookService` и `GuestbookAccess` | Оставить только `GuestbookAccess` |
| `isAdminClub` / `isGuestbook` / `switchGuestbookType` | `GuestbookService` и `GuestbookMode` | Оставить только `GuestbookMode` |
| Удаление attached files | `DeleteEntryController` и `GuestbookService::deleteAttachedFiles()` (с разным поведением) | Один сервис/use case `DeleteAttachedFilesService` с логированием |
| `EditEntryController` и `ReplyController` | Почти идентичная структура (findOrFail, валидация, update, render) | Общие use cases, контроллеры остаются тонкими |
| Три ветки `switch` в `GuestbookService::clear()` | Копипаста запроса с разным `time <` | Один метод репозитория с параметром-порогом (`?int $olderThan`) |
| Render-блоки «Wrong data» | Дважды в `DeleteEntryController` | Guard clause + одна точка выхода |

`switchGuestbookType()` в сервисе — мёртвый код: роут использует `GuestbookMode::switch()`. Поле `$guest_access = []` также захардкожено в двух местах (`GuestbookService` и default-аргумент `GuestbookMode`) — если фича нужна, вынести в конфиг; если нет — удалить.

---

## 3. Архитектурные несоответствия AGENTS.md

### 3.1. Репозиторий есть, но не используется

`GuestbookEntryRepositoryInterface` определяет `find/save/delete`, но:

- `EditEntryController`, `ReplyController`, `DeleteEntryController` зовут `(new GuestbookEntry())->find()/findOrFail()` напрямую;
- `GuestbookService::create()/clear()` строят запросы прямо на модели (`(new GuestbookEntry())->where(...)` вместо `::query()`);
- `GuestbookService::create()` обновляет счётчики пользователя через `(new User())->where(...)->update(...)` — чужая модель напрямую из сервиса гостевой.

**Действие:** все обращения к данным — через репозиторий; инкремент `postguest`/`lastpost` — отдельный метод (в идеале — в репозитории/сервисе пользователя).

### 3.2. Нет use cases для write-операций

Создание, редактирование, ответ, удаление и очистка размазаны по контроллерам и `GuestbookService`. По принятому паттерну напрашиваются:

- `CreateGuestbookEntryUseCase` (из `GuestbookService::create`)
- `EditGuestbookEntryUseCase` + `GetGuestbookEntryContextUseCase`
- `ReplyToGuestbookEntryUseCase`
- `DeleteGuestbookEntryUseCase` (с удалением вложений)
- `ClearGuestbookUseCase` (из `GuestbookService::clear`)
- `EnsureGuestbookEntryManageAccessUseCase` (общий guard для edit/delete/reply — закрывает п. 1.2)

После этого `GuestbookService` можно удалить целиком.

### 3.3. Legacy-стиль DI

`GuestbookService` и `GuestbookForm`: `di()` в конструкторе, untyped `protected` свойства, `di(...)` внутри методов (`create()`, `clear()`). Перевести на constructor promotion + `readonly`, зависимости — через контейнер.

### 3.4. Модель `GuestbookEntry` перегружена

- `di(Tools|HTMLPurifier|MediaEmbed)` в конструкторе модели — сервисы тянутся при каждой гидрации каждой строки;
- аксессоры `post_text` / `reply_text` — это presentation-логика (purify, embed, smilies) в Domain-модели.

**Действие (отдельным шагом, аккуратно):** вынести форматирование текста в Application-слой (например, `GuestbookEntryTextFormatter` или маппинг в `ListGuestbookEntriesUseCase`), модель оставить чистой. Это уберёт и `di()` из конструктора.

### 3.5. HTTP-мелочи в контроллерах

- `GuestbookController`: `echo $this->render->render(...); exit;` для закрытой гостевой — вместо `return`.
- `UploadFileController`: голый `header('Content-Type: application/json')` + ручной `json_encode`; стоит использовать принятый в проекте JSON-ответ.
- Заголовок с пагинацией собирается вручную (`' - ' . d__('system', 'Page')` — причём с дефисом вместо принятого em dash), хотя по гайду нужно `Johncms\Http\PageMeta`.

### 3.6. Экранирование на входе вместо выхода

`EditEntryController` и `ReplyController` передают в шаблон `htmlspecialchars($form_data['message'])` / `htmlspecialchars($message->reply_text)` — нарушение принципа «escape on output»: экранировать должен шаблон через `$this->e(...)` в нужном контексте.

### 3.7. Прямой доступ к `$_SESSION`

`GuestbookMode`, `GuestbookService::getCaptcha()/create()` работают с `$_SESSION` напрямую, при этом в контроллерах уже инжектится `Johncms\System\Http\Session`. Унифицировать через сервис сессии.

---

## 4. Мелкие замечания

- `DeleteEntryController` (GET) передаёт в шаблон сырой `$this->request->getQuery('id')` без `FILTER_VALIDATE_INT` — в отличие от POST-ветки.
- `ReplyController`: при ошибке валидации в шаблон уходит `reply_text` из модели, а не отправленный пользователем текст — введённое теряется.
- `GuestbookService::clear()`: `case '1'` / `case '2'` — строковые литералы при `int $period`; работает за счёт нестрогого сравнения, но лучше `match (int)`.
- Хедер-комментарий с копирайтом есть только в части файлов (`GuestbookForm`, модель) — в новых файлах его не ставят; привести к единому виду при рефакторинге.

---

## 5. Предлагаемый порядок работ (малые безопасные шаги)

1. **Багфиксы:** nullable-поля в `GuestbookEntryMetaDTO` (1.1); серверная проверка `canManage` в edit/delete/reply (1.2). Можно одним коммитом `fix(guestbook): ...`.
2. **Убрать дублирование access-логики:** контроллеры и use case переводятся на `GuestbookAccess`/`GuestbookMode`, из `GuestbookService` удаляются дубли (1.3, §2).
3. **Use cases для write-операций** по одному экрану за итерацию (как в MODULE_REFACTORING_GUIDE.md): create → edit → reply → delete → clear. Параллельно весь доступ к данным уходит в репозиторий (3.1, 3.2).
4. **Удалить `GuestbookService`**, перевести `GuestbookForm` на constructor injection (3.3).
5. **Вынести форматирование текста из модели** (3.4) — отдельным шагом, т.к. затрагивает шаблоны.
6. **Косметика:** PageMeta, JSON-ответ в upload, экранирование в шаблонах, `$_SESSION` → `Session` (3.5–3.7, §4).

Шаги 1–2 не меняют поведение для легитимных сценариев и безопасны; шаги 3–5 — классический перенос по гайду рефакторинга.

---

## Статус выполнения (2026-06-11)

Выполнено всё, кроме одного пункта:

- §1.1–1.4, §2, §3.1–3.7, §4 — реализованы (use cases, репозиторий, `GuestbookService` удалён, модель очищена, `PageMeta`, Session-сервис, экранирование в шаблонах, серверная проверка `canManage`).
- §3.5 (UploadFileController, ручной `json_encode` + `header()`) — **оставлено как есть**: тот же паттерн используется в `forum/UploadFileController`, отдельного JSON-ответа в кодовой базе нет; менять его только в guestbook значило бы вводить новый паттерн.
- `profile/GetActivityUseCase` переведён с аксессора `post_text` на `GuestbookEntryTextFormatter` (аксессор удалён из модели).
- Проверки: `composer cs-check` ✅, `composer test` (61 тест) ✅, DI-смоук всех сервисов модуля ✅, страница `/guestbook/` отвечает 200.

