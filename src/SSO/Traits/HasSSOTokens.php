<?php
namespace FroxlorGmbH\SSO\Traits;

use stdClass;

trait HasSSOTokens
{
    /**
     * The current access token for the authentication user.
     *
     * @var stdClass
     */
    protected $accessToken;

    /**
     * Get the current access token being used by the user.
     *
     * @return stdClass|null
     */
    public function ssoToken(): ?stdClass
    {
        return $this->accessToken;
    }

    /**
     * Determine if the current API token has a given scope.
     *
     * @param string $scope
     * @return bool
     */
    public function ssoTokenCan(string $scope): bool
    {
        $scopes = $this->accessToken ? $this->accessToken->scopes : [];

        return in_array('*', $scopes) || in_array($scope, $this->accessToken->scopes);
    }

    /**
     * Set the current access token for the user.
     *
     * @param stdClass $accessToken
     * @return $this
     */
    public function withBitinflowAccessToken(stdClass $accessToken): self
    {
        $this->accessToken = $accessToken;

        return $this;
    }
}