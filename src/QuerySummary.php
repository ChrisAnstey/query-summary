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

        // produce a collection of unique queries, with the count of how many times each occurred
        $counted = $statements->groupBy('query')->map(function ($items) {
            return count($items);
        });

        // loop through the unique queries, producing the output data (total calls, query time etc)
        $counted = $counted->map(function ($countedVal, $countedKey) use ($statements) {

            // get a full collection of the queries that match the current unique one
            $filtered = $statements->filter(function ($value) use ($countedKey) {
                return $value['query'] == $countedKey;
            });

            // use the first one, and add the summary details
            $firstExample = $filtered->first();
            $firstExample['sql'] = sprintf('%sx ', $countedVal) . $firstExample['query'];
            $firstExample['duration_str'] = $this->formatDuration($filtered->sum('time'));
            $firstExample['duration'] = $filtered->sum('time');
            $firstExample['count'] = $countedVal;

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
                "widget" => "PhpDebugBar.Widgets.LaravelSQLQueriesWidget",
                "map" => "querysummary",
                "default" => "[]"
            ],
            "querysummary:badge" => [
                "map" => "querysummary.nb_statements",
                "default" => 0
            ]
        ];
    }
}