<?php

namespace App\Tests\Context;

use App\PublishersList\Controller\ListController;
use App\Tests\NewsReview\Infrastructure\Repository\InMemoryPopularPublicationRepository;
use App\Twitter\Domain\Membership\Repository\MemberRepositoryInterface;
use App\Twitter\Domain\Resource\MemberIdentity;
use App\Twitter\Infrastructure\Cache\RedisCache;
use Behat\Behat\Context\Context;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Webmozart\Assert\Assert;

class ApiContext implements Context
{
    private const API_CONSUMER_SECRET = '^_not_so_secret_or_is_1t_$';

    private KernelInterface $kernel;

    private ListController $controller;

    private string $highlightPath;

    private InMemoryPopularPublicationRepository $popularPublicationRepository;

    private Request $request;

    public function __construct(
        KernelInterface $kernel,
        MemberRepositoryInterface $memberRepository,
        RouterInterface $router,
        RedisCache $cache,
        InMemoryPopularPublicationRepository $popularPublicationRepository,
        ListController $controller
    ) {
        $this->kernel = $kernel;

        $this->highlightPath = $router->generate('highlight');

        $cache->getClient()->flushAll();

        // Configure API consumer credentials
        $consumer = $memberRepository->saveApiConsumer(
            new MemberIdentity('api-consumer', 42),
            self::API_CONSUMER_SECRET
        );
        $memberRepository->saveMember($consumer);

        $this->controller = $controller;
        $this->popularPublicationRepository = $popularPublicationRepository;
    }

    /**
     * @When /^there are publications on a given day$/
     */
    public function thereArePublicationsOnAGivenDay()
    {
        // Override popular publication repository
        $this->controller->popularPublicationRepository = $this->popularPublicationRepository;
    }

    /**
     * @Given a news review is requested by an authenticated consumer
     */
    public function aNewsReviewIsRequestedByAnAuthenticatedConsumer()
    {
        $request = Request::create(
            $this->highlightPath,
            'GET',
            [
                'startDate' => (new \DateTimeImmutable('now'))->format('Y-m-d'),
                'endDate' => (new \DateTimeImmutable('now'))->format('Y-m-d'),
                'includeRetweets' => false
            ]
        );

        $request->headers = new HeaderBag(['x-auth-token' => self::API_CONSUMER_SECRET]);

        $this->request = $request;
    }

    /**
     * @Then /^these publications are ready to be served$/
     */
    public function thesePublicationsAreReadyToBeServed()
    {
        $response = $this->kernel->handle($this->request);
        Assert::eq(200, $response->getStatusCode());
    }
}
