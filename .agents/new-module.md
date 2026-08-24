# New Module Scaffold

How to wire a new module with the layered architecture. For layer responsibilities and repository rules, read `.agents/architecture.md`.

## Where a module lives

Modules lie under a vendor directory: everything shipped with the CMS is
`modules/johncms/<module>/`, and a third-party module is `modules/<vendor>/<module>/`.

The **name** of the module — the last path segment — is what everything else is keyed by: the
Twig namespace (`@news`), the gettext domain (`d__('news', …)`) and the source of its migrations
(`migrate --source=news`). The vendor exists on disk and nowhere else.

## Directory Structure

```
modules/johncms/<module>/
├── config/          services.php, routes.php
├── locale/          the gettext domain of the module
├── migrations/      its tables — see .agents/migrations.md
├── templates/       public/ and admin/
└── src/
    ├── Application/{Controllers,UseCases,DTO,Services,Middlewares}
    ├── Domain/{Models,Repository,Entities,Enums}
    └── Infrastructure/Persistence/Repository
```

Tables are described by migrations and nowhere else. `src/Install/Installer.php` is for demo data
only — it has no `install()` and creates nothing.

**Create subdirectories on demand, not all upfront.** Only the three top-level folders (`Application`, `Domain`, `Infrastructure`) must exist immediately. Create each nested folder (`Controllers`, `UseCases`, `DTO`, `Repository`, …) only when its first class appears.

### Empty `src/` subdirectories

Create the three top-level folders immediately even if they are empty:

```bash
mkdir -p modules/johncms/<name>/src/Application modules/johncms/<name>/src/Domain modules/johncms/<name>/src/Infrastructure
```

Empty directories are not tracked by git, but they must exist on disk — otherwise Symfony DI will fail when loading `services.php`.

## Autoload

Update `composer.json` with PSR-4 autoload:

```json
"Johncms\\Modules\\<Module>\\": "modules/johncms/<module>/src/"
```

Run `composer dump-autoload` in the php-fpm container:

```bash
docker exec $(docker ps -q -f name=johncms.php-fpm) composer dump-autoload
```

## Services Configuration

`config/services.php`:

```php
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->load(
        'Johncms\\Modules\\<Module>\\Application\\',
        MODULES_PATH . 'johncms/<module>/src/Application'
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
       <target>modules/johncms/<module>/locale</target>
       <sourceDir>modules/johncms/<module></sourceDir>
   </domain>
   ```

2. `crowdin.yml` — a `files` entry, otherwise the domain never reaches Crowdin:

   ```yaml
   - source: /modules/johncms/<module>/locale/<module>.pot
     translation: /modules/johncms/<module>/locale/%two_letters_code%.po
   ```

Then generate the template and the runtime dictionaries:

```bash
docker exec $(docker ps -q -f name=johncms.php-fpm) composer translate-scan
docker exec $(docker ps -q -f name=johncms.php-fpm) composer translate
```

Add a `<lang>.po` in `modules/johncms/<module>/locale/` for each language you were asked to translate. For the full pipeline and Crowdin commands, read `.agents/localization.md`.

## IDE Template Navigation

A module listed in `config/autoload/modules.global.php` gets the Twig namespace `@<module>`
without registering anything. The IDE cannot infer that convention, so regenerate the file it
reads after adding the module:

```bash
docker exec $(docker ps -q -f name=johncms.php-fpm) php system/bin/console twig:ide-config
```

Commit the resulting `ide-twig.json`. The verification gate runs the command with `--check` and
fails while the file is stale.

## Template Notes

Templates live in `modules/johncms/<module>/templates/{public,admin}/` and are reachable as
`@<module>/public/<page>.twig`. A controller returns `ViewResponse` with the template name and
the data; the page extends `@theme/layouts/default.twig` (or `@admin/layouts/default.twig` in
the panel) and fills `{% block content %}`.

The full set of rules — namespaces, environments, components, the traps — is in
`.agents/templates.md`.
