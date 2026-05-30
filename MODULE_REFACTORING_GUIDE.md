# JohnCMS Module Refactoring Guide

Step-by-step instructions for refactoring legacy modules to the modern layered architecture. Based on the successful forum module refactoring.

## Overview

1. [Prerequisites](#prerequisites)
2. [Step 1: Analysis](#step-1-analysis)
3. [Step 2: Create Structure](#step-2-create-structure)
4. [Step 3: Create Domain](#step-3-create-domain)
5. [Step 4: Create Infrastructure](#step-4-create-infrastructure)
6. [Step 5: Refactor Actions](#step-5-refactor-actions)
7. [Step 6: Testing & Cleanup](#step-6-testing--cleanup)

## Iteration Process

Work on **one page at a time**:

1. Agent refactors a single action
2. User reviews and requests fixes if needed
3. User commits the changes
4. Only then proceed to the next action

Do not refactor multiple pages in one request.

## Action Limits

- Keep controller + use case in a single request (no splitting)
- If a page requires many files, prioritize core functionality first
- If analysis takes too long, ask user to clarify scope

## Parallel Operations

Use parallel execution when operations are independent:

- Reading multiple files → use multiple read calls in one message
- Running independent checks (cs-check, psalm) → run in parallel
- Analyzing multiple files → use Task tool with explore agent

## Prerequisites

- PHP 8.2+
- Docker environment (see [AGENTS.md](./AGENTS.md) for commands)
- Understanding of JohnCMS architecture

## Step 1: Analysis

- List all `act` parameters and their include files
- Study database schema: `SHOW CREATE TABLE cms_<module>_<table>;`
- Reference existing modules (forum, mail, downloads) for patterns

## Step 2: Create Structure

Target directory structure (full picture):
```
modules/<module>/src/
├── Application/{Controllers,UseCases,DTO,Services,Middleware}
├── Domain/{Models,Repository,Entities,Enums}
└── Infrastructure/Persistence/Repository
```

**Create subdirectories on demand, not all upfront.** Only the three top-level folders
(`Application`, `Domain`, `Infrastructure`) must exist immediately (see [Empty `src/` subdirectories](#empty-src-subdirectories)).
Create each nested folder (`Controllers`, `UseCases`, `DTO`, `Repository`, …) only when its first class appears.

Update `composer.json` with PSR-4 autoload:
```json
"Johncms\\Modules\\<Module>\\": "modules/<module>/src/"
```

Run `composer dump-autoload` in php-fpm container:
```bash
docker exec $(docker ps -q -f name=johncms9.php-fpm) composer dump-autoload
```

### Migrate Installer

Copy `modules/<name>/Install/Installer.php` to `modules/<name>/src/Install/Installer.php` and update the namespace:

```
ModuleName\Install  →  Johncms\Modules\ModuleName\Install
```

Then delete the old directory:
```
modules/<name>/Install/Installer.php
modules/<name>/Install/              (now empty)
```

> Installer discovery supports both the old (`ModuleName\Install\Installer`) and new (`Johncms\Modules\ModuleName\Install\Installer`) namespaces — the module admin page keeps working during migration.

### Empty `src/` subdirectories

Create the three top-level folders immediately even if they are empty:
```bash
mkdir -p modules/<name>/src/Application modules/<name>/src/Domain modules/<name>/src/Infrastructure
```

Empty directories are not tracked by git, but they must exist on disk — otherwise Symfony DI will fail when loading `services.php`.

## Step 3: Create Domain

**Models** in `Domain/Models/`:
```php
class MailMessage extends Model
{
    protected $table = 'cms_mail';
    protected $fillable = ['user_id', 'from_id', 'text', 'time'];
}
```

**Repository Interfaces** in `Domain/Repository/`:
```php
interface ContactRepositoryInterface
{
    public function getContacts(int $userId): Collection;
}
```

**Method naming: `find*` vs `get*`.** Follow the existing convention across modules:

- `find*` — single-entity lookup that **may return `null`**: `findById(int $id): ?User`, `findContact(...): ?Contact`.
- `get*` — returns a **guaranteed** value, a `Collection`, or a paginator (never a bare `null` for a missing single entity): `getContacts(): Collection`, `getBlocklist(): Collection`, `getConversation(): LengthAwarePaginator`.

Rule of thumb: if the return type is `?Entity`, name it `find*`. The use case decides what a missing result means (e.g. throw a `*NotFoundException`).

## Step 4: Create Infrastructure

**Repository Implementations** in `Infrastructure/Persistence/Repository/`:
```php
class EloquentContactRepository implements ContactRepositoryInterface
{
    public function getContacts(int $userId): Collection
    {
        return Contact::query()->where('user_id', $userId)->get();
    }
}
```

**Services Configuration** in `config/services.php`:
```php
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->load(
        'Johncms\\Modules\\<Module>\\Application\\',
        MODULES_PATH . '<module>/src/Application'
    )
        // Add ->exclude([...]) for DTO and Exceptions directories only when they exist
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->set(ContactRepositoryInterface::class, EloquentContactRepository::class)->public();
};
```

## Step 5: Refactor Actions

Refactor page-by-page, starting with simple read-only actions.

### Controller Pattern

```php
final readonly class ContactController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private User $user,
        private GetContactListUseCase $getContactListUseCase,
    ) {
        $this->controllerContext->initModule('mail');
    }

    public function __invoke(): string
    {
        $userId = (int) $this->user->id;
        $page = max(1, (int) $this->request->getQuery('page', 1));
        
        $result = $this->getContactListUseCase->execute($userId, $page);
        
        $this->navChain->add(__('Mail'), '/mail/');
        $this->navChain->add(__('Contacts'));
        
        $meta = new PageMeta(__('Contacts'), $page);
        $this->render->addData([
            'title'       => $meta->title,
            'page_title'  => __('Contacts'),
            'description' => $meta->description,
        ]);
        
        return $this->render->render('mail::contact_list', ['data' => ['items' => $result->contacts]]);
    }
}
```

### Use Case Pattern

```php
final readonly class GetContactListUseCase
{
    public function __construct(
        private ContactRepositoryInterface $contactRepository,
    ) {}

    public function execute(int $userId, int $page, int $perPage): ContactListResultDTO
    {
        $contacts = $this->contactRepository->getPaginated($userId, $page, $perPage);
        return new ContactListResultDTO($contacts);
    }
}
```

And in controller:
```php
$result = $this->getContactListUseCase->execute($userId, $page, $this->user->config->kmess);
```

### Middleware for Authorization

Create `src/Application/Middlewares/AuthorizedUserMiddleware.php`:
```php
final readonly class AuthorizedUserMiddleware implements MiddlewareInterface
{
    public function __construct(private User $user) {}

    public function handle(Request $request, callable $next): mixed
    {
        if (! $this->user->isValid()) {
            pageNotFound();
        }
        return $next($request);
    }
}
```

### Routes

```php
return static function (RouteCollection $router): void {
    $mailGroup = $router->group('', function (RouteCollection $r): void {
        $r->get('/mail/contacts/', ContactController::class);
        // ... other routes
    });
    $mailGroup->addMiddleware(AuthorizedUserMiddleware::class);
};
```

**Trailing slash convention:** define routes *without* a trailing slash (`/downloads/search`), but use a trailing slash in template and controller links (`/downloads/search/`). `index.php` normalises URIs with `rtrim` before matching, so both variants work at runtime.

**Clean URL mapping examples:**
- `?action=search` → `/downloads/search/`
- `?action=view&id={id}` → `/downloads/view/{id:number}/`

### Legacy Redirects

```php
final class MailLegacyRedirectResolver
{
    public function resolve(array $queryParams): ?string
    {
        if (!isset($queryParams['act'])) {
            return null;
        }

        return match ($queryParams['act']) {
            'index' => '/mail/contacts/',
            'ignor' => '/mail/blocklist/',
            default => null,
        };
    }
}
```

## Step 6: Testing & Cleanup

Run the pre-commit checklist from [AGENTS.md](./AGENTS.md#pre-commit-checklist) before every commit.

**After testing:**
1. Remove legacy `includes/` directory
2. Remove or archive old templates
3. Update module documentation in `docs/`

### Template link absolutization

When a template moves to a new route but other pages of the same module are still served by the catch-all `index.php`, relative links like `?id=X` resolve against the new URL and break navigation. Make all same-module links absolute as part of each controller extraction:

```
?id=X              → /module_name/?id=X
?act=foo           → /module_name/?act=foo
?act=foo&id=X      → /module_name/?act=foo&id=X
?do=dir&id=X       → /module_name/?do=dir&id=X
```

After migrating a page, also grep the entire module folder for old `?act=foo` references in other templates and controllers and update them to the new clean URL:

```bash
grep -r "act=foo" modules/<name>/
```

## Template Notes

- Templates call `$this->layout('system::layout/default')` without arguments.
- Global Plates variables (e.g. `$user`) are not passed explicitly; IDE won't see them — add a `@var` annotation with the FQCN. Note: `use` statements do not work in `.phtml` files, so use the fully-qualified class name:
  ```php
  /** @var \Johncms\Users\User $user */
  ```

See [AGENTS.md](./AGENTS.md) for architecture principles, repository rules, access guard pattern, PHP style rules, and the pre-commit checklist.
