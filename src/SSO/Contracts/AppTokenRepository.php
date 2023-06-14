<?php
namespace FroxlorGmbH\SSO\Contracts;

use FroxlorGmbH\SSO\Exceptions\RequestFreshAccessTokenException;

interface AppTokenRepository
{
    /**
     * @throws RequestFreshAccessTokenException
     *
     * @return string
     */
    public function getAccessToken(): string;
}