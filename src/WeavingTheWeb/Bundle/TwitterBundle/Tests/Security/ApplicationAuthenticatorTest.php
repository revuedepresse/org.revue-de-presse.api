<?php

namespace WeavingTheWeb\Bundle\TwitterBundle\Tests\Security;

use WTW\CodeGeneration\QualityAssuranceBundle\Test\WebTestCase;

/**
 * @package WeavingTheWeb\Bundle\TwitterBundle\Tests\Security
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 *
 * @group requires-internet
 * @group cli-twitter
 * @group messaging-twitter
 * @group twitter
 */
class ApplicationAuthenticatorTest extends WebTestCase
{
    /**
     * @var \WeavingTheWeb\Bundle\TwitterBundle\Security\ApplicationAuthenticator;
     */
    protected $authenticator;

    public function setUp()
    {
        $this->client = $this->getClient();
        $this->authenticator = $this->get('weaving_the_web_twitter.application_authenticator');
    }

    public function testBase64EncodeConsumerTokens()
    {
        $base64EncodedTokens = $this->authenticator->makeAuthorizationBasic('xvz1evFS4wEEPTGEFPHBog',
            'L8qq9PZyRg6ieKGEKhZolGC0vJWLw8iEJ88DRdyOg');

        $this->assertEquals('eHZ6MWV2RlM0d0VFUFRHRUZQSEJvZzpMOHFxOVBaeVJnNmllS0dFS2hab2xHQzB2SldMdzhpRUo4OERSZHlPZw==',
            $base64EncodedTokens);
    }

    /**
     * @group requires-internet
     * @group messaging-twitter
     * @group twitter
     */
    public function testPostOauth2Token()
    {
        $consumerKey = $this->getParameter('weaving_the_web_twitter.consumer_key');
        $consumerSecret = $this->getParameter('weaving_the_web_twitter.consumer_secret');

        $basic = $this->authenticator->makeAuthorizationBasic($consumerKey, $consumerSecret);

        $response = $this->authenticator->postOauth2Token($basic);

        $this->assertArrayHasKey('token_type', $response);
        $this->assertArrayHasKey('access_token', $response);
    }

    /**
     * @group requires-internet
     * @group messaging-twitter
     * @group twitter
     */
    public function testAuthenticate()
    {
        $accessToken = $this->authenticator->authenticate();
        $this->assertInternalType('array', $accessToken);
        $this->assertArrayHasKey('access_token', $accessToken);
        $this->assertArrayHasKey('consumer_key', $accessToken);
    }
}