<?php

namespace FroxlorGmbH\SSO\Http\Middleware;


use FroxlorGmbH\SSO\Exceptions\MissingScopeException;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CheckForAnyScope
{
    /**
     * Handle the incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param mixed ...$scopes
     * @return Response
     *
     * @throws AuthenticationException|MissingScopeException
     */
    public function handle($request, $next, ...$scopes)
    {
        if (!$request->user() || !$request->user()->ssoToken()) {
            throw new AuthenticationException;
        }

        foreach ($scopes as $scope) {
            if ($request->user()->ssoTokenCan($scope)) {
                return $next($request);
            }
        }

        throw new MissingScopeException($scopes);
    }
}