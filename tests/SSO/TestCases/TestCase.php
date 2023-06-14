<?php

namespace FroxlorGmbH\SSO\Tests\TestCases;

use FroxlorGmbH\SSO\SSO;
use FroxlorGmbH\SSO\Providers\SSOServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

/**
 * @author René Preuß <rene@preuss.io>
 */
abstract class TestCase extends BaseTestCase
{

    protected function getPackageProviders($app)
    {
        return [
            SSOServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'SSO' => SSO::class,
        ];
    }
}