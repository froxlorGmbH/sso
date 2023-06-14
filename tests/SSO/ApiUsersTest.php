<?php

namespace FroxlorGmbH\SSO\Tests;

use FroxlorGmbH\SSO\Enums\Scope;
use FroxlorGmbH\SSO\Tests\TestCases\ApiTestCase;
use Illuminate\Support\Str;

/**
 * @author René Preuß <rene@preuss.io>
 */
class ApiUsersTest extends ApiTestCase
{

    public function testGetAuthedUser(): void
    {
        $this->getClient()->withToken($this->getToken());
        $this->registerResult($result = $this->getClient()->getAuthedUser());
        $this->assertTrue($result->success());
        $this->assertEquals('unittest@froxlor.com', $result->data()->email);
    }

    public function testEmailAvailabilityNonExisting(): void
    {
        $this->getClient()->withToken($this->getToken());
        $this->registerResult($result = $this->getClient()->isEmailExisting('unittest+non-existing@froxlor.com'));
        $this->assertTrue(!$result->success());
    }

    public function testEmailAvailabilityExisting(): void
    {
        $this->getClient()->withToken($this->getToken());
        $this->registerResult($result = $this->getClient()->isEmailExisting('unittest@froxlor.com'));
        $this->assertTrue($result->success());
    }

    public function testCreateUser(): void
    {
        $testEmailAddress = $this->createRandomEmail();

        $this->registerResult($result = $this->getClient()->retrievingToken('client_credentials', [
            'scope' => Scope::USER,
        ]));

        $this->getClient()->withToken($result->data()->access_token);
        $this->registerResult($result = $this->getClient()->createUser([
            'first_name' => 'Unit',
            'last_name' => 'Test',
            'email' => $testEmailAddress,
            'password' => 'secret',
            'password_confirmation' => 'secret',
            'terms_accepted' => true,
        ]));
        $this->assertTrue($result->success(), $result->error());
        $this->assertEquals($testEmailAddress, $result->data()->email);
    }

    private function createRandomEmail(): string
    {
        return sprintf('unittest+%s@froxlor.com', Str::random());
    }
}
