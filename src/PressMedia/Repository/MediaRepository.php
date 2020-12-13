<?php

namespace App\PressMedia\Repository;

use App\PressMedia\Entity\Media;
use Doctrine\ORM\EntityRepository;

class MediaRepository extends EntityRepository
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

    /**
     * @param $mediaFileContents
     * @return array
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveMediasFromProperties($mediaFileContents): array
    {
        $medias = array_map(
            function (array $media) {
                if (!array_key_exists(3, $media)) {
                    throw new \Exception('Unavailable properties');
                }
                if (!is_numeric($media[3])) {
                    throw new \Exception('Unexpected property (non-numeric for media type)');
                }

                if (!is_numeric($media[0])) {
                    throw new \Exception('Unexpected property (non-numeric for source id)');
                }

                if (empty($media[5])) {
                    $media[5] = null;
                }

                if (empty($media[6])) {
                    $media[6] = null;
                }

                if (!array_key_exists(7, $media) || empty($media[7])) {
                    $media[7] = null;
                }

                $name = strtr($media[1], [
                    '‘' =>  '\'',
                    '’' =>  '\'',
                ]);

                $sourceId = (int) $media[0];
                $media = $this->findOneBy(['sourceId' => $sourceId]);
                if ($media instanceof Media) {
                    return $media;
                }

                $media = new Media(
                    $sourceId,
                    $name,
                    $type = (int) $media[3],
                    $channel = $media[5],
                    $periodicity = $media[6],
                    $scope = $media[7]
                );
                $this->getEntityManager()->persist($media);

                return $media;
            },
            $mediaFileContents
        );

        try {
            $this->getEntityManager()->flush();
        } catch (\Exception $exception ) {
            $this->logger->critical($exception->getMessage());
        }

        $medias = array_combine(
            array_map(
                function (Media $media) {
                    return $media->name;
                },
                $medias
            ),
            $medias
        );
        ksort($medias);

        return $medias;
    }
}
