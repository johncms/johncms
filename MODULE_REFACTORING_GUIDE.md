# JohnCMS Module Refactoring Guide

## Purpose

Step-by-step instructions for refactoring legacy JohnCMS modules to the modern layered architecture (Application/Domain/Infrastructure). Based on the successful refactoring of the forum module.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Docker Environment](#docker-environment)
3. [General Principles](#general-principles)
4. [Step 1: Analysis and Planning](#step-1-analysis-and-planning)
5. [Step 2: Create Base Structure](#step-2-create-base-structure)
6. [Step 3: Create Domain Models](#step-3-create-domain-models)
7. [Step 4: Create Infrastructure](#step-4-create-infrastructure)
8. [Step 5: Configure Services](#step-5-configure-services)
9. [Step 6: Refactor Actions](#step-6-refactor-actions)
10. [Step 7: Testing](#step-7-testing)
11. [Step 8: Cleanup](#step-8-cleanup)
12. [Patterns](#patterns)

## Prerequisites

- PHP 8.2+
- Docker environment
- Understanding of JohnCMS architecture (see AGENTS.md)

## Docker Environment

All PHP and Composer commands must run inside the `php-fpm` container:

```bash
docker exec ${COMPOSE_PROJECT_NAME}.php-fpm php <command>
docker exec ${COMPOSE_PROJECT_NAME}.php-fpm composer <command>
```

## General Principles

1. **Refactor gradually** – page-by-page (act-by-act), starting with simple actions
2. **Preserve backward compatibility** – replace old `?act=` URLs with new routes; add legacy redirects if needed
3. **Follow layered architecture** – Application → Domain ← Infrastructure
4. **Use dependency injection** – inject interfaces, not implementations
5. **Keep repositories thin** – only data access, no business logic
6. **Escape on output** – never escape on input

## Step 1: Analysis and Planning

### 1.1 Understand the Current Module
- List all `act` parameters and their include files
- Identify database tables and templates
- Map business logic flow

### 1.2 Study Database Schema
```sql
SHOW CREATE TABLE cms_<module>_<table>;
```

### 1.3 Reference Example Modules
Study the forum module (`modules/forum/`) for patterns:
- Directory structure
- Repository interfaces
- Use Case patterns
- Controller organization

## Step 2: Create Base Structure

### 2.1 Create Source Directory
```bash
mkdir -p modules/<module>/src/{Application,Domain,Infrastructure}
```

### 2.2 Directory Structure
```
modules/<module>/src/
├── Application/
│   ├── Controllers/
│   ├── UseCases/
│   ├── DTO/
│   └── Services/
├── Domain/
│   ├── Models/
│   ├── Repository/
│   ├── Entities/
│   └── Enums/
└── Infrastructure/
    └── Persistence/
        └── Repository/
```

### 2.3 Update composer.json
Add PSR‑4 namespace mapping:
```json
{
    "autoload": {
        "psr-4": {
            "Johncms\\Modules\\<Module>\\": "modules/<module>/src/"
        }
    }
}
```
Run:
```bash
docker exec ${COMPOSE_PROJECT_NAME}.php-fpm composer dump-autoload
```

## Step 3: Create Domain Models

### 3.1 Eloquent Models
Create models for each database table in `Domain/Models/`:
```php
<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class MailMessage extends Model
{
    protected $table = 'cms_mail';
    protected $fillable = ['user_id', 'from_id', 'text', 'time'];
}
```

### 3.2 Repository Interfaces
Create interfaces in `Domain/Repository/`:
```php
<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Domain\Repository;

interface ContactRepositoryInterface
{
    public function getContacts(int $userId): Collection;
    public function addContact(int $userId, int $contactId): Contact;
}
```

## Step 4: Create Infrastructure

### 4.1 Repository Implementations
Create Eloquent implementations in `Infrastructure/Persistence/Repository/`:
```php
<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Infrastructure\Persistence\Repository;

use Johncms\Modules\Mail\Domain\Repository\ContactRepositoryInterface;

class EloquentContactRepository implements ContactRepositoryInterface
{
    public function getContacts(int $userId): Collection
    {
        return Contact::query()
            ->where('user_id', $userId)
            ->get();
    }
}
```

## Step 5: Configure Services

Create `config/services.php` using Symfony DI ContainerConfigurator:

```php
<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Johncms\Modules\Mail\Domain\Repository\ContactRepositoryInterface;
use Johncms\Modules\Mail\Infrastructure\Persistence\Repository\EloquentContactRepository;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    // Autoload all Application classes (Controllers, Use Cases, Services)
    $services->load(
        'Johncms\\Modules\\Mail\\Application\\',
        MODULES_PATH . 'mail/src/Application'
    )
        ->exclude([
            MODULES_PATH . 'mail/src/Application/DTO',
            MODULES_PATH . 'mail/src/Application/Exceptions',
        ])
        ->autowire()
        ->autoconfigure()
        ->public();

    // Autoload Infrastructure classes
    $services->load(
        'Johncms\\Modules\\Mail\\Infrastructure\\',
        MODULES_PATH . 'mail/src/Infrastructure'
    )
        ->autowire()
        ->autoconfigure();

    // Register repository implementations
    $services->set(ContactRepositoryInterface::class, EloquentContactRepository::class)->public();
};
```

**Key points:**
- `$services->load()` autowires all classes in the specified directory
- Exclude DTOs and Exceptions from autowiring
- Repositories are explicitly set to implement their interfaces
- Use `->public()` for services that need to be accessible outside the container

## Step 6: Refactor Actions

Refactor page‑by‑page, starting with simple read‑only actions.

### 6.1 Pattern for Each Action

1. **Analyze** the legacy `includes/<action>.php`
2. **Create DTOs** in `Application/DTO/`
3. **Create Use Cases** in `Application/UseCases/`
4. **Create Controller** in `Application/Controllers/`
5. **Update template** (if needed)
6. **Add routes** in `config/routes.php`

### 6.2 Use Case Examples

**Simple read Use Case:**
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

**Access Guard Pattern** (for complex actions):
- `Ensure*AccessUseCase` – performs access checks (throws exceptions)
- `Get*ContextUseCase` – returns context DTO
- `*UseCase` – performs the main action

### 6.3 Invokable Controller Example
```php
<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Modules\Mail\Application\UseCases\GetContactListUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Users\User;
use Johncms\System\View\Render;

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
        $perPage = $this->user->config->kmess;
        
        $result = $this->getContactListUseCase->execute($userId, $page, $perPage);
        
        $this->navChain->add(__('Mail'), '/mail/');
        $this->navChain->add(__('Contacts'));
        
        $pageTitle = __('Contacts');
        $meta = new PageMeta($pageTitle, $page);
        $this->render->addData([
            'title'       => $meta->title,
            'page_title'  => $pageTitle,
            'description' => $meta->description,
        ]);
        
        return $this->render->render('mail::contact_list', [
            'data' => ['items' => $result->contacts],
        ]);
    }
}
```

### 6.4 Routes
Update `config/routes.php` (follow forum/mail patterns):

```php
<?php

declare(strict_types=1);

use Johncms\Modules\Mail\Application\Controllers\ContactController;
use Johncms\Router\RouteCollection;
use Johncms\System\Users\User;

return static function (RouteCollection $router, User $user): void {
    if ($user->isValid()) {
        // Invokable controller (single action)
        $router->get('/mail/', ContactController::class)->name('mail.index');
        $router->get('/mail/contacts/', ContactController::class)->name('mail.contacts');
        
        // For multi-action controllers: [Controller::class, 'method']
        // $router->get('/mail/blocklist', [BlocklistController::class, 'index']);
        
        // POST routes for form submissions
        // $router->post('/mail/contacts/add/{id:number}', AddContactController::class);
    }
};
```

## Step 7: Testing

### 7.1 Code Style
```bash
docker exec ${COMPOSE_PROJECT_NAME}.php-fpm composer cs-check
docker exec ${COMPOSE_PROJECT_NAME}.php-fpm composer cs-fix
```

### 7.2 Static Analysis
```bash
docker exec ${COMPOSE_PROJECT_NAME}.php-fpm composer psalm
```

### 7.3 Verify Functionality
- Test new routes
- Check data display and pagination
- Verify form submissions
- Test access controls

## Step 8: Cleanup

After all actions are refactored and tested:
1. Remove `includes/` directory
2. Remove old templates (or keep as backup)
3. Update `index.php` to use new architecture
4. Update module documentation in `docs/` (if applicable)

## Patterns

### Access Guard Pattern
```php
try {
    $this->ensureAccessUseCase->execute($userId, $targetId);
    $context = $this->getContextUseCase->execute($targetId);
    
    if ($request->isPost()) {
        $result = $this->actionUseCase->execute($userId, $context, $requestData);
        return redirect($result->redirectUrl);
    }
    
    return $this->renderForm($context);
} catch (AccessDeniedException $e) {
    return $this->renderError(__('Access denied'), 403);
}
```

### Repository Design
**Good** (only data access):
```php
public function getPaginatedContacts(int $userId, int $page, int $perPage): LengthAwarePaginator
{
    return Contact::query()
        ->where('user_id', $userId)
        ->paginate($perPage, ['*'], 'page', $page);
}
```

**Bad** (business logic in repository):
```php
public function getContactsWithMessageCount(int $userId): array
{
    $contacts = /* ... query ... */;
    foreach ($contacts as &$contact) {
        $contact['message_count'] = $this->countMessages($contact['id']); // Move to Use Case!
    }
    return $contacts;
}
```

### Legacy Redirects

Handle old `?act=` URLs with a redirect resolver service (pattern from forum):

**1. Create Redirect Resolver:**
```php
<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\Services;

final class MailLegacyRedirectResolver
{
    public function resolve(array $queryParams): ?string
    {
        if (!isset($queryParams['act'])) {
            return null;
        }

        $act = $queryParams['act'];
        
        return match ($act) {
            'index'    => '/mail/contacts/',
            'ignor'    => '/mail/blocklist/',
            'write'    => isset($queryParams['id']) 
                ? '/mail/conversation/' . (int)$queryParams['id'] . '/'
                : null,
            default    => null,
        };
    }
}
```

**2. Use in Controller:**
```php
public function __invoke(): string
{
    // Check for legacy redirect before processing request
    $legacyRedirect = $this->legacyRedirectResolver->resolve(
        $this->request->getQueryParams()
    );
    
    if ($legacyRedirect !== null) {
        http_response_code(301);
        header('Location: ' . $legacyRedirect);
        exit;
    }
    
    // Normal controller logic...
}
```

**3. Register in services.php:**
```php
$services->set(MailLegacyRedirectResolver::class)->autowire()->public();
```

### Page Titles with Pagination and Template Variables

For pages that support pagination, use the built‑in `PageMeta` class to generate proper `<title>` and meta `description` tags:

**1. Import PageMeta:**
```php
use Johncms\Http\PageMeta;
```

**2. Create PageMeta in Controller and add variables via addData:**
```php
public function __invoke(): string
{
    $page = max(1, (int) $this->request->getQuery('page', 1));
    $pageTitle = d__('module', 'Page Title');  // Main title (without "Page N")
    
    $meta = new PageMeta($pageTitle, $page);
    $this->render->addData([
        'title'       => $meta->title,        // Includes " — Page N" suffix for page > 1
        'page_title'  => $pageTitle,          // Original title (for template heading)
        'description' => $meta->description,  // Same as title but with description fallback
    ]);
    
    // Rest of controller logic...
}
```

**Important:** Variables added via `$this->render->addData()` are automatically available in all templates (including the layout template). Do **NOT** pass them again when rendering a specific template:

```php
// ✅ CORRECT: addData once, no duplicate passing
$this->render->addData([
    'title' => 'My Title',
    'page_title' => 'My Page Title',
]);

return $this->render->render('module::template', [
    'data' => $result,  // Only module-specific data
]);

// ❌ WRONG: redundant passing
return $this->render->render('module::template', [
    'title' => 'My Title',      // Already in addData
    'page_title' => 'My Title', // Already in addData  
    'data' => $result,
]);
```

**3. Template simplification:**
In your template file, use `$this->layout()` without passing title/page_title:

```php
<?php
/**
 * @var $data
 */
$this->layout('system::layout/default');
?>
<!-- Template content -->
```

The `system::layout/default` layout will automatically receive `$title` and `$page_title` from the global template data added via `addData()`.

**How PageMeta works:**
- **Page 1:** `PageMeta('Contacts', 1)` → title = `'Contacts'`, description = `'Contacts'`
- **Page 2:** `PageMeta('Contacts', 2)` → title = `'Contacts — Page 2'`, description = `'Contacts — Page 2'`
- **Custom description:** `PageMeta('Contacts', 2, 'User contact list')` → title = `'Contacts — Page 2'`, description = `'User contact list — Page 2'`

**Rules:**
- Always use `d__('system', 'Page')` for the word "Page" (translation)
- The suffix (` — Page N`) is added only from page 2 onward
- If a custom description is not provided, the title is used as description
- Apply consistently across all paginated pages in the module
- Use `addData()` for global template variables (title, page_title, description, canonical, keywords, etc.)
- Never pass the same variables both via `addData()` and template render arguments

### Error Handling
```php
try {
    $result = $useCase->execute($data);
} catch (NotFoundException $e) {
    return $this->renderError(__('Record not found'), 404);
} catch (AccessDeniedException $e) {
    return $this->renderError(__('Access denied'), 403);
}
```
