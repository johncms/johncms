<?php

declare(strict_types=1);

namespace Johncms\Debug\Collectors;

use DebugBar\DataCollector\PDO\PDOCollector;
use Exception;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Connection;
use Illuminate\Support\Str;

/**
 * Collects data about SQL statements executed with PDO
 */
class QueryCollector extends PDOCollector
{
    protected $timeCollector;
    protected array $queries = [];
    protected $renderSqlWithParams = false;
    protected bool $durationBackground = true;
    protected bool $explainQuery = false;
    protected array $explainTypes = ['SELECT', 'INSERT', 'UPDATE', 'DELETE'];
    protected bool $showCopyButton = true;

    public function setExplainQuery(bool $enabled = true)
    {
        $this->explainQuery = $enabled;
    }

    /**
     * Renders the SQL of traced statements with params embedded
     *
     * @param boolean $enabled
     * @param string $quotationChar NOT USED
     */
    public function setRenderSqlWithParams($enabled = true, $quotationChar = "'")
    {
        $this->renderSqlWithParams = $enabled;
    }

    /**
     * Show or hide copy button next to the queries
     */
    public function setShowCopyButton(bool $enabled = true)
    {
        $this->showCopyButton = $enabled;
    }

    /**
     * Enable/disable the shaded duration background on queries
     */
    public function setDurationBackground(bool $enabled)
    {
        $this->durationBackground = $enabled;
    }

    public function addQuery(string $query, array $bindings, float $time, Connection $connection)
    {
        $explainResults = [];
        $time = $time / 1000;
        $endTime = microtime(true);
        $startTime = $endTime - $time;

        $pdo = null;
        try {
            $pdo = $connection->getPdo();
        } catch (Exception) {
            // ignore error for non-pdo laravel drivers
        }
        $bindings = $connection->prepareBindings($bindings);

        // Run EXPLAIN on this query (if needed)
        if ($this->explainQuery && $pdo && preg_match('/^\s*(' . implode('|', $this->explainTypes) . ') /i', $query)) {
            $statement = $pdo->prepare('EXPLAIN ' . $query);
            $statement->execute($bindings);
            $explainResults = $statement->fetchAll(\PDO::FETCH_CLASS);
        }

        $bindings = $this->getDataFormatter()->checkBindings($bindings);
        if (! empty($bindings) && $this->renderSqlWithParams) {
            foreach ($bindings as $key => $binding) {
                // This regex matches placeholders only, not the question marks,
                // nested in quotes, while we iterate through the bindings
                // and substitute placeholders by suitable values.
                $regex = is_numeric($key)
                    ? "/(?<!\?)\?(?=(?:[^'\\\']*'[^'\\']*')*[^'\\\']*$)(?!\?)/"
                    : "/:{$key}(?=(?:[^'\\\']*'[^'\\\']*')*[^'\\\']*$)/";

                // Mimic bindValue and only quote non-integer and non-float data types
                if (! is_int($binding) && ! is_float($binding)) {
                    if ($pdo) {
                        try {
                            $binding = $binding !== null ? $pdo->quote($binding) : 'null';
                        } catch (Exception) {
                            $binding = $this->emulateQuote($binding);
                        }
                    } else {
                        $binding = $this->emulateQuote($binding);
                    }
                }

                $query = preg_replace($regex, addcslashes((string) $binding, '$'), $query, 1);
            }
        }

        $this->queries[] = [
            'query'      => $query,
            'type'       => 'query',
            'bindings'   => $this->getDataFormatter()->escapeBindings($bindings),
            'time'       => $time,
            'explain'    => $explainResults,
            'connection' => $connection->getDatabaseName(),
            'driver'     => $connection->getConfig('driver'),
            'show_copy'  => $this->showCopyButton,
        ];

        $this->timeCollector?->addMeasure(Str::limit($query), $startTime, $endTime);
    }

    /**
     * Mimic mysql_real_escape_string
     */
    protected function emulateQuote(string $value): string
    {
        $search = ["\\", "\x00", "\n", "\r", "'", '"', "\x1a"];
        $replace = ["\\\\", "\\0", "\\n", "\\r", "\'", '\"', "\\Z"];

        return "'" . str_replace($search, $replace, $value) . "'";
    }

    /**
     * {@inheritDoc}
     */
    public function collect(): array
    {
        $connection = Manager::connection();
        $queryLog = $connection->getQueryLog();
        foreach ($queryLog as $query) {
            $this->addQuery($query['query'], $query['bindings'], $query['time'], $connection);
        }

        $totalTime = 0;
        $queries = $this->queries;

        $statements = [];
        foreach ($queries as $query) {
            $totalTime += $query['time'];

            $statements[] = [
                'sql'          => $this->getDataFormatter()->formatSql($query['query']),
                'type'         => $query['type'],
                'params'       => [],
                'bindings'     => $query['bindings'],
                'show_copy'    => $query['show_copy'],
                'backtrace'    => [],
                'duration'     => $query['time'],
                'duration_str' => ($query['type'] == 'transaction') ? '' : $this->formatDuration($query['time']),
                'stmt_id'      => $this->getDataFormatter()->formatSource(''),
                'connection'   => $query['connection'],
            ];

            // Add the results from the explain as new rows
            if ($query['driver'] === 'pgsql') {
                $explainer = trim(
                    implode(
                        "\n",
                        array_map(fn($explain) => $explain->{'QUERY PLAN'}, $query['explain'])
                    )
                );

                if ($explainer) {
                    $statements[] = [
                        'sql'  => " - EXPLAIN: {$explainer}",
                        'type' => 'explain',
                    ];
                }
            } else {
                foreach ($query['explain'] as $explain) {
                    $statements[] = [
                        'sql'       => " - EXPLAIN # {$explain->id}: `{$explain->table}` ({$explain->select_type})",
                        'type'      => 'explain',
                        'params'    => $explain,
                        'row_count' => $explain->rows,
                        'stmt_id'   => $explain->id,
                    ];
                }
            }
        }

        if ($this->durationBackground) {
            if ($totalTime > 0) {
                // For showing background measure on Queries tab
                $start_percent = 0;

                foreach ($statements as $i => $statement) {
                    if (! isset($statement['duration'])) {
                        continue;
                    }

                    $width_percent = $statement['duration'] / $totalTime * 100;

                    $statements[$i] = array_merge($statement, [
                        'start_percent' => round($start_percent, 3),
                        'width_percent' => round($width_percent, 3),
                    ]);

                    $start_percent += $width_percent;
                }
            }
        }

        $nb_statements = array_filter($queries, fn($query) => $query['type'] === 'query');

        return [
            'nb_statements'            => count($nb_statements),
            'nb_failed_statements'     => 0,
            'accumulated_duration'     => $totalTime,
            'accumulated_duration_str' => $this->formatDuration($totalTime),
            'statements'               => $statements,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'queries';
    }

    /**
     * {@inheritDoc}
     */
    public function getWidgets(): array
    {
        return [
            "queries"       => [
                "icon"    => "database",
                "widget"  => "PhpDebugBar.Widgets.queryLogWidget",
                "map"     => "queries",
                "default" => "[]",
            ],
            "queries:badge" => [
                "map"     => "queries.nb_statements",
                "default" => 0,
            ],
        ];
    }
}
