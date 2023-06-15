<?php

namespace FroxlorGmbH\SSO\Providers;

use FroxlorGmbH\SSO\SSO;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class SSOUserProvider implements UserProvider
{
    private $sso;
    private $authIdentifier = null;
    private $accessTokenField = null;
    private $fields;
    private $model;
    private $request;

    public function __construct(
        SSO     $sso,
        Request $request,
        array   $config
    )
    {
        $this->request = $request;
        $this->model = $config['model'] ?? "\\App\\Models\\User";
        $this->fields = $config['fields'] ?? [];
        $this->authIdentifier = $config['model_key'] ?? null;
        $this->accessTokenField = $config['access_token_field'] ?? null;
        $this->sso = $sso;
    }

    /**
     * @param mixed $identifier
     * @return Builder|Model|object|null
     */
    public function retrieveById($identifier)
    {
        $model = $this->createModel();
        $token = $this->request->bearerToken();
        $authIdentifier = $this->authIdentifier ?: $model->getAuthIdentifierName();

        $user = $this->newModelQuery($model)
            ->where($authIdentifier, $identifier)
            ->first();

        // Return user when found
        if ($user) {
            // Update access token when updated
            if ($this->accessTokenField) {
                $user[$this->accessTokenField] = $token;

                if ($user->isDirty()) {
                    $user->save();
                }
            }

            return $user;
        }

        // Create new user
        $this->sso->setToken($token);
        $result = $this->sso->getAuthedUser();

        if (!$result->success()) {
            return null;
        }

        $ssoAttributes = Arr::only((array)$result->data(), array_keys($this->fields));
        $attributes = [];
        $attributes[$authIdentifier] = $result->data->id;

        foreach ($this->fields as $sso_field => $local_field) {
            $attributes[$local_field] = $ssoAttributes[$sso_field];
        }

        if ($this->accessTokenField) {
            $attributes[$this->accessTokenField] = $token;
        }

        return $this->newModelQuery($model)->create($attributes);
    }

    /**
     * Create a new instance of the model.
     *
     * @return Model
     */
    public function createModel(): Model
    {
        $class = '\\' . ltrim($this->model, '\\');

        return new $class;
    }

    /**
     * Get a new query builder for the model instance.
     *
     * @param Model|null $model
     * @return Builder
     */
    protected function newModelQuery(Model $model = null): Builder
    {
        return is_null($model)
            ? $this->createModel()->newQuery()
            : $model->newQuery();
    }

    public function retrieveByToken($identifier, $token)
    {
        return null;
    }

    public function updateRememberToken(Authenticatable $user, $token)
    {
        // void
    }

    public function retrieveByCredentials(array $credentials)
    {
        return null;
    }

    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        return false;
    }
}
