<?php
namespace Maraful\QuerySummary;

use Debugbar;
use Barryvdh\Debugbar\DataFormatter\QueryFormatter;
use Maraful\QuerySummary\QuerySummary;
use Log;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $collector = new QuerySummary();
        $collector->setDataFormatter(new QueryFormatter());

        // explicitly disable render with params, as we want to group similar queries
        $collector->setRenderSqlWithParams(false);

        $db = $this->app['db'];

        try {
            $db->listen(
                function ($query) use ($db, $collector) {
                    if (!Debugbar::shouldCollect('db', true)) {
                        return; // We've turned off collecting after the listener was attached
                    }

                    $collector->addQuery(
                        (string) $query->sql,
                        $query->bindings,
                        $query->time,
                        $query->connection
                    );
                }
            );
        } catch (\Exception $e) {
            Log::info('Cannot add listen to Queries for Query-Summary: ' . $e->getMessage());
        }

        Debugbar::addCollector($collector);
    }
}
