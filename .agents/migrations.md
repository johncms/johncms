# Database Migrations

Every change to the schema is a migration. There is no second place a table is described in: the
installer of a fresh site and the upgrade of an existing one run the same files.

## Where they live

```
system/migrations/                  the core
modules/<vendor>/<module>/migrations/   a module (the CMS ships its own under johncms/)
```

Outside `src/` on purpose — a file under `src/` is picked up by the directory load of the
container, and PSR-4 would force the class name to match a file name that has to stay sortable.

File name: `2026_09_01_120000_add_slug_to_sections.php`. The timestamp is the version, the rest is
a description. Identity in the journal is the **source and the version**, never the file name, so
two modules may share a minute and the description may be corrected later.

Create one with the generator rather than by hand — it picks a version no other migration of that
source has taken:

```bash
php system/bin/console make:migration forum add_slug_to_sections --table=forum_sections
php system/bin/console make:migration system create_widgets_table --table=widgets --create
```

## What one looks like

```php
<?php

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;
use Johncms\Database\Schema\TableDefinition;

return new class extends Migration {
    public function up(): void
    {
        $this->schema->create('forum_sections', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('parent')->unsigned()->default(0)->index();
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->schema->dropIfExists('forum_sections');
    }
};
```

A migration is given two things and nothing else: `$this->schema` (`SchemaInterface`) and
`$this->db` (`ConnectionInterface`).

## Rules

1. **No models, repositories, use cases or services.** A migration is a historical record: the one
   written today still has to run, unchanged, on a site upgrading two years from now. Application
   code will have moved on by then, and the failure surfaces on somebody else's installation with
   no way back. Move data with SQL through `$this->db`.
2. **No `config()` and no `di()`**, for the same reason. A migration that needs a setting is a
   migration that does something different on two sites.
3. **A released migration is never edited.** Correct a mistake with a new migration on top.
   `migrate:status` points out a file that changed after it ran.
4. **A migration never touches the tables of another module** — the same rule as everywhere else.
5. **No platform-only idioms**: no column positioning, no hand-written type definitions. What is
   not in `TableDefinition` cannot be carried out by every adapter behind `SchemaInterface`.
6. **Idempotence is for the baseline only.** The `initial_*` snapshots of 10.0 create what is
   missing and leave the rest alone, because a site upgrading from 9.9 already has most of it.
   Everything after them is a plain step forward, with no `hasTable()` around it.

`down()` is optional. Saying nothing refuses the rollback instead of quietly destroying what the
step forward carried, which is the right answer for most migrations. Rolling back is a tool of
development: on a site that is not in debug mode it demands `--force`.

## Running them

```bash
php system/bin/console migrate                 # apply what is pending
php system/bin/console migrate --source=forum  # one source only
php system/bin/console migrate --dry-run       # say what would run
php system/bin/console migrate:status          # applied, waiting, and files edited after they ran
php system/bin/console migrate:rollback        # undo the last batch (development)
```

An administrator without a shell runs `migrate` from **Maintenance** in the admin panel, where it
is queued and started by the scheduler. While anything is pending, the panel says so on every
page.

DDL is not transactional on MySQL. A run stops at the migration that failed and does not record
it, so running again starts from it — but it may have carried out part of its work, and the error
message says so. A migration that only moves data may ask for a transaction with
`useTransaction(): true`.

## Tables of a new module

Nothing is created in `Install/Installer` — it is for demo data only. Add
`modules/<vendor>/<module>/migrations/` and describe the tables there; the source is named
after the name of the module — `forum`, never `johncms/forum` — and must not be renamed once its
migrations have run anywhere. The source is named after the
directory of the module and must not be renamed once its migrations have run anywhere.

## In tests

```php
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\RunsMigrations;

$this->bootDatabase();
$this->migrate('forum');                            // the whole source
$this->migrate('system', 'initial_auth_schema');    // one migration of it
```

Ask for the migration the test needs rather than the whole source — it says what the test depends
on, and for the core it is the only way: index names inherited from 9.x repeat across tables,
which MySQL keeps per table and SQLite per database, so the core baseline cannot be built whole on
the database the unit suite runs on.

`BaselineSchemaParityTest` holds the `initial_*` snapshots to the schema they replaced. If a
baseline is edited, that test is what says so.
