<?php
namespace FroxlorGmbH\SSO\Http\Middleware;

use FroxlorGmbH\SSO\Exceptions\MissingScopeException;
use FroxlorGmbH\SSO\Helpers\JwtParser;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use stdClass;

abstract class CheckCredentials
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param mixed ...$scopes
     *
     * @return mixed
     * @throws AuthenticationException|MissingScopeException
     *
     */
    public function handle($request, Closure $next, ...$scopes)
    {
        $decoded = $this->getJwtParser()->decode($request);

        $request->attributes->set('oauth_access_token_id', $decoded->jti);
        $request->attributes->set('oauth_client_id', $decoded->aud);
        //$request->attributes->set('oauth_client_trusted', $decoded->client->trusted);
        $request->attributes->set('oauth_user_id', $decoded->sub);
        $request->attributes->set('oauth_scopes', $decoded->scopes);

        $this->validateScopes($decoded, $scopes);

        return $next($request);
    }

    private function getJwtParser(): JwtParser
    {
        return app(JwtParser::class);
    }

    abstract protected function validateScopes(stdClass $token, array $scopes);
}