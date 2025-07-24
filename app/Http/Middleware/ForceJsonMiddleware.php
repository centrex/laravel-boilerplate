<?php

declare(strict_types = 1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Routing\ResponseFactory;

class ForceJsonMiddleware
{
    public function __construct(protected ResponseFactory $responseFactory) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $request->headers->set('Accept', 'application/json');

        $response = $next($request);

        // Convert not json responses to json
        if (!$response instanceof JsonResponse) {
            return $this->responseFactory->json(
                $response->content(),
                $response->status(),
                $response->headers->all(),
            );
        }

        return $response;
    }
}
