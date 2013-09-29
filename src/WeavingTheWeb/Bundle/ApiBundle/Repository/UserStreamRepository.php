<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Repository;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class UserStreamRepository extends ResourceRepository
{
    public function getAlias()
    {
        return 'ust';
    }

    /**
     * @param $statuses
     * @param $identifier
     */
    public function saveStatuses($statuses, $identifier)
    {
        $entityManager = $this->getEntityManager();
        $extracts = $this->extractProperties($statuses, function ($extract) use ($identifier) {
            $extract['identifier'] = $identifier;

            return $extract;
        });

        foreach ($extracts as $extract) {
            /**
             * @var \WeavingTheWeb\Bundle\ApiBundle\Entity\UserStream $userStream
             */
            $userStream = $this->queryFactory->makeUserStream($extract);
            $userStream->setIdentifier($extract['identifier']);
            $entityManager->persist($userStream);
        }

        $entityManager->flush();
    }

    /**
     * @param $statuses
     * @param $setter
     * @return array
     */
    protected function extractProperties($statuses, callable $setter)
    {
        $extracts = [];

        foreach ($statuses as $status) {
            if (property_exists($status, 'text')) {
                $extract = [
                    'text' => $status->text,
                    'screen_name' => $status->user->screen_name,
                    'name' => $status->user->name,
                    'user_avatar' => $status->user->profile_image_url,
                ];
                $extract = $setter($extract);
                $extracts[] = $extract;
            }
        }

        return $extracts;
    }
}
