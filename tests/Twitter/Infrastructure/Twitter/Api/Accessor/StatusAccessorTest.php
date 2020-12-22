<?php
declare(strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Twitter\Api\Accessor;

use App\Twitter\Infrastructure\Twitter\Api\Accessor\StatusAccessor;
use App\Twitter\Domain\Curation\CollectionStrategy;
use App\Twitter\Domain\Publication\Repository\StatusRepositoryInterface;
use App\Twitter\Infrastructure\Amqp\Message\FetchPublicationInterface;
use App\Twitter\Infrastructure\Twitter\Api\Accessor\StatusAccessorInterface;
use App\Twitter\Domain\Publication\Repository\ExtremumAwareInterface;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group status_accessor
 */
class StatusAccessorTest extends KernelTestCase
{
    private StatusAccessorInterface $accessor;

    /**
     * @return object|void|null
     */
    protected function setUp(): void
    {
        self::$kernel    = self::bootKernel();
        self::$container = self::$kernel->getContainer();

        $this->accessor = self::$container->get(StatusAccessor::class);
    }
    /**
     * @test
     *
     * @throws
     */
    public function it_should_update_extremum_for_ascending_order_finding(): void
    {
        $strategy = CollectionStrategy::fromArray(
            [
                FetchPublicationInterface::BEFORE       => '2010-01-01',
                FetchPublicationInterface::publishers_list_ID => 1
            ]
        );

        $statusRepository = $this->prophesizeStatusRepositoryForAscendingOrderFinding();
        $this->accessor->setStatusRepository($statusRepository->reveal());

        $options = $this->accessor->updateExtremum(
            $strategy,
            [
                FetchPublicationInterface::SCREEN_NAME => 'pierrec'
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
        $strategy = CollectionStrategy::fromArray(
            [
                FetchPublicationInterface::publishers_list_ID => 1
            ]
        );

        $statusRepository = $this->prophesizeStatusRepositoryForDescendingOrderFinding();
        $this->accessor->setStatusRepository($statusRepository->reveal());

        $options = $this->accessor->updateExtremum(
            $strategy,
            [
                FetchPublicationInterface::SCREEN_NAME => 'pierrec'
            ],
            false
        );

        self::assertArrayHasKey('since_id', $options);
        self::assertEquals(201, $options['since_id']);
    }

    /**
     * @return ObjectProphecy
     */
    private function prophesizeStatusRepositoryForAscendingOrderFinding(): ObjectProphecy
    {
        $statusRepository = $this->prophesize(
            StatusRepositoryInterface::class
        );
        $statusRepository->findNextExtremum(
            'pierrec',
            ExtremumAwareInterface::FINDING_IN_ASCENDING_ORDER,
            '2010-01-01'
        )->willReturn(['statusId' => '201']);

        return $statusRepository;
    }

    /**
     * @return ObjectProphecy
     */
    private function prophesizeStatusRepositoryForDescendingOrderFinding(): ObjectProphecy
    {
        $statusRepository = $this->prophesize(
            StatusRepositoryInterface::class
        );
        $statusRepository->findLocalMaximum(
            'pierrec',
            null
        )->willReturn(['statusId' => '200']);

        return $statusRepository;
    }
}
