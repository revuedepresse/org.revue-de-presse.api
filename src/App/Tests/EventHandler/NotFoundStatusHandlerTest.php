<?php

namespace App\Tests\EventHandler;

use App\Accessor\Exception\NotFoundStatusException;
use App\Status\Entity\NotFoundStatus;
use App\Tests\StatusConsumptionTestCase;
use WeavingTheWeb\Bundle\ApiBundle\Entity\ArchivedStatus;
use WeavingTheWeb\Bundle\ApiBundle\Entity\Status;
use WeavingTheWeb\Bundle\ApiBundle\Entity\StatusInterface;

class NotFoundStatusHandlerTest extends StatusConsumptionTestCase
{
    /**
     * @param $statusClass
     * @param $statusId
     *
     * @test
     * @group it_should_save_not_found_statuses
     * @dataProvider getStatusClasses
     */
    public function it_should_save_not_found_statuses($statusClass, $statusId)
    {
        $this->accessor->propagateNotFoundStatuses = true;

        $message = 'It should throw a not found status exception when a status can not be found';

        /** @var StatusInterface $status */
        $status = new $statusClass();

        $this->saveStatusWithId($status, $statusId);

        try {
            $this->accessor->showStatus($statusId);
        } catch (\Exception $exception) {
            $this->assertInstanceOf(
                NotFoundStatusException::class,
                $exception,
                $message
            );

            $notFoundStatusRepository = $this->get('repository.not_found_status');
            $notFoundStatuses = $notFoundStatusRepository->findBy([]);

            $this->assertCount(
                1,
                $notFoundStatuses,
                'There should be exactly one record.'
            );

            /** @var NotFoundStatus $notFoundStatus */
            $notFoundStatus = $notFoundStatuses[0];

            $this->assertInstanceOf(
                NotFoundStatus::class,
                $notFoundStatus,
                sprintf(
                'The only record should be an instance of "%s"',
                NotFoundStatus::class
                )
            );

            $this->assertEquals(
                $status->getStatusId(),
                $notFoundStatus->getStatus()->getStatusId(),
                'The status not should match the one tested against via an accessor call'
            );

            return;
        }

        $this->fail($message);
    }

    /**
     * @return array
     */
    public function getStatusClasses()
    {
        return [
            [$statusClass = Status::class, $statusId = 1],
            [$statusClass = ArchivedStatus::class, $statusId = 2],
        ];
    }

    /**
     * @param $status
     * @param $statusId
     * @return mixed
     */
    private function saveStatusWithId($status, $statusId)
    {
        $entityManager = $this->get('doctrine.orm.entity_manager');

        $status->setStatusId($statusId);
        $status->setScreenName('screen name');
        $status->setName('name');
        $status->setText('text');
        $status->setUserAvatar('http://avatar.com');
        $status->setIdentifier('identifier');
        $status->setIndexed(false);
        $status->setCreatedAt(new \DateTime('now', new \DateTimeZone('UTC')));

        $entityManager->persist($status);
        $entityManager->flush();

        return $status;
    }
}
