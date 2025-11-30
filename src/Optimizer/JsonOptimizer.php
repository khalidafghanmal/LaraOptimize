<?php
namespace Khalid\ResponseOptimizer\Optimizer;

class JsonOptimizer
{
    protected $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function optimize(string $json): string
    {
        return json_encode(json_decode($json, true));
    }
}
