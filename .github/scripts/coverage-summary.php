<?php

/**
 * Turns the Clover report of PHPUnit into a table of line coverage by area, for the summary of a
 * CI run: the core by its top directory, every module on its own, the least covered first.
 *
 * It is a map of where tests are missing, not a gate: nothing here fails the run.
 *
 *     php .github/scripts/coverage-summary.php coverage.xml >> "$GITHUB_STEP_SUMMARY"
 */

declare(strict_types=1);

$report = $argv[1] ?? '';
$clover = is_file($report) ? simplexml_load_file($report) : false;

if ($clover === false) {
    fwrite(STDERR, sprintf('There is no Clover report at "%s".' . PHP_EOL, $report));
    exit(1);
}

$root = getcwd() . DIRECTORY_SEPARATOR;
$areas = [];

foreach ($clover->xpath('//file') ?: [] as $file) {
    $path = str_replace($root, '', (string) $file['name']);

    $area = match (true) {
        preg_match('#^system/src/([^/]+)/#', $path, $matches) === 1    => 'core: ' . $matches[1],
        str_starts_with($path, 'system/src/')                          => 'core',
        preg_match('#^modules/([^/]+/[^/]+)/#', $path, $matches) === 1 => $matches[1],
        default                                                        => 'other',
    };

    $areas[$area] ??= ['lines' => 0, 'covered' => 0];
    $areas[$area]['lines'] += (int) $file->metrics['statements'];
    $areas[$area]['covered'] += (int) $file->metrics['coveredstatements'];
}

$percent = static fn (array $area): float => $area['lines'] === 0 ? 0.0 : 100 * $area['covered'] / $area['lines'];

uasort($areas, static fn (array $a, array $b): int => $percent($a) <=> $percent($b));

$total = [
    'lines'   => array_sum(array_column($areas, 'lines')),
    'covered' => array_sum(array_column($areas, 'covered')),
];

echo '## Line coverage', PHP_EOL, PHP_EOL;
echo '| Area | Covered | Lines | % |', PHP_EOL;
echo '|---|---:|---:|---:|', PHP_EOL;
printf('| **Total** | %d | %d | **%.1f** |' . PHP_EOL, $total['covered'], $total['lines'], $percent($total));

foreach ($areas as $name => $area) {
    printf('| %s | %d | %d | %.1f |' . PHP_EOL, $name, $area['covered'], $area['lines'], $percent($area));
}
