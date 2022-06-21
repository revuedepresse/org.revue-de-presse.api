<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Curation\Repository;

use App\Tests\Twitter\Infrastructure\Api\Builder\Accessor\OwnershipAccessorBuilder;
use App\Twitter\Domain\Api\Accessor\OwnershipAccessorInterface;
use App\Twitter\Domain\Api\Selector\ListSelectorInterface;
use App\Twitter\Infrastructure\Curation\Repository\OwnershipBatchCollectedEventRepository;
use App\Twitter\Infrastructure\Api\Selector\MemberOwnershipsBatchSelector;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group curating_member_ownerships
 */
class OwnershipBatchCollectedEventRepositoryTest extends KernelTestCase
{
    private OwnershipBatchCollectedEventRepository $subjectUnderTest;
    private OwnershipAccessorInterface $accessor;

    protected function setUp(): void
    {
        // Arrange

        $kernel = static::bootKernel();

        $this->subjectUnderTest = static::getContainer()->get('test.'.OwnershipBatchCollectedEventRepository::class);
        $this->accessor = OwnershipAccessorBuilder::build();
    }

    /**
     * @test
     */
    public function it_collects_a_batch_of_ownership(): void
    {
        $batch = $this->subjectUnderTest->collectedOwnershipBatch(
            $this->accessor,
            new MemberOwnershipsBatchSelector('dummy_screen_name')
        );

        self::assertTrue($batch->isEmpty(), 'It collects an empty batch of ownerships');
    }
}