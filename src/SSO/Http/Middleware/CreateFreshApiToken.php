<?php
namespace FroxlorGmbH\SSO\Http\Middleware;

use FroxlorGmbH\SSO\ApiTokenCookieFactory;
use FroxlorGmbH\SSO\SSO;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CreateFreshApiToken
{
    /**
     * The API token cookie factory instance.
     *
     * @var ApiTokenCookieFactory
     */
    protected $cookieFactory;

    /**
     * The authentication guard.
     *
     * @var string
     */
    protected $guard;

    /**
     * Create a new middleware instance.
     *
     * @param ApiTokenCookieFactory $cookieFactory
     * @return void
     */
    public function __construct(ApiTokenCookieFactory $cookieFactory)
    {
        $this->cookieFactory = $cookieFactory;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string|null $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $this->guard = $guard;

        $response = $next($request);

        if ($this->shouldReceiveFreshToken($request, $response)) {
            $response->withCookie($this->cookieFactory->make(
                $request->user($this->guard)->getAuthIdentifier(), $request->session()->token()
            ));
        }

        return $response;
    }

    /**
     * Determine if the given request should receive a fresh token.
     *
     * @param Request $request
     * @param Response $response
     * @return bool
     */
    protected function shouldReceiveFreshToken($request, $response): bool
    {
        return $this->requestShouldReceiveFreshToken($request) &&
            $this->responseShouldReceiveFreshToken($response);
    }

    /**
     * Determine if the request should receive a fresh token.
     *
     * @param Request $request
     * @return bool
     */
    protected function requestShouldReceiveFreshToken($request)
    {
        return $request->isMethod('GET') && $request->user($this->guard);
    }

    /**
     * Determine if the response should receive a fresh token.
     *
     * @param Response $response
     * @return bool
     */
    protected function responseShouldReceiveFreshToken($response)
    {
        return ($response instanceof Response ||
                $response instanceof JsonResponse) &&
            !$this->alreadyContainsToken($response);
    }

    /**
     * Determine if the given response already contains an API token.
     *
     * This avoids us overwriting a just "refreshed" token.
     *
     * @param Response $response
     * @return bool
     */
    protected function alreadyContainsToken($response): bool
    {
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === SSO::cookie()) {
                return true;
            }
        }

        return false;
    }
}