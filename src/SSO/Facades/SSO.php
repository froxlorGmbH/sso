<?php

namespace FroxlorGmbH\SSO\Facades;

use FroxlorGmbH\SSO\SSO as SSOService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string|static cookie(string $cookie = null)
 * @method static Authenticatable actingAs($user, $scopes = [], $guard = 'api')
 * @method static static withClientId(string $clientId): self
 * @method static string getClientSecret(): string
 */
class SSO extends Facade
{
    protected static function getFacadeAccessor()
    {
        return SSOService::class;
    }
}