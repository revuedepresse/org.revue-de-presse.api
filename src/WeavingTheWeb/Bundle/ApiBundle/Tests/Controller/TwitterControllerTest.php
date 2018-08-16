<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Tests\Controller;

use WTW\CodeGeneration\QualityAssuranceBundle\Test\WebTestCase;

class TwitterControllerTest extends WebTestCase
{
    /**
     * @group data-mining-twitter
     * @group isolated-testing
     */
    public function testGetUsersStreamsAction()
    {
        $this->client = $this->getAuthenticatedClient();

        $url = $this->get('router')->generate('weaving_the_web_api_get_users_streams');
        $this->client->request('GET', $url);

        $this->assertResponseStatusCodeEquals(200);

        $content = $this->client->getResponse()->getContent();
        $decodedContent = json_decode($content, true);

        $this->assertEquals(JSON_ERROR_NONE, json_last_error());
        $this->assertInternalType('array', $decodedContent);
        $this->assertInternalType('array', $decodedContent['data']);
        $this->assertCount(1, $decodedContent['data']);
        $this->assertArrayHasKey('hash', $decodedContent['data'][0]);
    }
}
