<?php

namespace App\Tests\Api;

use GuzzleHttp\Exception\ConnectException;
use Prophecy\Argument;
use Prophecy\Prophet;

use App\Twitter\Api\Accessor;
use WeavingTheWeb\Bundle\TwitterBundle\Api\TwitterErrorAwareInterface;

use WeavingTheWeb\Bundle\TwitterBundle\Exception\UnavailableResourceException;
use WeavingTheWeb\Bundle\TwitterBundle\Exception\EmptyErrorCodeException;

use WTW\CodeGeneration\QualityAssuranceBundle\Test\WebTestCase;

/**
 * @group twitter-accessor
 */
class AccessorTest extends WebTestCase
{
    /**
     * @var $accessor \App\Twitter\Api\Accessor
     */
    protected $accessor;

    protected $targetDirectory;

    protected $testUser;

    /**
     * @var \Prophecy\Prophet $prophet
     */
    protected $prophet;

    public static function setUpBeforeClass()
    {
        self::setOption('environment', 'test');

        parent::setUpBeforeClass();
    }

    public function setUp()
    {
        parent::setUp();

        $this->client = $this->getClient();

        $this->accessor = $this->get('weaving_the_web_twitter.api_accessor');
        $this->testUser = 'MathieuCoste';
        $this->prophet = new Prophet();
    }

    public function tearDown()
    {
        $this->prophet->checkPredictions();

        parent::tearDown();
    }

    /**
     * @test
     * @group requires-internet
     * @group messaging-twitter
     * @group twitter
     * @group it_should_fetch_a_timeline
     */
    public function it_should_fetch_a_timeline()
    {
        $items = $this->accessor->fetchStatuses([
            'screen_name' => $this->testUser,
            'count' => 15,
            'page' => 1
        ]);
        $this->assertCount(15, $items);
    }

    /**
     * @group requires-internet
     * @group messaging-twitter
     * @group twitter
     */
    public function testFetchRateLimitStatus()
    {
        $rateLimitStatus = $this->accessor->fetchRateLimitStatus();
        $this->assertInternalType('object', $rateLimitStatus);
        $this->assertGreaterThan(0, get_object_vars($rateLimitStatus->resources->statuses));
    }

    /**
     * @group requires-internet
     * @group messaging-twitter
     * @group twitter
     */
    public function testShowUser()
    {
        $tokenRepositoryMock = $this->mockOauthTokenCheck();
        $this->accessor->setTokenRepository($tokenRepositoryMock);

        $user = $this->accessor->showUser($this->testUser);
        $this->assertInternalType('object', $user);
        $this->assertEquals($this->testUser, $user->screen_name);

        $user = $this->accessor->showUser(23566182);
        $this->assertInternalType('object', $user);
        $this->assertEquals(23566182, $user->id);
    }

    /**
     * @group requires-internet
     * @group messaging-twitter
     * @group twitter
     */
    public function testShowStatus()
    {
        $status = $this->accessor->showStatus(194987972);
        $this->assertInternalType('object', $status);
        $this->assertEquals(194987972, $status->id);
    }

    /**
     * @group requires-internet
     * @group messaging-twitter
     * @group twitter
     */
    public function testShowUserFriends()
    {
        $friends = $this->accessor->showUserFriends($this->testUser);
        $this->assertInternalType('object', $friends);
        $this->assertGreaterThanOrEqual(2349, $friends->ids);
    }

    /**
     * @group requires-internet
     * @group messaging-twitter
     * @group twitter-lists
     *
     * @return mixed
     */
    public function testGetUserLists()
    {
        $lists = $this->accessor->getUserLists($this->testUser);
        $this->assertInternalType('array', $lists);
        $this->assertGreaterThanOrEqual(5, $lists);

        return array_pop($lists);
    }

    /**
     * @group requires-internet
     * @group messaging-twitter
     * @group twitter-lists
     * @depends testGetUserLists
     *
     * @param object $list
     */
    public function testGetListMembers($list)
    {
        $tokenRepositoryMock = $this->mockOauthTokenCheck();
        $this->accessor->setTokenRepository($tokenRepositoryMock);

        $members = $this->accessor->getListMembers($list->id);
        $this->assertInternalType('array', $members->users);
        $this->assertGreaterThanOrEqual(1, $members->users);
    }

    /**
     * @group requires-internet
     * @group messaging-twitter
     * @group twitter-lists
     * @group twitter-api-error
     */
    public function testTwitterErrorException() 
    {
        $this->accessor->httpClient = $this->mockHttpClient();
        $this->accessor->setAuthenticationHeader('X-Auth');

        try {
            $this->accessor->contactEndpoint('/');
        } catch (ConnectException $exception) {
            $this->assertInstanceOf(ConnectException::class, $exception);

            return;
        }

        $this->fail('It should raise an exception');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockHttpClient()
    {
        $httpClientMock = $this->prophet->prophesize('\Goutte\Client');
        $httpClientMock->setHeader(Argument::cetera())->willReturn(null);
        $httpClientMock->request(Argument::cetera())->willReturn(null);
        $httpClientMock->setClient(Argument::any())->willReturn(null);

        $httpClientMock->getResponse()->willReturn($this->mockResponse());

        return $httpClientMock->reveal();
    }

    /**
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    protected function mockResponse()
    {
        $responseMock = $this->prophet->prophesize('\Symfony\Component\HttpFoundation\Response');
        $mockedContent = json_encode((object) [
            'errors' => [
            [
                'code' => TwitterErrorAwareInterface::ERROR_SUSPENDED_USER,
                'message' => 'User has been suspended',
            ],
            ],
        ]);
        $responseMock->getContent()->willReturn($mockedContent);

        return $responseMock->reveal();
    }

    /**
     * @test
     */
    public function it_should_make_content_out_of_empty_error_code_exception()
    {
        $message = 'Response with empty error code.';
        $code = 0;
        $exceededRateLimitErrorCode = $this->accessor->getExceededRateLimitErrorCode();

        $emptyErrorCodeException = EmptyErrorCodeException::encounteredWhenUsingToken(
            $message,
            $exceededRateLimitErrorCode
        );

        $content = $this->accessor->makeContentOutOfException($emptyErrorCodeException);

        $this->assertInternalType('object', $content, 'It should return a content object');
        $this->assertObjectHasAttribute('errors', $content, 'The content object should have a errors attribute');

        $this->assertInternalType('array', $content->errors, 'The errors attribute should be an array');
        $this->assertCount(1, $content->errors, 'There should be an error as value of the errors attribute.');

        $this->assertInstanceOf(
            '\stdClass',
            $content->errors[0],
            'The first error should be an instance of standard class.'
        );
        $this->assertObjectHasAttribute('message', $content->errors[0], 'The first error object should have a message');
        $this->assertObjectHasAttribute('code', $content->errors[0], 'The first error object should have a code');

        $this->assertEquals(
            $message,
            $content->errors[0]->message,
            'The first error message should be the message of the exception argument'
        );
        $this->assertNotEquals(
            $code,
            $content->errors[0]->code,
            sprintf('The first error code should not equal the exception argument error code (%d)', $code)
        );

        $this->assertEquals(
            $exceededRateLimitErrorCode,
            $content->errors[0]->code,
            'The first error code should be the exceeded rate limit error code'
        );
    }

    /**
     * @test
     */
    public function it_should_handle_response_content_with_empty_error_code()
    {
        $exception = $this->makeException(1);
        $caughtExceptions = [];

        $tokenProphecy = $this->prophet->prophesize('\WeavingTheWeb\Bundle\ApiBundle\Entity\Token');
        $tokenProphecy->getOauthToken()->shouldBeCalled();

        /** @var \WeavingTheWeb\Bundle\ApiBundle\Entity\Token $tokenMock */
        $tokenMock = $tokenProphecy->reveal();

        try {
            $this->accessor->handleResponseContentWithEmptyErrorCode($exception, $tokenMock);
        } catch (\Exception $exception) {
            $caughtExceptions[] = $exception;
        } finally {
            $this->assertEquals(
                1,
                count($caughtExceptions),
                'It should re-throw input exception with non-empty error code'
            );
        }

        $emptyErrorCodeException = $this->makeException(0);
        $content = $this->accessor->handleResponseContentWithEmptyErrorCode($emptyErrorCodeException, $tokenMock);

        $this->assertInternalType(
            'object',
            $content,
            'It should handle response content with empty error code by returning an object'
        );
        $this->assertInstanceOf(
            '\StdClass',
            $content,
            'It should return a instance of standard class'
        );
    }

    /**
     * @return \Exception
     */
    protected function makeException($code)
    {
        $message = 'Response with Empty error code';

        return new \Exception($message, $code);
    }

    /**
     * @test
     */
    public function it_should_refresh_freezing_conditions_before_contacting_endpoint()
    {
        $tokenProphecy = $this->prophet->prophesize('\WeavingTheWeb\Bundle\ApiBundle\Entity\Token');
        $tokenProphecy->isFrozen()->shouldBeCalled();

        $tokenRepositoryProphecy = $this->prophet->prophesize(
            '\WeavingTheWeb\Bundle\ApiBundle\Repository\TokenRepository'
        );
        $tokenRepositoryProphecy->refreshFreezeCondition(Argument::any(), Argument::cetera())
            ->willReturn($tokenProphecy->reveal());

        /** @var \WeavingTheWeb\Bundle\ApiBundle\Repository\TokenRepository $tokenRepositoryMock **/
        $tokenRepositoryMock = $tokenRepositoryProphecy->reveal();

        $this->accessor->setTokenRepository($tokenRepositoryMock);
        $token = $this->accessor->preEndpointContact(['oauth' => ''], '/' );

        $this->assertInstanceOf(
            '\WeavingTheWeb\Bundle\ApiBundle\Entity\Token',
            $token,
            'It should return a refreshed token'
        );
    }

    /**
     * @test
     */
    public function it_should_use_bearer_token()
    {
        $this->assertFalse(
            $this->accessor->shouldUseBearerToken(),
            'It should not use bearer token when authentication header is missing'
        );

        $this->accessor->setAuthenticationHeader('bearer_token');
        $this->assertTrue(
            $this->accessor->shouldUseBearerToken(),
            'It should use bearer token when authentication header has been set'
        );
    }

    /**
     * @test
     */
    public function it_should_make_a_http_client_from_a_oauth_token()
    {
        $tokens = $this->mockOAuthTokens();
        $httpClient = $this->accessor->makeHttpClient($tokens);

        $this->assertInternalType('object', $httpClient, 'it should make an HTTP client.');
        $this->assertInstanceOf('\TwitterOAuth', $httpClient, 'it should make an instance of Twitter OAuth client.');
    }

    /**
     * @test
     */
    public function it_should_connect_to_an_endpoint()
    {
        $clientProphecy = $this->prophet->prophesize('\TwitterOAuth');
        $clientProphecy->get(Argument::any(), Argument::cetera())
            ->shouldBeCalled()
            ->willReturn(new \StdClass);

        /**
         * @var \TwitterOAuth $clientMock
         */
        $clientMock = $clientProphecy->reveal();

        $content = $this->accessor->connectToEndpoint($clientMock, 'http://endpoint');
        $this->assertInternalType('object', $content, 'It should return content as an object');
        $this->assertInstanceOf('\stdClass', $content, 'It should return an instance of the standard class');

    }

    /**
     * @return array
     */
    protected function mockOAuthTokens()
    {
        return ['key' => '', 'secret' => '', 'oauth' => '', 'oauth_secret' => ''];
    }

    /**
     * @test
     */
    public function it_should_contact_endpoint_using_consumer_key()
    {
        $tokens = $this->mockOAuthTokens();

        $tokenProphecy = $this->prophet->prophesize('\WeavingTheWeb\Bundle\ApiBundle\Entity\Token');

        /** @var \WeavingTheWeb\Bundle\ApiBundle\Entity\Token $tokenMock */
        $tokenMock = $tokenProphecy->reveal();

        $caughtExceptions = [];

        try {
            $this->accessor->contactEndpointUsingConsumerKey('http://endpoint', $tokenMock, $tokens);
        } catch (\Exception $exception) {
            $caughtExceptions[] = $exception;
        }

        $this->assertCount(1, $caughtExceptions,
            'It should throw an exception after trying to connect to fake endpoint'
        );
    }

    /**
     * @test
     */
    public function it_should_contact_endpoint_using_bearer_token()
    {
        $bearerHeader = 'token';
        $endpoint = 'http://endpoint';
        $expectedContent = (object)['field' => 'value'];

        $clientProphecy = $this->prophet->prophesize('\Goutte\Client');
        $clientProphecy->setHeader('Authorization', $bearerHeader)->willReturn(null)->shouldBeCalled();
        $clientProphecy->request('GET', $endpoint)->willReturn(null)->shouldBeCalled();

        $responseProphecy = $this->prophet->prophesize('\Symfony\Component\BrowserKit\Response');
        $responseProphecy->getContent()->willReturn(json_encode($expectedContent))->shouldBeCalled();

        $responseMock = $responseProphecy->reveal();
        $clientProphecy->getResponse()->willReturn($responseMock)->shouldBeCalled();

        $this->accessor->httpClient = $clientProphecy->reveal();

        $this->accessor->setAuthenticationHeader($bearerHeader);
        $content = $this->accessor->contactEndpointUsingBearerToken($endpoint);

        $this->assertInternalType('object', $content, 'It should return a object content');
        $this->assertInstanceOf('\stdClass', $content, 'It should return an instance of standard class');
        $this->assertEquals($expectedContent, $content, 'It should return the expected content');
    }

    /**
     * @test
     */
    public function it_should_tell_if_a_content_is_erroneous()
    {
        $content = (object) ['errors' => []];
        $this->assertFalse($this->accessor->hasError($content), 'It should tell if a content is not erroneous');

        $content->errors[] = 'error item';
        $this->assertTrue($this->accessor->hasError($content), 'It should tell if a content is erroneous');
    }

    /**
     * @test
     * @group it_should_log_a_request_error
     */
    public function it_should_log_a_request_error()
    {
        $content = $this->makeContentError();

        $loggerProphecy = $this->prophet->prophesize('\Psr\Log\LoggerInterface');
        $loggerMock = $loggerProphecy->reveal();

        $this->accessor->setLogger($loggerMock);

        $tokenProphecy = $this->prophet->prophesize('\WeavingTheWeb\Bundle\ApiBundle\Entity\Token');

        /** @var \WeavingTheWeb\Bundle\ApiBundle\Entity\Token $tokenMock */
        $tokenMock = $tokenProphecy->reveal();

        $exception = $this->accessor->logExceptionForToken('/', $content, $tokenMock);

        $this->assertInternalType('object', $exception, 'It should return an object');
        $this->assertInstanceOf(
            '\WeavingTheWeb\Bundle\TwitterBundle\Exception\UnavailableResourceException',
            $exception,
            'It should return an exception'
        );
    }

    /**
     * @test
     */
    public function it_should_extract_a_content_error()
    {
        $exception = $this->accessor->extractContentErrorAsException($this->makeContentError());
        $this->assertInternalType('object', $exception, 'It should extract a content error as an object');
        $this->assertInstanceOf(
            '\WeavingTheWeb\Bundle\TwitterBundle\Exception\UnavailableResourceException',
            $exception,
            'It should extract a content error as an object'
        );
    }

    /**
     * @return object
     */
    protected function makeContentError()
    {
        $errorMessage = (object) [
            'code' => 1,
            'message' => 'error message',
        ];

        return (object) [
            'errors' => [
                $errorMessage,
            ],
        ];
    }

    /**
     * @test
     */
    public function it_should_return_twitter_error_codes()
    {
        $twitterErrorCode = $this->accessor->getTwitterErrorCodes();

        $this->assertInternalType('array', $twitterErrorCode, 'It should return Twitter error codes');
        $this->assertGreaterThan(0, count($twitterErrorCode), 'It should return a least one Twitter error code');
    }

    /**
     * @test
     */
    public function it_should_match_with_one_of_twitter_error_codes()
    {
        $emptyReplyException = new UnavailableResourceException('', 0);
        $this->assertFalse(
            $this->accessor->matchWithOneOfTwitterErrorCodes($emptyReplyException),
            'A empty error code should not be considered as matching one of existing Twitter error codes.'
        );

        $userNotFoundException = new UnavailableResourceException('', Accessor::ERROR_USER_NOT_FOUND);
        $this->assertTrue(
            $this->accessor->matchWithOneOfTwitterErrorCodes($userNotFoundException),
            'A user not found error code should match with one of existing Twitter error codes.'
        );
    }

    /**
     * @test
     * @group it_should_take_care_of_request_errors_mismatching_twitter_error
     */
    public function it_should_take_care_of_request_errors_mismatching_twitter_error()
    {
        $tokenProphecy = $this->prophet->prophesize('\WeavingTheWeb\Bundle\ApiBundle\Entity\Token');
        $tokenProphecy->getOauthToken()->willReturn('token')->shouldBeCalled();
        $tokenProphecy->__toString()->willReturn('tok')->shouldBeCalled();

        /** @var \WeavingTheWeb\Bundle\ApiBundle\Entity\Token $tokenMock */
        $tokenMock = $tokenProphecy->reveal();

        $unfrozenTokenProphecy = $this->prophet->prophesize('\WeavingTheWeb\Bundle\ApiBundle\Entity\Token');
        $unfrozenTokenProphecy->isFrozen()->willReturn(false)->shouldBeCalled();
        $unfrozenTokenProphecy->getOauthToken()->willReturn('token')->shouldBeCalled();
        $unfrozenTokenProphecy->__toString()->willReturn('tok')->shouldBeCalled();

         /** @var \WeavingTheWeb\Bundle\ApiBundle\Entity\Token $unfrozenTokenMock */
        $unfrozenTokenMock = $unfrozenTokenProphecy->reveal();

        $tokenRepositoryProphecy = $this->prophet
            ->prophesize('\WeavingTheWeb\Bundle\ApiBundle\Repository\TokenRepository');
        $tokenRepositoryProphecy->freezeToken(Argument::type('string'))->willReturn(null)->shouldBeCalled();
        $tokenRepositoryProphecy->refreshFreezeCondition(Argument::type('string'), Argument::cetera())
            ->willReturn($unfrozenTokenMock);

        /** @var \WeavingTheWeb\Bundle\ApiBundle\Repository\TokenRepository $tokenRepositoryMock */
        $tokenRepositoryMock = $tokenRepositoryProphecy->reveal();
        $this->accessor->setTokenRepository($tokenRepositoryMock);

        $moderatorProphecy = $this->prophet
            ->prophesize('\App\Api\Moderator\ApiLimitModerator');
        $moderatorProphecy->waitFor(Argument::type('integer'), Argument::type('array'))->willReturn(null)
            ->shouldBeCalled();

        /** @var \App\Api\Moderator\ApiLimitModerator $moderatorMock */
        $moderatorMock = $moderatorProphecy->reveal();

        $this->accessor->setModerator($moderatorMock);

        $endpoint = 'http://endpoint';

        $caughtExceptions = [];
        try {
            $this->accessor->delayUnknownExceptionHandlingOnEndpointForToken($endpoint, $tokenMock);
        } catch (\Exception $exception) {
            $caughtExceptions[] = $exception;
        }

        $this->assertInstanceOf(
            '\WeavingTheWeb\Bundle\TwitterBundle\Exception\UnavailableResourceException',
            $caughtExceptions[0],
            'It should throw an unavailable resource exception'
        );
        $this->assertEquals(
            CURLE_COULDNT_RESOLVE_HOST,
            $caughtExceptions[0]->getCode(),
            'It should throw an exception with code of cURL error'
        );
    }

    /**
     * @return \WeavingTheWeb\Bundle\ApiBundle\Repository\TokenRepository
     */
    protected function mockOauthTokenCheck()
    {
        $tokenProphecy = $this->prophet->prophesize('\WeavingTheWeb\Bundle\ApiBundle\Entity\Token');
        $tokenProphecy->isFrozen()->willReturn(false)->shouldBeCalled();
        $tokenMock = $tokenProphecy->reveal();

        $tokenRepositoryProphecy = $this->prophet
            ->prophesize('\WeavingTheWeb\Bundle\ApiBundle\Repository\TokenRepository');
        $tokenRepositoryProphecy->refreshFreezeCondition(Argument::any(), Argument::cetera())
            ->willReturn($tokenMock)->shouldBeCalled();
        $tokenRepositoryProphecy->isOauthTokenFrozen(Argument::any())
            ->willReturn(false)->shouldBeCalled();
        $tokenRepositoryProphecy->findUnfrozenToken(Argument::any())
            ->willReturn($tokenMock)->shouldBeCalled();

        return $tokenRepositoryProphecy->reveal();
    }
}
