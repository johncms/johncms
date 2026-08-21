# Tests

Two suites, run by the same command:

```bash
docker exec $(docker ps -q -f name=johncms.php-fpm) composer test            # both
docker exec $(docker ps -q -f name=johncms.php-fpm) composer test:unit       # tests/Unit
docker exec $(docker ps -q -f name=johncms.php-fpm) composer test:functional # tests/Functional
```

Neither needs a server or an installed site: both run against SQLite in memory, which is why
CI runs them.

## Unit

One class, its collaborators doubled. A test that needs tables boots a database of its own:

```php
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\RunsMigrations;

$this->bootDatabase();
$this->migrate('forum', 'initial_schema'); // a source, optionally single migrations of it
```

Ask for the migrations the test depends on rather than for all of them — the list says what it
is about.

## Functional

Extend `Tests\Functional\FunctionalTestCase`. It boots the application once per process,
builds the schema with the migrations of the core and of every module, seeds the system roles,
and rolls back a transaction after every test — nothing has to be cleaned up by hand.

A request goes through `Kernel::handle()`, the real router, the real middleware and the real
templates:

```php
$response = $this->handleRequest('/admin/news', cookies: $this->actingAs($user));
```

`actingAs()` opens a real session and answers with the cookies to drive requests with, so the
visitor is authenticated the way a live one is.

### The visitor

`Tests\Support\FunctionalUserFactory` writes the account, and the test says what it may do:

```php
FunctionalUserFactory::create();                                   // an ordinary account
FunctionalUserFactory::createWithPermissions([NewsPermissions::MANAGE]); // exactly this permission
FunctionalUserFactory::createSupervisor();                         // the staff, allowed anything
```

Never look for an account, a category or a file that an installation happens to have: the
database of a test is empty apart from what the test itself writes.

### Other rows

`Tests\Support\Fixture::insert('library_texts', ['name' => 'An article'])` writes a row into
any table and fills in the columns the test did not mention. The tables of 9.x are full of NOT
NULL columns without a default, and naming them all would bury the two that the assertion is
about.

## What not to assert

* The page title across two requests in one test — `Render` still keeps it in process state.
* Anything MySQL answers differently: the suites run on SQLite, so a full-text search or a
  collation-dependent order belongs to a test that does not need a database at all.
