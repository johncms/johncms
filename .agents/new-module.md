# New Module Scaffold

How to wire a new module with the layered architecture. For layer responsibilities and repository rules, read `.agents/architecture.md`.

## Where a module lives

Modules lie under a vendor directory: everything shipped with the CMS is
`modules/johncms/<module>/`, and a third-party module is `modules/<vendor>/<module>/`.

The **key** of a module is `vendor/name` — where it lies, and what it is called as a package. The
**alias** is the flat name everything that cannot hold a slash uses: the Twig namespace (`@news`),
the gettext domain (`d__('news', …)`) and the source of its migrations (`migrate --source=news`).
A module shipped with the CMS declares its name as the alias; one that declares none gets
`vendor.name`.

## Manifest

Every module carries `module.php` next to its `config/` — that is what makes a directory a module:

```php
<?php

declare(strict_types=1);

return [
    'key'   => 'johncms/<module>',
    'alias' => '<module>',
    'name'  => 'What to call it in an interface',
];
```

* `key` must match the directory the file lies in, or the module is refused with an error.
* `alias` may only hold lowercase letters, digits, dots, hyphens and underscores — never a slash.
  **It is fixed once released**: the journal of migrations is written under it.
* `version` — omit it in a module shipped with the CMS; its version is the version of the CMS.
* `system` — `true` only for a module that must never be switched off (the admin panel).
* `autoload` — omit it in a module shipped with the CMS (see below).
* `assets` — the built styles and scripts the module ships:

  ```php
  'assets' => [
      // Directory inside the module. "public" by default.
      'source'  => 'public',
      // What every page of that area loads, relative to the source directory.
      'entries' => ['public' => ['js/blog.js', 'css/blog.css'], 'admin' => []],
  ],
  ```

  They are shipped **built**: there is no Vite on a site that only runs the CMS. Installing the
  module copies them to `public/modules/<alias>/`, switching it off takes them out again, and only
  files a browser loads are copied — a `.php` among them would be code the module put where anyone
  can run it.

  A template of the module addresses one file with `module_asset('blog', 'js/app.js')`; everything
  in `entries` is printed by the layout on its own.
* `requires` — what the module cannot run without:

  ```php
  'requires' => [
      'php'     => '^8.4',
      'johncms' => '^10.0',
      'modules' => ['johncms/forum' => '^10.0'],
  ],
  ```

  Constraints are Composer constraints, read by composer/semver. A module of the release declares
  no `php` and no `johncms` — it ships with the CMS — but **does** declare the modules whose
  services it is built against, because those can be switched off. Get that wrong and switching
  the other module off fails to compile the container instead of reporting a problem.

  A module whose requirement is not met is not loaded, and neither is anything built on it. The
  exception is a module a **system** module needs: it stays loaded, and the listing says who is
  holding it — obeying a configuration that takes the admin panel down would leave nobody able to
  put it back.

## Directory Structure

```
modules/johncms/<module>/
├── module.php       manifest: key, alias, name
├── config/          services.php, routes.php
├── locale/          the gettext domain of the module
├── migrations/      its tables — see .agents/migrations.md
├── templates/       public/ and admin/
└── src/
    ├── Application/{Controllers,UseCases,DTO,Services,Middlewares}
    ├── Domain/{Models,Repository,Entities,Enums}
    └── Infrastructure/Persistence/Repository
```

Tables are described by migrations and nowhere else — `src/Install/Installer.php` creates none.
What it does have is four hooks around them, every one of which must be safe to run twice:

| | when |
|---|---|
| `install()` | after the migrations of a fresh installation: settings, reference data, directories |
| `update(string $from, string $to)` | after the migrations of an update |
| `uninstall()` | while the module is still loaded, before it is taken off the site: uploaded files, rows in tables of the core, its own settings |
| `installDemoData()` | when the installer of the site was asked for demo data |

## Publishing a module

A module distributed to other sites is an ordinary Composer package with one line that matters:

```json
{
    "name": "vasya/blog",
    "type": "johncms-module",
    "require": { "php": "^8.4" },
    "autoload": { "psr-4": { "Vasya\\Blog\\": "src/" } }
}
```

`composer require vasya/blog` puts it in `vendor/vasya/blog`, and the CMS finds it there — the
Composer runtime is asked which packages of that type are installed, so no plugin is needed and
nothing moves the files. Composer then prints what to run next; installing is still a separate
step, because that is what runs the migrations.

The package also carries `module.php`. Its `key` must match the package name, and both `composer.json`
and the manifest declare the same PSR-4 prefix: Composer registers it for the package in vendor/,
the manifest for a copy unpacked into `modules/` from an archive.

## Lifecycle

```bash
php system/bin/console module:install --from=<archive.zip>   # unpack and install, or update
php system/bin/console module:install <vendor>/<name> [--demo]
php system/bin/console module:enable|disable <vendor>/<name>
php system/bin/console module:update <vendor>/<name>     # after its files were replaced
php system/bin/console module:uninstall <vendor>/<name> [--purge]
```

An archive holds one directory with a `module.php` inside it; where the module ends up is
decided by the key in that manifest, not by the name of the directory. It is unpacked into
`data/tmp/`, never straight into `modules/`, and a previous version is moved into `data/backups/`
before the new one takes its place. An archive of a module the site already has is treated as an
update.

Installing runs the migrations of the module, then `install()`. Uninstalling runs `uninstall()`
and forgets the module, **keeping its tables** — `--purge` is what actually undoes the migrations
and deletes the data, and it refuses outright if any migration of the module never said how to be
undone.

Switching off is not uninstalling: the tables stay, and so does the module in `migrate:status`.
What cannot be switched off is a system module, or one another installed module requires.

## Getting into the menus

A module that nobody can find is a module nobody uses, and both menus are templates of the theme.
So a module declares its lines and the theme draws them — implement `MenuItemProviderInterface`,
the container tags it:

```php
final class BlogMenu implements MenuItemProviderInterface
{
    public function menuItems(): iterable
    {
        return [
            new MenuItem(MenuArea::Main, d__('blog', 'Blog'), '/blog/', icon: 'book', weight: 50),
            new MenuItem(MenuArea::Admin, d__('blog', 'Blog'), '/admin/blog', permission: 'blog.manage'),
        ];
    }
}
```

* The title is already translated — the menu is drawn long after the domain of the page was decided.
* `permission` filters the line: an item the visitor may not open never reaches the template.
* `icon` is an id in the sprite of the theme (`book`), or the address of an image the module ships
  (`/modules/blog/img/icon.svg`).
* `weight` orders them: lighter floats up, equal weights fall back to the title.

**Permissions.** A module declaring them gets them granted by `auth:sync-roles`, and installing it
prints a reminder to run that — the container was compiled before the module existed, so its
providers are not in it yet. `--purge` takes them back out of every role, after writing what it
removes to `data/backups/permissions-<vendor>-<name>-<date>.json`; a plain uninstall keeps them,
the same way it keeps the tables.

**Create subdirectories on demand, not all upfront.** Only the three top-level folders (`Application`, `Domain`, `Infrastructure`) must exist immediately. Create each nested folder (`Controllers`, `UseCases`, `DTO`, `Repository`, …) only when its first class appears.

### Empty `src/` subdirectories

Create the three top-level folders immediately even if they are empty:

```bash
mkdir -p modules/johncms/<name>/src/Application modules/johncms/<name>/src/Domain modules/johncms/<name>/src/Infrastructure
```

Empty directories are not tracked by git, but they must exist on disk — otherwise Symfony DI will fail when loading `services.php`.

## Autoload

A module of the release goes in the root `composer.json`, where Composer can build it into an
optimised classmap:

```json
"Johncms\\Modules\\<Module>\\": "modules/johncms/<module>/src/"
```

```bash
docker exec $(docker ps -q -f name=johncms.php-fpm) composer dump-autoload
```

A module installed into a site cannot go there — the root `composer.json` belongs to the release,
and an upgrade would overwrite it. Such a module declares what to load in its own manifest, and
`ModuleAutoloader` registers it at boot:

```php
'autoload' => [
    'psr-4' => ['Vendor\\Module\\' => 'src/'],
    // Only if the package brings dependencies of its own.
    'files' => ['vendor/autoload.php'],
],
```

Its classes are registered while the module is switched on and disappear when it is switched off —
which is what makes switching one off mean anything.

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

A module the registry loads gets the Twig namespace of its alias without registering anything —
a module of the release is listed in `config/autoload/modules.global.php`, anything else is
recorded in the generated `modules.local.php`. The IDE cannot infer that convention, so regenerate the file it
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
