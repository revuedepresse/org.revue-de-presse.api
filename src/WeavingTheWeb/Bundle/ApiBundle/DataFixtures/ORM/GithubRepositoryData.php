<?php

namespace WeavingTheWeb\Bundle\ApiBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface,
    Doctrine\Common\Persistence\ObjectManager;
use WeavingTheWeb\Bundle\ApiBundle\Entity\GithubRepository;

class GithubRepositoryData implements FixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $properties = [
            'github_id' => 1,
            'forks' => 1,
            'watchers' => 1,
            'clone_url' => 'http://clone.url',
            'avatar_url' => 'http://avatar.url',
            'description' => 'my description',
            'name' => 'repository name',
            'owner' => 'John Doe',
            'owner_id' => 1,
            'language' => 'PHP',
            'status' => 0,
        ];

        $repository = new GithubRepository();

        $repository->setGithubId($properties['github_id']);
        $repository->setForks($properties['forks']);
        $repository->setWatchers($properties['watchers']);
        $repository->setStatus($properties['status']);
        $repository->setOwnerId($properties['owner_id']);
        $repository->setOwner($properties['owner']);
        $repository->setLanguage($properties['language']);
        $repository->setName($properties['name']);
        $repository->setAvatarUrl($properties['avatar_url']);
        $repository->setCloneUrl($properties['clone_url']);
        $repository->setDescription($properties['description']);
        $repository->setCreatedAt(new \DateTime());
        $repository->setUpdatedAt(new \DateTime());

        $manager->persist($repository);

        $manager->flush();
    }
}
