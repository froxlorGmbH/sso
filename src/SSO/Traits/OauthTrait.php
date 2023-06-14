<?php

namespace FroxlorGmbH\SSO\Traits;

use FroxlorGmbH\SSO\Result;
use GuzzleHttp\Exception\RequestException;

/**
 * @author René Preuß <rene@preuss.io>
 */
trait OauthTrait
{

    /**
     * Retrieving a oauth token using a given grant type.
     *
     * @param string $grantType
     * @param array $attributes
     *
     * @return Result
     */
    public function retrievingToken(string $grantType, array $attributes): Result
    {
        try {
            $response = $this->client->request('POST', '/oauth/token', [
                'form_params' => $attributes + [
                        'grant_type' => $grantType,
                        'client_id' => $this->getClientId(),
                        'client_secret' => $this->getClientSecret(),
                    ],
            ]);

            $result = new Result($response, null);
        } catch (RequestException $exception) {
            $result = new Result($exception->getResponse(), $exception);
        }

        $result->sso = $this;

        return $result;
    }
}