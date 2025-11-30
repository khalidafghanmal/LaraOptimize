<?php
return [
    'enabled' => true,
    'allowed_content_types' => ['text/html','application/json','application/vnd.api+json'],
    'max_process_size' => 2097152,
    'gzip' => true,
    'defer_js' => true,
    'exclude_routes' => ['debugbar*','horizon*','api*'],
    'log_channel' => 'stack',
];
