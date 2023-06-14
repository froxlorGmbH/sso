<?php
namespace FroxlorGmbH\SSO;

use FroxlorGmbH\SSO\ApiOperations;
use FroxlorGmbH\SSO\Exceptions\RequestRequiresAuthenticationException;
use FroxlorGmbH\SSO\Exceptions\RequestRequiresClientIdException;
use FroxlorGmbH\SSO\Exceptions\RequestRequiresRedirectUriException;
use FroxlorGmbH\SSO\Helpers\Paginator;
use FroxlorGmbH\SSO\Traits;
use FroxlorGmbH\Support\Query;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * @author René Preuß <rene@preuss.io>
 */
class SSO
{
    use Traits\OauthTrait;
    use Traits\SshKeysTrait;
    use Traits\UsersTrait;

    use ApiOperations\Delete;
    use ApiOperations\Get;
    use ApiOperations\Post;
    use ApiOperations\Put;

    /**
     * The name for API token cookies.
     *
     * @var string
     */
    public static string $cookie = 'sso_token';

    /**
     * Indicates if SSO should ignore incoming CSRF tokens.
     */
    public static bool $ignoreCsrfToken = false;

    /**
     * Indicates if SSO should unserializes cookies.
     */
    public static bool $unserializesCookies = false;

    private static string $baseUrl = 'https://sso.froxlor.com/api/';

    /**
     * Guzzle is used to make http requests.
     */
    protected Client $client;

    /**
     * Paginator object.
     */
    protected Paginator $paginator;

    /**
     * SSO OAuth token.
     *
     */
    protected ?string $token = null;

    /**
     * SSO client id.
     *
     */
    protected ?string $clientId = null;

    /**
     * SSO client secret.
     */
    protected ?string $clientSecret = null;

    /**
     * SSO OAuth redirect url.
     */
    protected ?string $redirectUri = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if ($clientId = config('sso.client_id')) {
            $this->setClientId($clientId);
        }
        if ($clientSecret = config('sso.client_secret')) {
            $this->setClientSecret($clientSecret);
        }
        if ($redirectUri = config('sso.redirect_url')) {
            $this->setRedirectUri($redirectUri);
        }
        if ($redirectUri = config('sso.base_url')) {
            self::setBaseUrl($redirectUri);
        }
        $this->client = new Client([
            'base_uri' => self::$baseUrl,
        ]);
    }

    /**
     * @param string $baseUrl
     *
     * @internal only for internal and debug purposes.
     */
    public static function setBaseUrl(string $baseUrl): void
    {
        self::$baseUrl = $baseUrl;
    }

    /**
     * Get or set the name for API token cookies.
     *
     * @param string|null $cookie
     * @return string|static
     */
    public static function cookie(string $cookie = null)
    {
        if (is_null($cookie)) {
            return static::$cookie;
        }

        static::$cookie = $cookie;

        return new static;
    }

    /**
     * Set the current user for the application with the given scopes.
     *
     * @param Authenticatable|Traits\HasSSOTokens $user
     * @param array $scopes
     * @param string $guard
     * @return Authenticatable
     */
    public static function actingAs($user, $scopes = [], $guard = 'api')
    {
        $user->withSsoAccessToken((object)[
            'scopes' => $scopes
        ]);

        if (isset($user->wasRecentlyCreated) && $user->wasRecentlyCreated) {
            $user->wasRecentlyCreated = false;
        }

        app('auth')->guard($guard)->setUser($user);

        app('auth')->shouldUse($guard);

        return $user;
    }

    /**
     * Fluid client id setter.
     *
     * @param string $clientId SSO client id.
     *
     * @return self
     */
    public function withClientId(string $clientId): self
    {
        $this->setClientId($clientId);

        return $this;
    }

    /**
     * Get client secret.
     *
     * @return string
     * @throws RequestRequiresClientIdException
     */
    public function getClientSecret(): string
    {
        if (!$this->clientSecret) {
            throw new RequestRequiresClientIdException;
        }

        return $this->clientSecret;
    }

    /**
     * Set client secret.
     *
     * @param string $clientSecret SSO client secret
     *
     * @return void
     */
    public function setClientSecret(string $clientSecret): void
    {
        $this->clientSecret = $clientSecret;
    }

    /**
     * Fluid client secret setter.
     *
     * @param string $clientSecret SSO client secret
     *
     * @return self
     */
    public function withClientSecret(string $clientSecret): self
    {
        $this->setClientSecret($clientSecret);

        return $this;
    }

    /**
     * Get redirect url.
     *
     * @return string
     * @throws RequestRequiresRedirectUriException
     */
    public function getRedirectUri(): string
    {
        if (!$this->redirectUri) {
            throw new RequestRequiresRedirectUriException;
        }

        return $this->redirectUri;
    }

    /**
     * Set redirect url.
     *
     * @param string $redirectUri
     *
     * @return void
     */
    public function setRedirectUri(string $redirectUri): void
    {
        $this->redirectUri = $redirectUri;
    }

    /**
     * Fluid redirect url setter.
     *
     * @param string $redirectUri
     *
     * @return self
     */
    public function withRedirectUri(string $redirectUri): self
    {
        $this->setRedirectUri($redirectUri);

        return $this;
    }

    /**
     * Get OAuth token.
     *
     * @return string        SSO token
     * @return string|null
     * @throws RequestRequiresAuthenticationException
     */
    public function getToken(): ?string
    {
        if (!$this->token) {
            throw new RequestRequiresAuthenticationException;
        }

        return $this->token;
    }

    /**
     * Set OAuth token.
     *
     * @param string $token SSO OAuth token
     *
     * @return void
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * Fluid OAuth token setter.
     *
     * @param string $token SSO OAuth token
     *
     * @return self
     */
    public function withToken(string $token): self
    {
        $this->setToken($token);

        return $this;
    }

    /**
     * @param string $path
     * @param array $parameters
     * @param Paginator|null $paginator
     *
     * @return Result
     * @throws GuzzleException
     * @throws RequestRequiresClientIdException
     */
    public function get(string $path = '', array $parameters = [], Paginator $paginator = null): Result
    {
        return $this->query('GET', $path, $parameters, $paginator);
    }

    /**
     * Build query & execute.
     *
     * @param string $method       HTTP method
     * @param string $path         Query path
     * @param array $parameters    Query parameters
     * @param Paginator|null $paginator Paginator object
     * @param mixed|null $jsonBody JSON data
     *
     * @return Result     Result object
     * @throws GuzzleException
     * @throws RequestRequiresClientIdException
     */
    public function query(string $method = 'GET', string $path = '', array $parameters = [], Paginator $paginator = null, $jsonBody = null): Result
    {
        /** @noinspection DuplicatedCode */
        if ($paginator !== null) {
            $parameters[$paginator->action] = $paginator->cursor();
        }
        try {
            $response = $this->client->request($method, $path, [
                'headers' => $this->buildHeaders((bool)$jsonBody),
                'query' => Query::build($parameters),
                'json' => $jsonBody ?: null,
            ]);
            $result = new Result($response, null, $paginator);
        } catch (RequestException $exception) {
            $result = new Result($exception->getResponse(), $exception, $paginator);
        }
        $result->sso = $this;

        return $result;
    }

    /**
     * Build headers for request.
     *
     * @param bool $json Body is JSON
     *
     * @return array
     * @throws RequestRequiresClientIdException
     */
    private function buildHeaders(bool $json = false): array
    {
        $headers = [
            'Client-ID' => $this->getClientId(),
            'Accept' => 'application/json',
        ];
        if ($this->token) {
            $headers['Authorization'] = 'Bearer ' . $this->token;
        }
        if ($json) {
            $headers['Content-Type'] = 'application/json';
        }

        return $headers;
    }

    /**
     * Get client id.
     *
     * @return string
     * @throws RequestRequiresClientIdException
     */
    public function getClientId(): string
    {
        if (!$this->clientId) {
            throw new RequestRequiresClientIdException;
        }

        return $this->clientId;
    }

    /**
     * Set client id.
     *
     * @param string $clientId SSO client id
     *
     * @return void
     */
    public function setClientId(string $clientId): void
    {
        $this->clientId = $clientId;
    }

    /**
     * @param string $path
     * @param array $parameters
     * @param Paginator|null $paginator
     *
     * @return Result
     * @throws GuzzleException
     * @throws RequestRequiresClientIdException
     */
    public function post(string $path = '', array $parameters = [], Paginator $paginator = null): Result
    {
        return $this->query('POST', $path, $parameters, $paginator);
    }

    /**
     * @param string $path
     * @param array $parameters
     * @param Paginator|null $paginator
     *
     * @return Result
     * @throws GuzzleException
     * @throws RequestRequiresClientIdException
     */
    public function delete(string $path = '', array $parameters = [], Paginator $paginator = null): Result
    {
        return $this->query('DELETE', $path, $parameters, $paginator);
    }

    /**
     * @param string $path
     * @param array $parameters
     * @param Paginator|null $paginator
     *
     * @return Result
     * @throws GuzzleException
     * @throws RequestRequiresClientIdException
     */
    public function put(string $path = '', array $parameters = [], Paginator $paginator = null): Result
    {
        return $this->query('PUT', $path, $parameters, $paginator);
    }

    /**
     * @param string $method
     * @param string $path
     * @param array|null $body
     *
     * @return Result
     * @throws GuzzleException
     * @throws RequestRequiresClientIdException
     */
    public function json(string $method, string $path = '', array $body = null): Result
    {
        if ($body) {
            $body = json_encode(['data' => $body]);
        }

        return $this->query($method, $path, [], null, $body);
    }
}
