<?php
namespace Khalid\ResponseOptimizer;

use Illuminate\Support\ServiceProvider;
use Khalid\ResponseOptimizer\Middleware\OptimizeResponse;

class ResponseOptimizerServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('response-optimizer', function($app){
    $config = $app['config']->get('response-optimizer', []); 
    return new Optimizer\Optimizer($config);
});

    }

    public function boot()
    {
        $this->publishes([__DIR__.'/../config/response-optimizer.php' => config_path('response-optimizer.php')], 'config');
        $this->app->make(\Illuminate\Contracts\Http\Kernel::class)->pushMiddleware(OptimizeResponse::class);
    }
}
