<?php
namespace Maraful\QuerySummary;

use Debugbar;
use DebugBar\DataCollector\DataCollectorInterface;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;

/**
 * Provides summary of queries, grouped by uniques queries for Laravel DebugBar.
 *
 * "DataCollector" but, actually just uses the data from queries collector.
 */
class QuerySummary extends DataCollector implements DataCollectorInterface, Renderable
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
        $queryData = Debugbar::getCollector('queries')->collect();

        $statements = collect($queryData['statements']);

        // produce a collection of unique queries, with the count of how many times each occurred
        $counted = $statements->countBy(function ($statement) {
            return $statement['sql'];
        });

        // loop through the unique queries, producing the output data (total calls, query time etc)
        $counted = $counted->map(function ($countedVal, $countedKey) use ($statements) {

            // get a full collection of the queries that match the current unique one
            $filtered = $statements->filter(function ($value) use ($countedKey) {
                return $value['sql'] == $countedKey;
            });

            // use the first one, and add the summary details
            $firstExample = $filtered->first();
            $firstExample['sql'] = sprintf('%sx ', $countedVal) . $firstExample['sql'];
            $firstExample['duration_str'] = $this->formatDuration($filtered->sum('duration'));
            $firstExample['duration'] = $filtered->sum('duration');
            $firstExample['count'] = $countedVal;

            return $firstExample;
        });

        // for now, always sort by total time descending (allow config in future(?))
        $counted = $counted->sortByDesc('duration');

        // populate data used by js
        $queryData['statements'] = $counted->values()->toArray();
        $queryData['nb_statements'] = $counted->count();

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