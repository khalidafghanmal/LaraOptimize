<?php
namespace Khalid\ResponseOptimizer\Optimizer;

class AssetOptimizer
{
    protected $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function optimize(string $content, string $type): string
    {
        return $content;
    }
}
