<?php

namespace FroxlorGmbH\SSO\ApiOperations;

use FroxlorGmbH\SSO\Helpers\Paginator;
use FroxlorGmbH\SSO\Result;

/**
 * @author René Preuß <rene@preuss.io>
 */
trait Get
{

    abstract public function get(string $path = '', array $parameters = [], Paginator $paginator = null): Result;
}