<?php

namespace WeavingTheWeb\Bundle\TwitterBundle\Tests\Controller;

use Prophecy\Argument,
    Prophecy\Prophet;

use Symfony\Component\HttpFoundation\Response;

use WTW\CodeGeneration\QualityAssuranceBundle\Test\WebTestCase;

/**
 * @group tweet
 * @group controller_latest_tweets
 */
class TweetControllerTest extends WebTestCase
{
    /**
     * @var \Prophecy\Prophet $prophet
     */
    protected $prophet;

    public function setUp()
    {
        $this->prophet = new Prophet();
        $this->client = $this->getAuthenticatedClient(['follow_redirects' => true]);
    }

    public function tearDown()
    {
        $this->prophet->checkPredictions();
    }

    public function testLatestAction()
    {
        /** @var \Symfony\Component\Routing\Router $router */
        $router = $this->get('router');
        $latestTweetUrl = $router->generate('weaving_the_web_twitter_tweet_latest');

        $requestParameters = ['username' => 'user'];
        $this->client->request('GET', $latestTweetUrl, $requestParameters);

        $this->assertValidStatusContent();

        $this->assertValidOptionsResponse($latestTweetUrl);

        $serverErrorMessage = 'It should respond with a server error status';

        $this->client->request('GET', $latestTweetUrl);
        $response = $this->assertResponseStatusCodeEquals(500, $serverErrorMessage);

        /** @var \Symfony\Component\Translation\Translator $translator */
        $translator = $this->get('translator');
        $invalidOAuthTokenError = $translator->trans('twitter.error.invalid_oauth_token', [], 'messages');
        $decodeJson = $this->decodeJsonResponseContent($response);
        $this->assertArrayHasKey('error', $decodeJson);
        $this->assertEquals(
            $decodeJson['error'],
            $invalidOAuthTokenError,
            'It should inform the client about invalid OAuth token.'
        );

        $userRepositoryMock = $this->makeUserStreamRepositoryMock();
        static::$kernel->setKernelModifier(function (\AppKernel $kernel) use ($userRepositoryMock) {
            $kernel->getContainer()->set('weaving_the_web_twitter.repository.read.status', $userRepositoryMock);
        });

        $this->client->request('GET', $latestTweetUrl, $requestParameters);

        $response = $this->assertResponseStatusCodeEquals(500, $serverErrorMessage);

        $decodeJson = $this->decodeJsonResponseContent($response);
        $this->assertArrayHasKey('error', $decodeJson);

        $databaseConnectionError = $translator->trans('twitter.error.database_connection', [], 'messages');
        $this->assertEquals(
            $decodeJson['error'],
            $databaseConnectionError,
            'It should inform the client about a database connection error.'
        );
    }

    /**
     * @param $response
     * @return mixed
     */
    protected function decodeJsonResponseContent(Response $response)
    {
        $decodedJson = json_decode($response->getContent(), true);
        $this->assertEquals(JSON_ERROR_NONE, json_last_error(), 'The JSON response is not valid');

        return $decodedJson;
    }

    protected function assertValidStatusContent($message = '')
    {
        $response = $this->assertResponseStatusCodeEquals(200, $message);

        $decodedJson = $this->decodeJsonResponseContent($response);
        $this->assertArrayHasKey(0, $decodedJson, 'The decoded json array should contain one status');
        $this->assertEquals('194987972', $decodedJson[0]['status_id']);
    }

    /**
     * @param $latestTweetUrl
     */
    protected function assertValidOptionsResponse($latestTweetUrl)
    {
        $this->client->request('OPTIONS', $latestTweetUrl);
        $response = $this->assertResponseStatusCodeEquals(200);
        $this->assertEquals('*', $response->headers->get('Access-Control-Allow-Origin'));
    }

    /**
     * @return object
     */
    protected function makeUserStreamRepositoryMock()
    {
        /**
         * @var \WeavingTheWeb\Bundle\ApiBundle\Repository\StatusRepository $mock
         */
        $mock = $this->prophet->prophesize('\WeavingTheWeb\Bundle\ApiBundle\Repository\StatusRepository');
        $mock->findLatest(Argument::any())->willThrow(new \PDOException('No socket available.'));
        $mock->setOauthTokens(Argument::any())->willReturn(null);

        return $mock->reveal();
    }

    public function testBookmarksAction()
    {
        /** @var \Symfony\Component\Routing\Router $router */
        $router = $this->get('router');
        $latestTweetUrl = $router->generate('weaving_the_web_twitter_tweet_sync_bookmarks');

        $this->client->request('POST', $latestTweetUrl, ['statusIds' => [194987972], 'username' => 'user']);

        $this->assertValidStatusContent('When syncing bookmarks, the response should contain statuses');

        $this->assertValidOptionsResponse($latestTweetUrl);
    }

    /**
     * @dataProvider getStarringExpectations
     */
    public function testToggleStarringStatusAction($routeName, $expectedStarringStatus)
    {
        /** @var \Symfony\Component\Routing\Router $router */
        $router = $this->get('router');

        $statusId = 194987972;
        $actionUrl = $router->generate($routeName, ['statusId' => $statusId]);
        $this->client->request('OPTIONS', $actionUrl);
        $response = $this->assertResponseStatusCodeEquals(200);
        $this->assertEquals('*', $response->headers->get('Access-Control-Allow-Origin'));

        $this->client->request('POST', $actionUrl);
        $response = $this->assertResponseStatusCodeEquals(200);

        $decodedContent = $this->decodeJsonResponseContent($response);
        $this->assertArrayHasKey('status', $decodedContent);
        $this->assertEquals(
            $statusId,
            $decodedContent['status'],
            'It should return a json response containing the status id of a tweet which has been updated'
        );

        /** @var \WeavingTheWeb\Bundle\ApiBundle\Repository\StatusRepository $userStatusRepository */
        $userStatusRepository = $this->get('weaving_the_web_twitter.repository.read.status');
        /** @var \WeavingTheWeb\Bundle\ApiBundle\Entity\Status $userStatus */
        $userStatus = $userStatusRepository->findOneBy(['starred' => $expectedStarringStatus]);

        $this->assertNotNull($userStatus);
        $this->assertEquals($expectedStarringStatus, $userStatus->isStarred());
    }

    public function getStarringExpectations()
    {
        return [
            [
                'route_name' => 'weaving_the_web_twitter_tweet_star',
                'expected_starring_status' => true,
            ], [
                'route_name' => 'weaving_the_web_twitter_tweet_unstar',
                'expected_starring_status' => false,
            ]
        ];
    }
}
