<?php

namespace FroxlorGmbH\SSO\ApiOperations;

use FroxlorGmbH\SSO\Helpers\Paginator;
use FroxlorGmbH\SSO\Result;

/**
 * @author René Preuß <rene@preuss.io>
 */
trait Delete
{

    abstract public function delete(string $path = '', array $parameters = [], Paginator $paginator = null): Result;
}