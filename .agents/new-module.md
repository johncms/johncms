# New Module Scaffold

How to wire a new module with the layered architecture. For layer responsibilities and repository rules, read `.agents/architecture.md`.

## Directory Structure

```
modules/<module>/src/
├── Application/{Controllers,UseCases,DTO,Services,Middlewares}
├── Domain/{Models,Repository,Entities,Enums}
└── Infrastructure/Persistence/Repository
```

**Create subdirectories on demand, not all upfront.** Only the three top-level folders (`Application`, `Domain`, `Infrastructure`) must exist immediately. Create each nested folder (`Controllers`, `UseCases`, `DTO`, `Repository`, …) only when its first class appears.

### Empty `src/` subdirectories

Create the three top-level folders immediately even if they are empty:

```bash
mkdir -p modules/<name>/src/Application modules/<name>/src/Domain modules/<name>/src/Infrastructure
```

Empty directories are not tracked by git, but they must exist on disk — otherwise Symfony DI will fail when loading `services.php`.

## Autoload

Update `composer.json` with PSR-4 autoload:

```json
"Johncms\\Modules\\<Module>\\": "modules/<module>/src/"
```

Run `composer dump-autoload` in the php-fpm container:

```bash
docker exec $(docker ps -q -f name=johncms9.php-fpm) composer dump-autoload
```

## Services Configuration

`config/services.php`:

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

## Routes & Middleware

```php
return static function (RouteCollection $router): void {
    $mailGroup = $router->group('', function (RouteCollection $r): void {
        $r->get('/mail/contacts/', ContactController::class);
        // ... other routes
    });
    $mailGroup->addMiddleware(AuthorizedUserMiddleware::class);
};
```

Authorization middleware lives in `src/Application/Middlewares/`:

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

**Trailing slash convention:** define routes *without* a trailing slash (`/downloads/search`), but use a trailing slash in template and controller links (`/downloads/search/`). `index.php` normalises URIs with `rtrim` before matching, so both variants work at runtime.

## Localization

A new module gets its own gettext domain, named after the module. Register it in **both** places, or its strings silently stay untranslated:

1. `translate.xml.dist` — a `<domain>` entry, otherwise `translate-scan` never produces a `.pot`:

   ```xml
   <domain>
       <name><module></name>
       <target>modules/<module>/locale</target>
       <sourceDir>modules/<module></sourceDir>
   </domain>
   ```

2. `crowdin.yml` — a `files` entry, otherwise the domain never reaches Crowdin:

   ```yaml
   - source: /modules/<module>/locale/<module>.pot
     translation: /modules/<module>/locale/%two_letters_code%.po
   ```

Then generate the template and the runtime dictionaries:

```bash
docker exec $(docker ps -q -f name=johncms9.php-fpm) composer translate-scan
docker exec $(docker ps -q -f name=johncms9.php-fpm) composer translate
```

Add a `<lang>.po` in `modules/<module>/locale/` for each language you were asked to translate. For the full pipeline and Crowdin commands, read `.agents/localization.md`.

## Template Notes

* Templates call `$this->layout('system::layout/default')` without arguments.
* Global Plates variables (e.g. `$user`) are not passed explicitly; IDE won't see them — add a `@var` annotation with the FQCN. Note: `use` statements do not work in `.phtml` files, so use the fully-qualified class name:

  ```php
  /** @var \Johncms\Users\User $user */
  ```
