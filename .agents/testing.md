# Tests

Two suites, run by the same command:

```bash
docker exec $(docker ps -q -f name=johncms.php-fpm) composer test            # both
docker exec $(docker ps -q -f name=johncms.php-fpm) composer test:unit       # tests/Unit
docker exec $(docker ps -q -f name=johncms.php-fpm) composer test:functional # tests/Functional
```

Neither needs a server or an installed site: both run against SQLite in memory, which is why
CI runs them. The functional suite renders real pages, so the assets have to be built once
(`npm run build`) — a layout asks Vite for the entry point of its theme, and without the
compiled bundle every page fails on the missing manifest.

### Order

Tests run in random order, failed ones first. A failure that only some orders produce comes from
process state a test relies on without setting it — a static property, `ConfigRepository`, the
trusted proxies of `Request`. The seed is printed at the top of the run; repeat it with
`--random-order-seed=<seed>`, and make the test set that state in `setUp()` itself.

### The installer

`InstallerTest` installs a site from scratch: it serves `public/` with the built-in server of PHP,
goes through the steps of the installer as a browser would, then opens the home page and the panel
as the administrator the installer created. The installer writes `config/autoload/*.local.php` and
works with MySQL only, so the test needs a fresh checkout and `TEST_DATABASE_URL`, and skips itself
anywhere else — the checkout of the stack included. CI runs it on every server of the matrix.

To run it locally, give it a checkout of its own:

```bash
git worktree add /tmp/johncms-install HEAD
cp -r vendor /tmp/johncms-install/ && cp -r public/build /tmp/johncms-install/public/
docker run --rm --network johncms_app_network -v /tmp/johncms-install:/app -w /app \
    -e TEST_DATABASE_URL=mysql://root:password@mariadb:3306/johncms_install_test \
    johncms-php-fpm composer test:install
git worktree remove --force /tmp/johncms-install
```

### Coverage

CI measures line coverage on every push and lists it by area in the summary of the run, the least
covered first. It is a map of where tests are missing, not a threshold. The same table locally:

```bash
docker exec -e XDEBUG_MODE=coverage $(docker ps -q -f name=johncms.php-fpm) \
    vendor/bin/phpunit --coverage-clover=data/cache/coverage.xml
docker exec $(docker ps -q -f name=johncms.php-fpm) \
    php .github/scripts/coverage-summary.php data/cache/coverage.xml
```

### Against MySQL

CI runs the functional suite once more against MySQL 5.7 and 8.4 and MariaDB 10.2 and 11. Point
it at the MariaDB of the stack to do the same locally:

```bash
docker exec -e TEST_DATABASE_URL=mysql://root:password@mariadb:3306/johncms_test \
    $(docker ps -q -f name=johncms.php-fpm) composer test:functional
```

The password of root is `DB_PASSWORD` from `.env`. The suite drops the database it is given and
builds the schema anew, which is why `FunctionalDatabase` refuses a name that does not end in
`_test`.

MySQL commits the open transaction before DDL and TRUNCATE, and what a test wrote until then
would stay for every test after it. The harness fails such a test instead: a page or a test that
alters a table during a request does not belong in the suite as it is.

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

A form that posts carries the CSRF token the page put into it, and the check refuses it otherwise:

```php
$response = $this->handleRequest('/guestbook', 'POST', $this->withCsrfToken(['text' => 'Hello']));
```

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

### Every page

`RouteCrawlTest` opens every GET route that needs no placeholder filled as a guest, as a member
and as the staff. Every page must answer without a server error, finish the document it starts
and put the CSRF token into each form that posts; the panel must let nobody in but the staff, and
the staff must be refused nothing. The list comes from the router, so a new page is covered with no
change to the test.

A page that fails there is a bug of the page. The one exception is a page asking MySQL for
something SQLite does not have: it goes to `MYSQL_ONLY` with the reason, and the run against
MySQL still opens it.

When a request answers 500, `handleRequest(..., catch: false)` hands the test the exception the
kernel would have logged.

## Before changing untested code

Most of the legacy modules have no tests of their own. Before refactoring one, write tests that
pin what it does now — the pages a visitor gets, the statuses, the redirects, the rows a form
writes, the messages it shows — and commit them on their own, ahead of the refactoring:

```
test(library): record the current behaviour before moving the controllers
refactor(library): ...
```

They describe what the code does, not what it should do: an odd answer is pinned as it is, with a
comment saying so, and changed by a commit of its own that says why. Then the refactoring is
checked by tests written before it, which is the only way a test says the behavior was kept.

`LegacyValidatorCharacterizationTest` is an example: its cases name a rule and its options rather
than the engine, so the same cases accepted the engine that replaced it.

## What not to assert

* The page title across two requests in one test — `Render` still keeps it in process state.
* Anything MySQL answers differently from SQLite: a functional test has to pass on both, so a
  full-text search or a collation-dependent order belongs to a test that does not need a database
  at all.
