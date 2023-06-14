<?php

namespace FroxlorGmbH\SSO\Exceptions;

use Exception;

/**
 * @author René Preuß <rene@preuss.io>
 */
class RequestRequiresParameter extends Exception
{
    public function __construct($message = 'Request requires parameters', $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}