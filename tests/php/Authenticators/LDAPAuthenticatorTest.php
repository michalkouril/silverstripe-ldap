<?php

namespace SilverStripe\LDAP\Tests\Authenticators;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\LDAP\Authenticators\LDAPAuthenticator;
use SilverStripe\LDAP\Model\LDAPGateway;
use SilverStripe\LDAP\Services\LDAPService;
use SilverStripe\LDAP\Tests\Model\LDAPFakeGateway;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Security\Member;

class LDAPAuthenticatorTest extends SapphireTest
{
    /**
     * @var LDAPAuthenticator
     */
    protected $authenticator;

    /**
     * @var HTTPRequest
     */
    private $request;

    /**
     * @var LDAPService
     */
    private $service;

    /**
     * @var ValidationResult
     */
    private $result;

    /**
     * @var array
     */
    private $data;

    protected static $fixture_file = 'LDAPAuthenticatorTest.yml';

    protected function setUp(): void
    {
        parent::setUp();

        $gateway = new LDAPFakeGateway();
        Injector::inst()->registerService($gateway, LDAPGateway::class);

        $service = Injector::inst()->get(LDAPService::class);
        $service->setGateway($gateway);

        $this->service = $service;

        $this->authenticator = Injector::inst()->create(LDAPAuthenticator::class);

        Config::modify()->set(LDAPAuthenticator::class, 'allow_email_login', 'yes');

        $this->request = new HTTPRequest('get', '/');
        $this->request->setSession(new Session([]));
        $this->result = new ValidationResult();
        $this->data = [
            'Login' => null,
            'Password' => null
        ];
    }

    public function testDisallowedEmailLogin()
    {
        Config::modify()->set(LDAPAuthenticator::class, 'allow_email_login', 'no');
        $this->data['Login'] = 'joe@soap.com';
        $this->data['Password'] = 'test';
        $this->callAuthMethod();
        $this->assertFalse($this->result->isValid());
    }

    /**
     * Tests whether a validator error results if User not found at gateway and no fallback member found
     */
    public function testEmailNotFoundAtGateWay()
    {
        $invalidGatewayAndLocalEmail = 'invalid@example.com';
        $this->data = ['Login' => $invalidGatewayAndLocalEmail, 'Password' => 'test'];
        $this->callAuthMethod();
        $this->assertFalse($this->result->isValid());
    }

    /**
     * Tests whether fallback authenticator returns a member if enabled
     */
    public function testFallbackAuthenticator()
    {
        Config::modify()->set(LDAPAuthenticator::class, 'fallback_authenticator', 'yes');
        $member = $this->objFromFixture(Member::class, 'dbOnlyMember');
        $this->data = ['Login' => $member->Email, 'Email' => $member->Email, 'Password' => 'password'];
        $result = $this->callAuthMethod();
        $this->assertInstanceOf(Member::class, $result);
        $this->assertEquals($member->Email, $result->Email);
    }

    /**
     * Tests for Invalid Credentials upon LDAP authentication failure
     */
    public function testLDAPAuthenticationFailure()
    {
        $this->data = ['Login' => 'usernotfound', 'Password' => 'passwordnotfound'];
        $this->callAuthMethod();
        $this->assertFalse($this->result->isValid());
        $this->assertStringContainsString('Username not found', $this->result->getMessages()[0]['message']);
    }

    /**
     * Tests whether a new member is created in SS if it was found in LDAP but doesn't
     * exist in SS
     */
    public function testAuthenticateCreatesNewMemberIfNotFound()
    {
        $this->data = ['Login' => 'joe@bloggs.com', 'Password' => 'mockPassword'];
        $member = $this->callAuthMethod();
        $this->assertTrue($this->result->isValid());
        $this->assertInstanceOf(Member::class, $member);
        $this->assertEquals(123, $member->GUID);
    }

    private function callAuthMethod()
    {
        $result = $this->authenticator->authenticate(
            $this->data,
            $this->request,
            $this->result
        );

        return $result;
    }
}
