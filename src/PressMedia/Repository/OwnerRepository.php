<?php
declare(strict_types=1);

namespace App\PressMedia\Repository;

use App\PressMedia\Entity\Owner;
use Doctrine\ORM\EntityRepository;
use Psr\Log\LoggerInterface;

class OwnerRepository extends EntityRepository
{
    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @param array $mediaRelationshipProperties
     * @param array $mediasCollection
     * @return array
     */
    public function saveOwnersFromProperties(
        array $mediaRelationshipProperties,
        array $mediasCollection
    ): array
    {
        $ownerships =  [];

        $owners = array_map(
            function (array $ownership) use ($mediasCollection, &$ownerships) {
                $shares = null;

                if (is_numeric(str_replace(',', '.', $ownership[2]))) {
                    $shares = (float) str_replace(',', '.', $ownership[2]);
                }

                $ownershipLevel = null;
                if (!is_numeric($ownership[2]) && is_null($shares)) {
                    $ownershipLevel = $ownership[2];
                }

                $name = $ownership[1];
                if (!array_key_exists($name, $ownerships)) {
                    $ownerships[$name] = new Owner(
                        $sourceId = (int) $ownership[0],
                        $name
                    );
                    $this->getEntityManager()->persist($ownerships[$name]);
                    $this->getEntityManager()->flush();
                }

                if (!array_key_exists(3, $ownership)) {
                    throw new \Exception('Invalid media name.');
                }

                $mediaName = strtr($ownership[3], [
                    '‘' =>  '\'',
                    '’' =>  '\'',
                ]);

                if (!array_key_exists($mediaName, $mediasCollection)) {
                    throw new \Exception('Unknown media.');
                }

                /** @var Owner $owner */
                $owner = $ownerships[$name];
                $ownership = $owner->hasRelationshipWithRegardsToMedia(
                    $mediasCollection[$mediaName],
                    $shares,
                    $ownershipLevel
                );
                $this->getEntityManager()->persist($ownership);
                $this->getEntityManager()->flush();

                return $owner;
            },
            $mediaRelationshipProperties
        );

        $owners = array_combine(
            array_map(
                function (Owner $owner) {
                    $this->getEntityManager()->persist($owner);
                    return $owner->name;
                },
                $owners
            ),
            $owners
        );

        ksort($owners);

        try {
            $this->getEntityManager()->flush();
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
        }

        return $owners;
    }
}
