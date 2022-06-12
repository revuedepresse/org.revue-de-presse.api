<?php
declare (strict_types=1);

namespace App\NewsReview\Infrastructure\Repository;

use App\NewsReview\Domain\Routing\Repository\PublishersListRouteRepositoryInterface;
use App\NewsReview\Infrastructure\Routing\Entity\PublishersList;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group publishers_list
 */
class PublishersListRouteRepositoryTest extends KernelTestCase
{
    private PublishersListRouteRepositoryInterface $repositoryUnderTest;
    private EntityManagerInterface $entityManager;

    public function setUp(): void
    {
        $kernel = static::bootKernel();

        self::$container = $kernel->getContainer();

        $this->repositoryUnderTest = self::$container->get('test.'.PublishersListRouteRepositoryInterface::class);
        $this->entityManager = self::$container->get('doctrine.orm.entity_manager');

        $this->removeFixtures();
    }

    /**
     * @test
     */
    public function it_finds_all_publishers_list_routes_sorted_by_hostname(): void
    {
        $firstList = new PublishersList('List 1', Uuid::uuid4());
        $firstListHostname = 'https://list1.example.com';

        $secondList = new PublishersList('List 2', Uuid::uuid4());
        $secondListHostname = 'https://list2.example.com';

        $this->repositoryUnderTest->exposePublishersList(
            $secondList,
            $secondListHostname
        );
        $this->repositoryUnderTest->exposePublishersList(
            $firstList,
            $firstListHostname
        );

        $routes = $this->repositoryUnderTest->allPublishersRoutes()->toArray();

        self::assertNotEmpty($routes, 'There should be one route available at least.');

        self::assertCount(2, $routes, 'There should be exactly two routes');

        self::assertArrayHasKey(0, $routes, 'There should be a first route matching the first publishers list.');
        self::assertArrayHasKey(1, $routes, 'There should be a second route matching the second publishers list.');

        self::assertIsArray(
            $routes[0],
            'The first publishers list route should be represented as an array',
        );
        self::assertIsArray(
            $routes[1],
            'The second publishers list route should be represented as an array',
        );

        self::assertArrayHasKey(
            'hostname',
            $routes[0],
            'The first publishers list route should contain a hostname',
        );
        self::assertArrayHasKey(
            'hostname',
            $routes[1],
            'The second publishers list route should contain a hostname',
        );

        self::assertEquals(
            $firstListHostname,
            $routes[0]['hostname'],
            'The first publishers list route should be exposed from the expected hostname.',
        );
        self::assertEquals(
            $secondListHostname,
            $routes[1]['hostname'],
            'The second publishers list route should be exposed from the expected hostname.',
        );
    }

    protected function tearDown(): void
    {
        $this->removeFixtures();

        parent::tearDown();
    }

    private function removeFixtures(): void
    {
        $this->entityManager->getConnection()->executeQuery('
            TRUNCATE TABLE publishers_list_route CASCADE;
        ');
    }
}
