<?php

namespace WeavingTheWeb\Bundle\TwitterBundle\Tests\Serializer;

use Prophecy\Argument,
    Prophecy\Prophet;

use WeavingTheWeb\Bundle\ApiBundle\Entity\Aggregate;

use WeavingTheWeb\Bundle\TwitterBundle\Api\Accessor;
use WTW\CodeGeneration\QualityAssuranceBundle\Test\WebTestCase;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 *
 * @group serialization
 * @group twitter
 * @group status-serialization
 */
class UserStatusTest extends WebTestCase
{
    /**
     * @var \WeavingTheWeb\Bundle\TwitterBundle\Serializer\UserStatus;
     */
    protected $serializer;

    /**
     * @var Accessor
     */
    protected $accessor;

    /**
     * @var Prophet
     */
    private $prophet;

    public function setUp()
    {
        parent::setUp();

        $this->client = $this->getClient();
        $this->prophet = new Prophet();

        $this->serializer = $this->get('weaving_the_web_twitter.serializer.user_status');
        $this->accessor = $this->get('weaving_the_web_twitter.api_accessor');

        $this->mockTokenRepository();
        $this->mockAggregateRepository();
        $this->mockAccessor();
        $this->mockEntityManager();
    }

    public function tearDown()
    {
        $this->prophet->checkPredictions();

        parent::tearDown();
    }

    /**
     * @test
     * @group it_should_guess_max_id
     */
    public function it_should_guess_max_id() {
        $options = [
            'since_id' => 4
        ];

        $options = $this->accessor->guessMaxId($options, false);

        $this->assertArrayHasKey('max_id', $options, 'It should return options containing a max id value');
        $this->assertEquals(2, $options['max_id'], 'The max id should be smaller than since id');

        $options = $this->accessor->guessMaxId([], false);
        $this->assertEquals(INF, $options['max_id'], 'The max id should be equal to infinity');
    }

    /**
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\ProtectedAccountException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\SuspendedAccountException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\UnavailableResourceException
     *
     * @test
     * @group it_should_serialize_a_status
     */
    public function it_should_serialize_a_status()
    {
        $success = $this->serializer->serialize([
            'oauth' => '',
            'screen_name' => 'user',
            'aggregate_id' => 1
        ]);
        $this->assertTrue($success);
    }

    protected function mockAccessor()
    {
        $accessorMock = $this->prophet->prophesize('WeavingTheWeb\Bundle\TwitterBundle\Api\Accessor');

        $accessorMock->userToken = '';
        $accessorMock->showUser(Argument::type('string'))->willReturn(
            (object)[
                'screen_name' => 'user',
                'protected' => false,
                'statuses_count' => 1
            ]
        );
        $accessorMock->getUserToken()->willReturn('tok');
        $accessorMock->shouldSkipSerializationForMemberWithScreenName(Argument::any())->willReturn(false);
        $accessorMock->isApiLimitReached()->willReturn(false);
        $accessorMock->isApiRateLimitReached(Argument::type('string'))->willReturn(false);
        $accessorMock->fetchTimelineStatuses(Argument::type('array'))->willReturn([
            (object) [
                'text' => 'This is a test.',
                'user' => (object) [
                    'screen_name' => 'user',
                    'name' => 'full name',
                    'profile_image_url' => 'http://profile',
                ],
                'id_str' => '10',
                'created_at' => (new \DateTime())->format('Y-m-d'),
                'api_document' => '{}',
            ]
        ]);

        $this->serializer->setAccessor($accessorMock->reveal());
    }

    protected function mockTokenRepository()
    {
        $tokenRepositoryMock = $this->prophet->prophesize('WeavingTheWeb\Bundle\ApiBundle\Repository\TokenRepository');

        $tokenMock = $this->mockToken();
        $tokenRepositoryMock->refreshFreezeCondition(Argument::any(), Argument::cetera())
            ->willReturn($tokenMock->reveal());
        $tokenRepositoryMock->findOneBy(Argument::type('array'))->willReturn($tokenMock);
        $tokenRepositoryMock->findFirstUnfrozenToken()->willReturn($tokenMock);

        $this->getContainer()->set('weaving_the_web_twitter.repository.token', $tokenRepositoryMock->reveal());
    }

    protected function mockEntityManager()
    {
        $entityManagerMock = $this->prophet->prophesize('Doctrine\ORM\EntityManager');

        $entityManagerMock->persist(Argument::any())->willReturn(null);
        $entityManagerMock->flush()->willReturn(null);

        $this->getContainer()->set('doctrine.orm.entity_manager', $entityManagerMock->reveal());
    }

    protected function mockAggregateRepository()
    {
        $aggregateRepositoryMock = $this->prophet->prophesize('WeavingTheWeb\Bundle\ApiBundle\Repository\AggregateRepository');

        $aggregate = new Aggregate('test_aggregate', 'My List');

        $aggregateRepositoryMock->find(Argument::type('integer'))
            ->willReturn($aggregate);

        $this->getContainer()->set('weaving_the_web_twitter.repository.aggregate', $aggregateRepositoryMock->reveal());
    }

    public function mockToken()
    {
        $tokenMock = $this->prophet->prophesize('WeavingTheWeb\Bundle\ApiBundle\Entity\Token');
        $tokenMock->isFrozen()->willReturn(false);

        return $tokenMock;
    }

    /**
     * @test
     * @group it_should_tell_if_serialization_limit_has_been_hit
     */
    public function it_should_tell_if_serialization_limit_has_been_hit()
    {
        $this->assertTrue($this->serializer->hitSerializationLimit(4000),
            'It should serialize at least 3200 statuses');

        $this->assertTrue($this->serializer->hitSerializationLimit(3100),
            'It should serialize at least 3200 statuses without omitting the statuses which might have been deleted');

        $this->assertFalse($this->serializer->hitSerializationLimit(3099),
            'It should not consider the serialization limit has been hit ' .
            'when 3200 statuses minus one hundred statuses have been serialized.');
    }

    /**
     * @test
     * @group it_should_tell_if_statuses_have_been_serialized
     */
    public function it_should_tell_if_statuses_have_been_serialized()
    {
        $noStatusesSerialized = 'It should tell if no statuses have just been serialized';
        $this->assertFalse($this->serializer->justSerializedSomeStatuses(null), $noStatusesSerialized);

        $this->assertFalse($this->serializer->justSerializedSomeStatuses(0), $noStatusesSerialized);

        $this->assertTrue($this->serializer->justSerializedSomeStatuses(1),
            'It should tell if a status has just been serialized');
    }

    /**
     * @test
     * @group it_should_tell_if_all_available_statuses_have_been_serialized
     */
    public function it_should_tell_if_all_available_statuses_have_been_serialized()
    {
        $lastSerializationBatchSize = 1;
        $totalSerializedStatuses = 3099;
        $this->assertFalse($this->serializer->serializedAllAvailableStatuses(
                $lastSerializationBatchSize, $totalSerializedStatuses
            ),
            'It should tell if all available statuses have been serialized');

        $lastSerializationBatchSize = 0;
        $totalSerializedStatuses = 3100;
        $this->assertTrue($this->serializer->serializedAllAvailableStatuses(
                $lastSerializationBatchSize, $totalSerializedStatuses
            ),
            'It should tell if there would likely be more statuses available.');
    }

    /**
     * @test
     * @group it_should_declare_members_as_whisperer_after_all_their_statuses_have_been_collected
     */
    public function it_should_declare_members_as_whisperer_after_all_their_statuses_have_been_collected()
    {
        $whispererRepositoryProphecy = $this->prophet
            ->prophesize('\WeavingTheWeb\Bundle\ApiBundle\Repository\WhispererRepository');

        $testCase = $this;
        $whispererRepositoryProphecy->declareWhisperer(
            Argument::type('\WeavingTheWeb\Bundle\ApiBundle\Entity\Whisperer')
        )->will(function ($arguments) use ($testCase) {
            $testCase->assertInstanceOf('\WeavingTheWeb\Bundle\ApiBundle\Entity\Whisperer', $arguments[0]);

            $whisperer = $arguments[0];
            $testCase->assertGreaterThan(3000, $whisperer->getWhispers(),
                'It should return a valid number of whispers');

            return null;
        });
        $this->serializer->setWhispererRepository($whispererRepositoryProphecy->reveal());

        $this->assertFalse($this->serializer->flagWhisperers('whisperer', 1, 10),
            'It should not flag users as whisperers when some more statuses have been collected.');

        $this->assertTrue($this->serializer->flagWhisperers('whisperer', 0, 3100),
            'It should flag whisperers for whom all available statuses has been collected already.');

        $this->assertTrue($this->serializer->flagWhisperers('whisperer', null, 3099),
            'It should tell if a whisperer has been successfully declared from his screen name.');
    }
}
