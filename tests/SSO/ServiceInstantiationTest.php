<?php

namespace FroxlorGmbH\SSO\Tests;

use FroxlorGmbH\SSO\SSO;
use FroxlorGmbH\SSO\Facades\SSO as SSOFacade;
use FroxlorGmbH\SSO\Tests\TestCases\TestCase;

/**
 * @author René Preuß <rene@preuss.io>
 */
class ServiceInstantiationTest extends TestCase
{

    public function testInstance(): void
    {
        $this->assertInstanceOf(SSO::class, app(SSO::class));
    }

    public function testFacade(): void
    {
        $this->assertInstanceOf(SSO::class, SSOFacade::getFacadeRoot());
    }
}