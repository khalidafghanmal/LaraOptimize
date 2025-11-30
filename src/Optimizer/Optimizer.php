<?php
namespace Khalid\ResponseOptimizer\Optimizer;

class Optimizer
{
    protected $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function optimize(string $content, string $type): string
    {
        $start = microtime(true);

        if (stripos($type, 'application/json') !== false) {
            // Ultra-fast JSON minification: remove all whitespace outside strings
            $content = $this->minifyJson($content);
            return $content;
        }

        if (stripos($type, 'text/html') !== false) {

            // 1️⃣ Preserve sensitive tags
            $placeholders = [];
            $content = preg_replace_callback('/<(pre|code|textarea)(.*?)>.*?<\/\1>/si', function ($m) use (&$placeholders) {
                $key = '%%PLACEHOLDER_' . count($placeholders) . '%%';
                $placeholders[$key] = $m[0];
                return $key;
            }, $content);

            // 2️⃣ Remove comments except IE conditional
            $content = preg_replace('/<!--(?!\[if).*?-->/s', '', $content);

            // 3️⃣ Remove extra whitespace between tags & multiple spaces
            $content = preg_replace(['/>\s+</s', '/\s{2,}/'], ['><', ' '], $content);

            // 4️⃣ Minify inline JS
            $content = preg_replace_callback('/<script\b[^>]*>(.*?)<\/script>/is', function ($m) {
                $js = $m[1];
                $js = preg_replace('/\/\/[^\n]*|\/\*.*?\*\//s', '', $js);
                $js = preg_replace('/\s{2,}/', ' ', $js);
                return str_replace($m[1], $js, $m[0]);
            }, $content);

            // 5️⃣ Minify inline CSS
            $content = preg_replace_callback('/<style\b[^>]*>(.*?)<\/style>/is', function ($m) {
                $css = $m[1];
                $css = preg_replace('/\/\*.*?\*\//s', '', $css);
                $css = preg_replace('/\s{2,}/', ' ', $css);
                $css = preg_replace('/\s*([{}:;,])\s*/', '$1', $css);
                return str_replace($m[1], $css, $m[0]);
            }, $content);

            // 6️⃣ Smart defer JS
            if (!empty($this->config['defer_js'])) {
                $content = preg_replace('/<script(?!.*\bdefer\b)(.*?)src=/i', '<script$1 defer src=', $content);
            }

            // 7️⃣ Restore placeholders
            foreach ($placeholders as $key => $original) {
                $content = str_replace($key, $original, $content);
            }

            $content = trim($content);

            return $content;
        }

        return $content;
    }

    /**
     * Minify JSON without decoding everything (fast for huge JSON)
     */
    protected function minifyJson(string $json): string
    {
        // Remove whitespace outside quotes
        $inQuotes = false;
        $escaped = false;
        $result = '';
        $length = strlen($json);

        for ($i = 0; $i < $length; $i++) {
            $char = $json[$i];

            if ($char === '"' && !$escaped) $inQuotes = !$inQuotes;
            if (!$inQuotes && preg_match('/\s/', $char)) continue;

            $result .= $char;
            $escaped = ($char === '\\' && !$escaped);
        }

        return $result;
    }
}
