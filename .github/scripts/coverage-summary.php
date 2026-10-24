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

$header = ['Area', 'Covered', 'Lines', '%'];
$rows = [['**Total**', (string) $total['covered'], (string) $total['lines'], sprintf('**%.1f**', $percent($total))]];

foreach ($areas as $name => $area) {
    $rows[] = [$name, (string) $area['covered'], (string) $area['lines'], sprintf('%.1f', $percent($area))];
}

// Every cell is padded to the widest one of its column, so the table lines up in a terminal too.
// The padding changes nothing in how Markdown renders it. A rule needs at least three dashes.
$widths = array_map(
    static fn (int $column): int => max(3, ...array_map(static fn (array $row): int => strlen($row[$column]), [$header, ...$rows])),
    array_keys($header),
);

// The area is text and is aligned left, the numbers are aligned right.
$line = static fn (array $cells): string => '| ' . implode(' | ', array_map(
    static fn (string $cell, int $column): string => str_pad($cell, $widths[$column], ' ', $column === 0 ? STR_PAD_RIGHT : STR_PAD_LEFT),
    $cells,
    array_keys($cells),
)) . ' |' . PHP_EOL;

$rule = array_map(
    static fn (int $width, int $column): string => $column === 0 ? str_repeat('-', $width) : str_repeat('-', $width - 1) . ':',
    $widths,
    array_keys($widths),
);

echo '## Line coverage', PHP_EOL, PHP_EOL;
echo $line($header), $line($rule);

foreach ($rows as $row) {
    echo $line($row);
}
