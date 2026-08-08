# Pagination & Page Meta

## Page Title and Description with Pagination

Use `Johncms\Http\PageMeta` to build the document `title` and meta `description` for paginated pages. It appends the page suffix automatically from page 2 onward.

```php
use Johncms\Http\PageMeta;

$meta = new PageMeta($documentTitle, $page);
// or with explicit description:
$meta = new PageMeta($documentTitle, $page, $description);

$this->render->addData([
    'title'       => $meta->title,
    'page_title'  => __('Page Heading'),
    'description' => $meta->description,
]);
```

Rules:

* Page 1 receives no suffix — `title` and `description` stay unchanged.
* If `description` is omitted or empty, `PageMeta` uses `title` as the base for the description.
* The separator is ` — ` (em dash with spaces), followed by the translated word "Page" from `d__('system', 'Page')`.

## Pagination

Use `Johncms\Http\Pagination` for all paginated lists in new and refactored code. **Do not use** the legacy fork `johncms/johncms-pagination` (Laravel `LengthAwarePaginator` / `->paginate()`) — it is being removed.

Components:

* `PaginationFactory` (DI service) — builds a `Pagination` from a total count. Inject it into controllers.
* `Pagination` — immutable, no service-locator: holds page math (`getOffset()`, `getPerPage()`, `getCurrentPage()`, `getTotalPages()`, `hasPages()`), builds URLs (`getUrl()`), exposes items (`getItems()`), and renders the template (`render()`).
* `PaginationGuard` (DI service) — returns a redirect URL for non-canonical pages; it does **not** redirect itself (keeps it testable). The controller calls `redirect()` explicitly.

### Controller flow

```php
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;

// in constructor: PaginationFactory $paginationFactory, PaginationGuard $paginationGuard

// 1. Build pagination from the total count (a cheap COUNT query in the use case/repository).
$pagination = $this->paginationFactory->create($this->listUseCase->count());

// 2. Canonical-page guard — only on the GET/render branch, never after a POST action.
if ($this->request->getMethod() !== 'POST') {
    $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
    if ($redirectUrl !== null) {
        redirect($redirectUrl);
    }
}

// 3. Fetch the page slice with offset/limit from pagination.
$items = $this->listUseCase->getPage($pagination->getPerPage(), $pagination->getOffset());

// 4. Build meta from the resolved current page and render the HTML pagination.
$meta = new PageMeta($pageTitle, $pagination->getCurrentPage());
return $this->render->render('module::index', [
    'items'      => $items,
    'pagination' => $pagination->render(),
    // ...
]);
```

`PaginationFactory::create(int $total, ?int $perPage = null, string $pageParamName = 'page', ?int $currentPage = null)`:

* `perPage` defaults to the current user's `config->kmess`. Pass an explicit value only when the list has its own page size.
* `currentPage` defaults to the `page` query parameter; pass it explicitly only in non-HTTP contexts.

### Repository / use case split

The use case must **not** know about HTML rendering or the Laravel paginator. Split data access into two methods so the controller controls slicing:

* `count(): int` → repository `count*(...)` (a `COUNT` query).
* `getPage(int $limit, int $offset): array` → repository `get*(..., int $limit, int $offset): Collection`, mapped to DTOs.

Repositories take explicit `limit`/`offset` and return `Collection`; never call `->paginate()`.

### Rules

* Page 1 is canonical without a `page` query parameter; `getUrl(1)` strips the parameter (handled by `PaginationGuard`: `?page=1`, junk, or `page < 1` → URL without `page`; `page > totalPages` → last page).
* Escape on output: `Pagination` items carry raw `type`/`page`/`url`/`active`, and
  `@theme/components/pagination.twig` prints them — autoescape does the rest. Do not pre-escape in PHP.
* `render()` returns `Twig\Markup`, so a template prints it with `{{ pagination }}` and needs no
  `|raw`. Render in the controller/template (or use `getItems()` for custom markup / JSON
  endpoints), never inside the use case.
* Redirect statuses are 302 for now (matching the old fork); the canonical strip may become 301 later if `redirect()` gains a status parameter.
