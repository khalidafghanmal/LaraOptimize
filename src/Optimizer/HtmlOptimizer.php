<?php
namespace Khalid\ResponseOptimizer\Optimizer;

class HtmlOptimizer
{
    protected $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function optimize(string $html): string
    {
        $start = microtime(true);

        $placeholders = [];
        $html = preg_replace_callback('/<(pre|code|textarea)(.*?)>.*?<\/\1>/si', function($match) use (&$placeholders) {
            $key = '%%PLACEHOLDER_' . count($placeholders) . '%%';
            $placeholders[$key] = $match[0];
            return $key;
        }, $html);

        $html = preg_replace('/<!--(?!\[if).*?-->/s', '', $html);

        $html = preg_replace('/>\s+</s', '><', $html);
        $html = preg_replace('/\s{2,}/', ' ', $html);

        $html = preg_replace_callback('/<script\b[^>]*>(.*?)<\/script>/is', function($m) {
            $js = $m[1];
            $js = preg_replace('/\/\/[^\n]*|\/\*.*?\*\//s', '', $js);
            $js = preg_replace('/\s{2,}/', ' ', $js);
            return str_replace($m[1], $js, $m[0]);
        }, $html);

        $html = preg_replace_callback('/<style\b[^>]*>(.*?)<\/style>/is', function($m) {
            $css = $m[1];
            $css = preg_replace('/\/\*.*?\*\//s', '', $css);
            $css = preg_replace('/\s{2,}/', ' ', $css);
            $css = preg_replace('/\s*([{}:;,])\s*/', '$1', $css);
            return str_replace($m[1], $css, $m[0]);
        }, $html);

        if (!empty($this->config['defer_js'])) {
            $html = preg_replace('/<script(?!.*\bdefer\b)(.*?)src=/i', '<script$1 defer src=', $html);
        }

        if (!empty($this->config['inline_critical_css'])) {
            $html = $this->inlineCriticalCSS($html);
        }

        foreach ($placeholders as $key => $original) {
            $html = str_replace($key, $original, $html);
        }

        $html = trim($html);

        if (!empty($this->config['benchmark'])) {
            $end = microtime(true);
            $original_size = strlen($html);
            $this->config['benchmark_result'] = [
                'optimized_length' => $original_size,
                'time_seconds' => $end - $start
            ];
        }

        return $html;
    }

   
    protected function inlineCriticalCSS(string $html): string
    {
        return preg_replace_callback('/<link rel="stylesheet" href="(.*?)"[^>]*>/i', function($m) {
            $path = public_path($m[1]);
            if (file_exists($path) && filesize($path) < 5120) { 
                $css = file_get_contents($path);
                $css = preg_replace('/\/\*.*?\*\//s', '', $css); 
                $css = preg_replace('/\s{2,}/', ' ', $css);
                return "<style>$css</style>";
            }
            return $m[0];
        }, $html);
    }
}
