<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Middleware для сжатия HTTP ответов
 * 
 * Автоматически сжимает контент ответов с помощью Gzip для уменьшения трафика
 */
class CompressionMiddleware
{
    /**
     * Обработать входящий запрос и сжать ответ
     * @param Request $request HTTP запрос
     * @param Closure $next Следующий middleware в цепочке
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $acceptEncoding = $request->header('Accept-Encoding');
        
        if (strpos($acceptEncoding, 'gzip') !== false) {
            $content = $response->getContent();
            
            if (strlen($content) > 1024) {
                $compressed = gzencode($content, 6);
                
                if ($compressed !== false) {
                    $response->setContent($compressed);
                    $response->header('Content-Encoding', 'gzip');
                    $response->header('Vary', 'Accept-Encoding');
                }
            }
        }

        return $response;
    }
}

