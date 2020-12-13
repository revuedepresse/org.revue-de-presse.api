<?php

namespace App\Tests\Context;

use App\Twitter\Domain\Resource\MemberIdentity;
use App\Twitter\Domain\Membership\Repository\MemberRepositoryInterface;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;

class ApiContext implements Context
{
    private const API_CONSUMER_SECRET = '^_not_so_secret_or_is_1t_$';

    /**
     * @var KernelInterface
     */
    private KernelInterface $kernel;

    private string $highlightPath;

    public function __construct(
        KernelInterface $kernel,
        MemberRepositoryInterface $memberRepository,
        RouterInterface $router
    ) {
        $this->kernel = $kernel;
        $this->highlightPath = $router->generate('highlight');

        $consumer = $memberRepository->saveApiConsumer(
            new MemberIdentity('api-consumer', 42),
            self::API_CONSUMER_SECRET
        );
        $memberRepository->saveMember($consumer);
    }

    /**
     * @Given there are new publications
     */
    public function thereAreNewPublications()
    {
        // Replace realtime database
    }

    /**
     * @When I access the daily press review
     */
    public function iAccessTheDailyPressReview()
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

        $response = $this->kernel->handle($request);
    }

    /**
     * @Then I can see highlights for today
     */
    public function iCanSeeHighlightsForToday()
    {
        throw new PendingException();
    }
}
