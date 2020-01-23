<?php
namespace Maraful\QuerySummary;

use Debugbar;
use DebugBar\DataCollector\DataCollectorInterface;
use DebugBar\DataCollector\Renderable;
use Barryvdh\Debugbar\DataCollector\QueryCollector;

/**
 * Provides summary of queries, grouped by uniques queries for Laravel DebugBar.
 *
 * Uses own listener to capture db queries.
 */
class QuerySummary extends QueryCollector implements DataCollectorInterface, Renderable
{

    /**
     * {@inheritDoc}
     */
    public function getName() {
        return "querysummary";
    }

    /**
     * {@inheritDoc}
     */
    public function collect()
    {
        $statements = collect($this->queries);

        // group them by query
        $grouped = $statements->groupBy('query');

        // loop through the unique queries, producing the output data (total calls, query time etc)
        $counted = $grouped->map(function ($group, $key) {

            // use the first one, and add the summary details
            $firstExample = $group->first();
            $firstExample['sql'] = sprintf('%sx ', $group->count()) . $firstExample['query'];
            $firstExample['duration_str'] = $this->formatDuration($group->sum('time'));
            $firstExample['duration'] = $group->sum('time');
            $firstExample['count'] = $group->count();
            $firstExample['backtrace'] = array_values($firstExample['source']);
            $firstExample['stmt_id'] = $this->getDataFormatter()->formatSource(reset($firstExample['source']));
            // create subgroup by the source of the query in the code
            $subGrouped = $group->groupBy(function($item) {
                $key = '';
                foreach ($item['source'] as $source) {
                    $key .= $source->name . " ln" . $source->line . "<br />";
                }
                return $key;
            })->map(function ($items) {
                return [
                    'count' => count($items),
                    'source' => $items->first()['source'],
                    'duration' => $items->sum('time'),
                    'duration_str' => $this->formatDuration($items->sum('time')),
                ];
            });
            $firstExample['subCount'] = $subGrouped->toArray();

            return $firstExample;
        });

        // for now, always sort by total time descending (allow config in future(?))
        $counted = $counted->sortByDesc('duration');

        $totalTime = $counted->sum('duration');

        // populate data used by js
        $queryData = [
            'nb_statements' => $counted->count(),
            'nb_failed_statements' => 0,
            'accumulated_duration' => $totalTime,
            'accumulated_duration_str' => $this->formatDuration($totalTime),
            'statements' => $counted->values()->toArray(),
        ];

        return $queryData;
    }

    /**
     * {@inheritDoc}
     */
    public function getWidgets()
    {
        return [
            "querysummary" => [
                "icon" => "database",
                "widget" => "PhpDebugBar.Widgets.LaravelSQLQuerySummaryWidget",
                "map" => "querysummary",
                "default" => "[]"
            ],
            "querysummary:badge" => [
                "map" => "querysummary.nb_statements",
                "default" => 0
            ]
        ];
    }

    /**
     * Check if the given file is to be excluded from analysis
     *
     * @param string $file
     * @return bool
     */
    protected function fileIsInExcludedPath($file)
    {
        // first check it's not excluded by parent class
        if (parent::fileIsInExcludedPath($file)) {
            return true;
        }

        // not excluded already, so check it's not our package code

        $excludedPath = '/maraful/query-summary/src/ServiceProvider';
        $normalizedPath = str_replace('\\', '/', $file);

        return (strpos($normalizedPath, $excludedPath) !== false);
    }
}