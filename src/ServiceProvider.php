<?php
namespace Maraful\QuerySummary;

use Debugbar;

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
        Debugbar::addCollector(new QuerySummary());

    }
}
