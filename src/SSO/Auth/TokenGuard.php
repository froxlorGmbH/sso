<?php

namespace FroxlorGmbH\SSO\Auth;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use FroxlorGmbH\SSO\Helpers\JwtParser;
use FroxlorGmbH\SSO\SSO;
use FroxlorGmbH\SSO\Traits\HasSSOTokens;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use stdClass;
use Throwable;

class TokenGuard
{
    use GuardHelpers;

    /**
     * @var Encrypter
     */
    private $encrypter;

    /**
     * @var JwtParser
     */
    private $jwtParser;

    public function __construct(UserProvider $provider, Encrypter $encrypter, JwtParser $jwtParser)
    {
        $this->provider = $provider;
        $this->encrypter = $encrypter;
        $this->jwtParser = $jwtParser;
    }

    /**
     * Get the user for the incoming request.
     *
     * @param Request $request
     * @return mixed
     * @throws BindingResolutionException
     * @throws Throwable
     */
    public function user(Request $request): ?Authenticatable
    {
        if ($request->bearerToken()) {
            return $this->authenticateViaBearerToken($request);
        } elseif ($request->cookie(SSO::cookie())) {
            return $this->authenticateViaCookie($request);
        }

        return null;
    }

    /**
     * Authenticate the incoming request via the Bearer token.
     *
     * @param Request $request
     * @return Authenticatable
     * @throws BindingResolutionException
     * @throws Throwable
     */
    protected function authenticateViaBearerToken(Request $request): ?Authenticatable
    {
        if (!$token = $this->validateRequestViaBearerToken($request)) {
            return null;
        }

        // If the access token is valid we will retrieve the user according to the user ID
        // associated with the token. We will use the provider implementation which may
        // be used to retrieve users from Eloquent. Next, we'll be ready to continue.
        /** @var Authenticatable|HasSSOTokens $user */
        $user = $this->provider->retrieveById(
            $request->attributes->get('oauth_user_id') ?: null
        );

        if (!$user) {
            return null;
        }

        return $token ? $user->withBitinflowAccessToken($token) : null;
    }

    /**
     * Authenticate and get the incoming request via the Bearer token.
     *
     * @param Request $request
     * @return stdClass|null
     * @throws BindingResolutionException
     * @throws Throwable
     */
    protected function validateRequestViaBearerToken(Request $request): ?stdClass
    {
        try {
            $decoded = $this->jwtParser->decode($request);

            $request->attributes->set('oauth_access_token_id', $decoded->jti);
            $request->attributes->set('oauth_client_id', $decoded->aud);
            $request->attributes->set('oauth_client_trusted', $decoded->client->trusted);
            $request->attributes->set('oauth_user_id', $decoded->sub);
            $request->attributes->set('oauth_scopes', $decoded->scopes);

            return $decoded;
        } catch (AuthenticationException $e) {
            $request->headers->set('Authorization', '', true);

            Container::getInstance()->make(
                ExceptionHandler::class
            )->report($e);

            return null;
        }
    }

    /**
     * Authenticate the incoming request via the token cookie.
     *
     * @param Request $request
     * @return mixed
     */
    protected function authenticateViaCookie(Request $request)
    {
        if (!$token = $this->getTokenViaCookie($request)) {
            return null;
        }

        // If this user exists, we will return this user and attach a "transient" token to
        // the user model. The transient token assumes it has all scopes since the user
        // is physically logged into the application via the application's interface.
        /** @var Authenticatable|HasSSOTokens $user */
        if ($user = $this->provider->retrieveById($token['sub'])) {
            return $user->withBitinflowAccessToken((object)['scopes' => ['*']]);
        }

        return null;
    }

    /**
     * Get the token cookie via the incoming request.
     *
     * @param Request $request
     * @return array|null
     */
    protected function getTokenViaCookie(Request $request): ?array
    {
        // If we need to retrieve the token from the cookie, it'll be encrypted so we must
        // first decrypt the cookie and then attempt to find the token value within the
        // database. If we can't decrypt the value we'll bail out with a null return.
        try {
            $token = $this->decodeJwtTokenCookie($request);
        } catch (Exception $e) {
            return null;
        }

        // We will compare the CSRF token in the decoded API token against the CSRF header
        // sent with the request. If they don't match then this request isn't sent from
        // a valid source and we won't authenticate the request for further handling.
        if (!SSO::$ignoreCsrfToken && (!$this->validCsrf($token, $request) ||
                time() >= $token['expiry'])) {
            return null;
        }

        return $token;
    }

    /**
     * Decode and decrypt the JWT token cookie.
     *
     * @param Request $request
     * @return array
     */
    protected function decodeJwtTokenCookie(Request $request): array
    {
        return (array)JWT::decode(
            CookieValuePrefix::remove($this->encrypter->decrypt($request->cookie(SSO::cookie()), SSO::$unserializesCookies)),
            new Key(
                $this->encrypter->getKey(),
                'HS256'
            )
        );
    }

    /**
     * Determine if the CSRF / header are valid and match.
     *
     * @param array $token
     * @param Request $request
     * @return bool
     */
    protected function validCsrf(array $token, Request $request): bool
    {
        return isset($token['csrf']) && hash_equals(
                $token['csrf'], (string)$this->getTokenFromRequest($request)
            );
    }

    /**
     * Get the CSRF token from the request.
     *
     * @param Request $request
     * @return string
     */
    protected function getTokenFromRequest(Request $request): string
    {
        $token = $request->header('X-CSRF-TOKEN');

        if (!$token && $header = $request->header('X-XSRF-TOKEN')) {
            $token = CookieValuePrefix::remove($this->encrypter->decrypt($header, static::serialized()));
        }

        return $token;
    }

    /**
     * Determine if the cookie contents should be serialized.
     *
     * @return bool
     */
    public static function serialized(): bool
    {
        return EncryptCookies::serialized('XSRF-TOKEN');
    }
}