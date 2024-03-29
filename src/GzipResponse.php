<?php

namespace Puklipo\Vapor\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @license MIT
 * @copyright PCS<pcs.engineer.team@gmail.com>
 */
class GzipResponse
{
    /**
     * @var callable
     */
    protected static $encodeWhen;

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        /** @var Response|RedirectResponse $response */
        $response = $next($request);

        if ($this->shouldEncode($request, $response)) {
            $response->setContent(gzencode($response->content(), 9))
                ->withHeaders([
                    'Content-Encoding' => 'gzip',
                    'X-Vapor-Base64-Encode' => 'True',
                ]);
        }

        return $response;
    }

    /**
     * @param  Request  $request
     * @param  mixed|Response|BinaryFileResponse  $response
     * @return bool
     */
    protected function shouldEncode(Request $request, mixed $response): bool
    {
        if (static::$encodeWhen) {
            return call_user_func(static::$encodeWhen, $request, $response);
        }

        return in_array('gzip', $request->getEncodings())
            && $request->method() === 'GET'
            && function_exists('gzencode')
            && ! $response->headers->contains('Content-Encoding', 'gzip')
            && ! $response instanceof BinaryFileResponse;
    }

    /**
     * @param  callable(Request $request, mixed $response): bool  $encodeWhen
     * @return void
     */
    public static function encodeWhen(callable $encodeWhen): void
    {
        static::$encodeWhen = $encodeWhen;
    }
}
