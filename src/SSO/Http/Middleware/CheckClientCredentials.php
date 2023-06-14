<?php
namespace FroxlorGmbH\SSO\Http\Middleware;

use FroxlorGmbH\SSO\Exceptions\MissingScopeException;
use stdClass;

class CheckClientCredentials extends CheckCredentials
{
    /**
     * Validate token credentials.
     *
     * @param stdClass $token
     * @param array $scopes
     *
     * @return void
     * @throws MissingScopeException
     *
     */
    protected function validateScopes(stdClass $token, array $scopes)
    {
        if (in_array('*', $token->scopes)) {
            return;
        }

        foreach ($scopes as $scope) {
            if (!in_array($scope, $token->scopes)) {
                throw new MissingScopeException($scopes);
            }
        }
    }
}