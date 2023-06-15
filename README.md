# froxlor GmbH SSO

PHP froxlor GmbH SSO API Client for Laravel 10+

## Table of contents

1. [Installation](#installation)
2. [Event Listener](#event-listener)
3. [Configuration](#configuration)
4. [Examples](#examples)
5. [Documentation](#documentation)
6. [Development](#Development)

## Installation

```
composer require froxlorgmbh/sso
```

**If you use Laravel 10+ you are already done, otherwise continue.**

Add Service Provider to your `app.php` configuration file:

```php
FroxlorGmbH\SSO\Providers\SSOServiceProvider::class,
```

## Event Listener

- Add `SocialiteProviders\Manager\SocialiteWasCalled` event to your `listen[]` array in `app/Providers/EventServiceProvider`.
- Add your listeners (i.e. the ones from the providers) to the `SocialiteProviders\Manager\SocialiteWasCalled[]` that you just created.
- The listener that you add for this provider is `'FroxlorGmbH\\SSO\\Socialite\\SSOExtendSocialite@handle',`.
- Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.


```
/**
 * The event handler mappings for the application.
 *
 * @var array
 */
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // add your listeners (aka providers) here
        'FroxlorGmbH\\SSO\\Socialite\\SSOExtendSocialite@handle',
    ],
];
```

## Configuration

Copy configuration to config folder:

```
$ php artisan vendor:publish --provider="FroxlorGmbH\SSO\Providers\SSOServiceProvider"
```

Add environmental variables to your `.env`

```
SSO_KEY=
SSO_SECRET=
SSO_REDIRECT_URI=http://localhost
```

You will need to add an entry to the services configuration file so that after config files are cached for usage in production environment (Laravel command `artisan config:cache`) all config is still available.

**Add to `config/services.php`:**

```php
'sso' => [
    'client_id' => env('SSO_KEY'),
    'client_secret' => env('SSO_SECRET'),
    'redirect' => env('SSO_REDIRECT_URI')
],
```

## Examples

#### Basic

```php
$sso = new FroxlorGmbH\SSO\SSO();

$sso->setClientId('abc123');

// Get SSH Key by User ID
$result = $sso->getSshKeysByUserId(38);

// Check, if the query was successfull
if ( ! $result->success()) {
    die('Ooops: ' . $result->error());
}

// Shift result to get single key data
$sshKey = $result->shift();

echo $sshKey->name;
```

#### Setters

```php
$sso = new FroxlorGmbH\SSO\SSO();

$sso->setClientId('abc123');
$sso->setClientSecret('abc456');
$sso->setToken('abcdef123456');

$sso = $sso->withClientId('abc123');
$sso = $sso->withClientSecret('abc123');
$sso = $sso->withToken('abcdef123456');
```

#### OAuth Tokens

```php
$sso = new FroxlorGmbH\SSO\SSO();

$sso->setClientId('abc123');
$sso->setToken('abcdef123456');

$result = $sso->getAuthedUser();

$user = $userResult->shift();
```

```php
$sso->setToken('uvwxyz456789');

$result = $sso->getAuthedUser();
```

```php
$result = $sso->withToken('uvwxyz456789')->getAuthedUser();
```

#### Facade

```php
use FroxlorGmbH\SSO\Facades\SSO;

SSO::withClientId('abc123')->withToken('abcdef123456')->getAuthedUser();
```

## Documentation

### Oauth

```php
public function retrievingToken(string $grantType, array $attributes)
```

### PaymentIntents

```php
public function getPaymentIntent(string $id)
public function createPaymentIntent(array $parameters)
```

### SshKeys

```php
public function getSshKeysByUserId(int $id)
public function createSshKey(string $publicKey, string $name = NULL)
public function deleteSshKey(int $id)
```

### Users

```php
public function getAuthedUser()
public function createUser(array $parameters)
```

[**OAuth Scopes Enums**](https://github.com/froxlorGmbH/sso/blob/main/src/Enums/Scope.php)

## Development

#### Run Tests

```shell
composer test
```

```shell
BASE_URL=xxxx CLIENT_ID=xxxx CLIENT_KEY=yyyy CLIENT_ACCESS_TOKEN=zzzz composer test
```

#### Generate Documentation

```shell
composer docs
```
