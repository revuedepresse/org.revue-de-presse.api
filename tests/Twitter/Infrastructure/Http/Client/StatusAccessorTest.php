<?php
declare(strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Http\Client\Client;

use App\Twitter\Infrastructure\Http\Client\TweetAwareHttpClient;
use App\Twitter\Infrastructure\Curation\CurationSelectors;
use App\Twitter\Domain\Publication\Repository\TweetRepositoryInterface;
use App\Twitter\Infrastructure\Amqp\Message\FetchAuthoredTweetInterface;
use App\Twitter\Domain\Http\Client\TweetAwareHttpClientInterface;
use App\Twitter\Domain\Publication\Repository\ExtremumAwareInterface;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group status_accessor
 */
class StatusAccessorTest extends KernelTestCase
{
    use ProphecyTrait;

    private TweetAwareHttpClientInterface $accessor;

    /**
     * @return object|void|null
     */
    protected function setUp(): void
    {
        self::$kernel    = self::bootKernel();

        $this->accessor = static::getContainer()->get(TweetAwareHttpClient::class);
    }
    /**
     * @test
     *
     * @throws
     */
    public function it_should_update_extremum_for_ascending_order_finding(): void
    {
        $selectors = CurationSelectors::fromArray(
            [
                FetchAuthoredTweetInterface::BEFORE       => '2010-01-01',
                FetchAuthoredTweetInterface::TWITTER_LIST_ID => 1
            ]
        );

        $tweetRepository = $this->prophesizeTweetRepositoryForAscendingOrderFinding();
        $this->accessor->setTweetRepository($tweetRepository->reveal());

        $options = $this->accessor->updateExtremum(
            $selectors,
            [
                FetchAuthoredTweetInterface::SCREEN_NAME => 'pierrec'
            ],
            false
        );

        self::assertArrayHasKey('max_id', $options);
        self::assertEquals(200, $options['max_id']);
    }

    /**
     * @test
     *
     * @throws
     */
    public function it_should_update_extremum_for_descending_order_finding(): void
    {
        $selectors = CurationSelectors::fromArray(
            [
                FetchAuthoredTweetInterface::TWITTER_LIST_ID => 1
            ]
        );

        $tweetRepository = $this->prophesizeTweetRepositoryForDescendingOrderFinding();
        $this->accessor->setTweetRepository($tweetRepository->reveal());

        $options = $this->accessor->updateExtremum(
            $selectors,
            [
                FetchAuthoredTweetInterface::SCREEN_NAME => 'pierrec'
            ],
            false
        );

        self::assertArrayHasKey('since_id', $options);
        self::assertEquals(201, $options['since_id']);
    }

    /**
     * @return ObjectProphecy
     */
    private function prophesizeTweetRepositoryForAscendingOrderFinding(): ObjectProphecy
    {
        $tweetRepository = $this->prophesize(
            TweetRepositoryInterface::class
        );
        $tweetRepository->findNextExtremum(
            'pierrec',
            ExtremumAwareInterface::FINDING_IN_ASCENDING_ORDER,
            '2010-01-01'
        )->willReturn(['statusId' => '201']);

        return $tweetRepository;
    }

    /**
     * @return ObjectProphecy
     */
    private function prophesizeTweetRepositoryForDescendingOrderFinding(): ObjectProphecy
    {
        $tweetRepository = $this->prophesize(
            TweetRepositoryInterface::class
        );
        $tweetRepository->findLocalMaximum(
            'pierrec',
            null
        )->willReturn(['statusId' => '200']);

        return $tweetRepository;
    }
}
