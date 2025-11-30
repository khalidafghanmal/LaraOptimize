<?php
namespace Khalid\ResponseOptimizer\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Khalid\ResponseOptimizer\Optimizer\Optimizer;
use Illuminate\Support\Facades\Log;

class OptimizeResponse
{
    protected $config;

    public function __construct()
    {
        $this->config = config('response-optimizer', []);
    }

    public function handle(Request $request, Closure $next)
    {
        if (empty($this->config['enabled'])) return $next($request);

        foreach ($this->config['exclude_routes'] ?? [] as $pattern) {
            if ($request->is($pattern)) return $next($request);
        }

        $response = $next($request);
        if (! $response instanceof Response) return $response;

        $contentType = $response->headers->get('Content-Type', '');
        $body = $response->getContent();
        $contentLength = strlen($body);

        if (($this->config['max_process_size'] ?? 2097152) < $contentLength) {
            Log::channel($this->config['log_channel'] ?? 'stack')->warning('ResponseOptimizer skipped: too large', ['uri'=>$request->getRequestUri(),'size'=>$contentLength]);
            return $response;
        }

        try {
            $optimized = (new Optimizer($this->config))->optimize($body, $contentType);
            $response->setContent($optimized);

            if (!headers_sent() && ($this->config['gzip'] ?? true) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '', 'gzip') !== false) {
                $compressed = gzencode($response->getContent());
                $response->setContent($compressed);
                $response->headers->set('Content-Encoding','gzip');
                $response->headers->set('Content-Length', strlen($compressed));
            }

        } catch (\Throwable $e) {
            Log::channel($this->config['log_channel'] ?? 'stack')->error('ResponseOptimizer error: '.$e->getMessage(), ['uri'=>$request->getRequestUri()]);
        }

        return $response;
    }
}
