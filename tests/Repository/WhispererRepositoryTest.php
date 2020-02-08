<?php

namespace App\Test\Repository\WhispererRepositoryTest;

use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Prophecy\Argument;
use Prophecy\Prophet;

use App\Api\Entity\Whisperer;
use App\Api\Repository\WhispererRepository;

use PHPUnit\Framework\TestCase;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 * @group   repository-whisperer
 */
class WhispererRepositoryTest extends TestCase
{
    /**
     * @var \Prophecy\Prophet
     */
    private $prophet;

    public function setUp()
    {
        parent::setUp();

        $this->prophet = new Prophet();
    }

    /**
     * @test
     */
    public function it_should_declare_whisperers()
    {
        $whispererRepository = $this->makeWhispererRepository();
        
        $whispers = 10;
        $whisperer = $this->makeWhisperer($whispers);
        $declaredWhisperer = $whispererRepository->declareWhisperer($whisperer);

        try {
            $this->prophet->checkPredictions();
        } catch (\Exception $exception) {
            $this->fail(sprintf('%s', $exception->getMessage()));
        } finally {
            $this->assertInstanceOf(
                '\DateTime',
                $declaredWhisperer->getUpdatedAt(),
                'A whisperer should have a instance of \DateTime as "updatedAt" property value'
            );
            $this->assertEquals(
                $whispers,
                $declaredWhisperer->getWhispers(),
                'It should set the number of whispers for each newly declared whisperers'
            );
            $this->assertInstanceOf(
                '\WeavingTheWeb\Bundle\ApiBundle\Entity\Whisperer',
                $declaredWhisperer,
                'It should return an instance of the whisperer who has been declared.'
            );
        }
    }

    /**
     * @param Whisperer|null $whisperer
     * @return WhispererRepository
     */
    protected function makeWhispererRepository(Whisperer $whisperer = null)
    {
        $entityManagerProphecy = $this->mockEntityManager($whisperer);
        $classMetadataProphecy = $this->mockClassMetadata();

        return new WhispererRepository(
            $entityManagerProphecy->reveal(),
            $classMetadataProphecy->reveal()
        );
    }

    /**
     * @param $whispers
     * @return Whisperer 
     */
    protected function makeWhisperer($whispers)
    {
        return new Whisperer('whispherer', $whispers);
    }
    
    /**
     * @test
     */
    public function it_should_update_whispers_for_existing_whisperers()
    {
        $whispererRepository = $this->makeWhispererRepository();

        $whispersDeclaredAtFirst = 10;
        $whisperer = $this->makeWhisperer($whispersDeclaredAtFirst);
        $whispererDeclaredAtFirst = $whispererRepository->declareWhisperer($whisperer);

        $whispererRepository = $this->makeWhispererRepository($whispererDeclaredAtFirst);
        
        $whispersDeclaredAtSecond = 11;
        $whisperer = $this->makeWhisperer($whispersDeclaredAtSecond);
        $whispererDeclaredAtSecond = $whispererRepository->declareWhisperer($whisperer);

        $this->assertEquals(
            $whispersDeclaredAtFirst,
            $whispererDeclaredAtSecond->getPreviousWhispers(),
            'It should return a whisperer with whispers declared at first as previous whispers.'
        );

        $this->assertEquals(
            $whispersDeclaredAtSecond,
            $whispererDeclaredAtSecond->getWhispers(),
            'It should return a whisperer with whispers declared at second as whispers.'
        );
    }

    /**
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    protected function mockClassMetadata()
    {
        $classMetadataProphecy = $this->prophet->prophesize('\Doctrine\ORM\Mapping\ClassMetadata');

        return $classMetadataProphecy;
    }

    /**
     * @param Whisperer $whisperer
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    protected function mockEntityManager(Whisperer $whisperer = null)
    {
        $entityManagerProphecy = $this->prophet->prophesize('\Doctrine\ORM\EntityManager');
        $entityManagerProphecy->persist(Argument::type('\WeavingTheWeb\Bundle\ApiBundle\Entity\Whisperer'))
            ->shouldBeCalled();
        $entityManagerProphecy->flush()->shouldBeCalled();

        $entityPersisterProphecy = $this->prophet->prophesize(BasicEntityPersister::class);
        $entityPersisterProphecy->load(Argument::any(), Argument::cetera())->willReturn($whisperer);

        $unitOfWorkProphecy = $this->prophet->prophesize('\Doctrine\ORM\UnitOfWork');
        $unitOfWorkProphecy->getEntityPersister(Argument::any())->willReturn($entityPersisterProphecy->reveal());

        $entityManagerProphecy->getUnitOfWork()->willReturn($unitOfWorkProphecy->reveal());

        return $entityManagerProphecy;
    }
}
