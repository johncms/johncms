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

Create directory structure:
```
modules/<module>/src/
├── Application/{Controllers,UseCases,DTO,Services,Middleware}
├── Domain/{Models,Repository,Entities,Enums}
└── Infrastructure/Persistence/Repository
```

Update `composer.json` with PSR-4 autoload:
```json
"Johncms\\Modules\\<Module>\\": "modules/<module>/src/"
```

Run `composer dump-autoload` in php-fpm container.

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
        ->exclude([...])
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

**Code style:**
```bash
docker exec ${COMPOSE_PROJECT_NAME}.php-fpm composer cs-check
```

**After testing:**
1. Remove legacy `includes/` directory
2. Remove or archive old templates
3. Update module documentation in `docs/`

## Key Patterns

**Access Guard Pattern** (for complex write operations):
- `Ensure*AccessUseCase` — throws exceptions on access denial
- `Get*ContextUseCase` — returns context DTO
- `*UseCase` — performs the action

**Repository Rules:**
- Only data access, no business logic
- Use query builder methods, not raw loops
- Return typed collections or paginators

**Template Variables:**
- Use `$this->render->addData()` for global variables (title, page_title, description)
- Do not pass them again in `render()` call
- Use `PageMeta` for pagination: `new PageMeta($title, $page)`

See [AGENTS.md](./AGENTS.md) for architecture principles and PHP style rules.
