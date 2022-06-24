<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Curation\Repository;

use App\Tests\Twitter\Infrastructure\Http\Builder\Client\ListAwareHttpClientBuilder;
use App\Twitter\Domain\Http\Client\ListAwareHttpClientInterface;
use App\Twitter\Domain\Http\Selector\ListSelectorInterface;
use App\Twitter\Infrastructure\Curation\Repository\ListsBatchCollectedEventRepository;
use App\Twitter\Infrastructure\Http\Selector\MemberOwnershipsBatchSelector;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group curating_member_ownerships
 */
class OwnershipBatchCollectedEventRepositoryTest extends KernelTestCase
{
    private ListsBatchCollectedEventRepository $subjectUnderTest;
    private ListAwareHttpClientInterface       $accessor;

    protected function setUp(): void
    {
        // Arrange

        $kernel = static::bootKernel();

        $this->subjectUnderTest = static::getContainer()->get('test.'.ListsBatchCollectedEventRepository::class);
        $this->accessor = ListAwareHttpClientBuilder::build();
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