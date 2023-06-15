# Implementing Auth

This method should typically be called in the `boot` method of your `AuthServiceProvider` class:

```php
use FroxlorGmbH\SSO\SSO;
use FroxlorGmbH\SSO\Providers\SSOUserProvider;
use Illuminate\Http\Request;

/**
 * Register any authentication / authorization services.
 *
 * @return void
 */
public function boot()
{
    Auth::provider('sso-users', function ($app, array $config) {
        return new SSOUserProvider(
            $app->make(SSO::class),
            $app->make(Request::class),
            $config
        );
    });
}
```

reference the guard in the `guards` configuration of your `auth.php` configuration file:

```php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],

    'api' => [
        'driver' => 'sso',
        'provider' => 'sso-users',
    ],
],
```

reference the provider in the `providers` configuration of your `auth.php` configuration file:

```php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class,
    ],
    
    'sso-users' => [
        'driver' => 'sso-users',
        'model' => App\Models\User::class,
        'model_key' => 'sso_user_id',
        'fields' => ['name' => 'name', 'email' => 'email'],
        'access_token_field' => 'sso_access_token',
    ],
],
```
