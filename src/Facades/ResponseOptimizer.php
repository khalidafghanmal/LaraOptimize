<?php
namespace Khalid\ResponseOptimizer\Facades;

use Illuminate\Support\Facades\Facade;

class ResponseOptimizer extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'response-optimizer';
    }
}
