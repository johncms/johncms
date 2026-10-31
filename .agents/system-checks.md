# System diagnostics

`/admin/system_check` collects services implementing `Johncms\Checker\SystemCheckInterface`.
Core checks live in `system/src/Checker/Checks`. A module keeps its own checks in its Application
layer; it must not make the core depend on that module.

## Adding a check

Implement the interface on an autoconfigured service registered under its concrete class name.
`PSRContainerFactory` tags the service as `johncms.system_check`, so neither the controller nor
the runner needs editing. A service declared with its own `set()` must keep `->autoconfigure()`.

```php
final readonly class ExampleCheck implements SystemCheckInterface
{
    public function __construct(private ExampleStatusInterface $status)
    {
    }

    public static function definition(): CheckDefinitionDTO
    {
        return new CheckDefinitionDTO(
            'example.health',
            d__('example', 'Example service'),
            d__('example', 'Example diagnostics'),
        );
    }

    public function run(): array
    {
        $healthy = $this->status->isHealthy();

        return [new CheckResultDTO(
            'example.health',
            d__('example', 'Example service'),
            $healthy ? d__('example', 'Available') : d__('example', 'Unavailable'),
            $healthy ? CheckStatus::Passed : CheckStatus::Failed,
            CheckSeverity::Warning,
            d__('example', 'Check the service configuration and restart it if necessary.'),
        )];
    }
}
```

Use imports from `Johncms\Checker` and `Johncms\Checker\DTO`. IDs must be unique across enabled
modules. Group names are translated text; checks with the same group share a card. `order` puts
cards and checks in a stable order. The icon belongs to the existing admin icon set.

Keep `definition()` free of queries and probes. The runner reads static metadata before creating
services; this allows it to skip expensive dependencies and to report construction failures.
Closely related inexpensive rows, such as PHP extensions, can share one check class. Independent
operations that may fail separately should use separate checks.

## Profiles and results

* `Page` is the default: read-only diagnostics on the system check page.
* `Dashboard` is opt-in: inexpensive, read-only checks suitable for every dashboard visit.
* `Installer` is opt-in: requirements that work without an installed site or database.
* `Extended` runs after a CSRF-protected POST. It includes Page checks and explicitly selected
  active checks. Test data must use unique names and be removed in `finally`.

`CheckStatus` separates a completed pass or failure from a skipped or unavailable check.
`CheckSeverity` describes the impact of a completed failure: Critical, Warning or Info. A skipped
dependent check must not add a second warning for the same missing prerequisite. An unavailable
result must never be counted as healthy. Return a non-empty list of results.

Names, values and descriptions are plain text escaped by Twig. Supply an optional HTTP/HTTPS
`documentationUrl` for a manual link; do not put HTML in descriptions. Give an actionable remedy
and never return credentials, connection strings or raw exception messages.

The runner logs exceptions and produces an Unavailable row, then continues. It does not cache the
report. Migration diagnostics read `PendingMigrationsProviderInterface` directly: the banner's
`PendingMigrations` deliberately turns a failed read into zero and is unsuitable for diagnostics.

## Testing

Inject the source of facts. `PhpEnvironmentInterface` supplies PHP version, extensions and ini
values; `FilesystemProbeInterface` supplies directory access and disk space. Test rules with
controlled inputs, boundaries and failures. Test adapters with temporary directories or an
isolated database. Do not change the host's PHP configuration to exercise a missing extension.

`PlatformRequirements` is shared by the installer and running site. Its test verifies alignment
with the root Composer requirements; update both when changing supported PHP or extensions.
The web page describes the PHP process serving it, while CLI describes CLI. Neither is a rescue
entry point for failures that stop application bootstrap before diagnostics can run.

Run `sh .agents/scripts/verify.sh` after changes. Tests live in `tests/Unit/Checker` and
`tests/Functional/Admin/SystemCheckTest.php`.
