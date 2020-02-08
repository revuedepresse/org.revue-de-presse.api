<?php

namespace App\Api\Entity;

use Doctrine\Common\Collections\ArrayCollection;

interface StatusInterface
{
    /**
     * Get id
     *
     * @return integer
     */
    public function getId();

    /**
     * @param string $hash
     */
    public function setHash($hash);

    /**
     * @return string
     */
    public function getHash();

    /**
     * @param $screenName
     * @return $this
     */
    public function setScreenName($screenName);

    /**
     * Get screeName
     *
     * @return string
     */
    public function getScreenName();

    /**
     * @param $name
     * @return $this
     */
    public function setName($name);

    /**
     * Get name
     *
     * @return string
     */
    public function getName();

    /**
     * @param $text
     * @return $this
     */
    public function setText($text);

    /**
     * Get text
     *
     * @return string
     */
    public function getText();

    /**
     * @param $userAvatar
     * @return $this
     */
    public function setUserAvatar($userAvatar);

    /**
     * Get userAvatar
     *
     * @return string
     */
    public function getUserAvatar();

    /**
     * @param $identifier
     * @return $this
     */
    public function setIdentifier($identifier);

    /**
     * Get identifier
     *
     * @return string
     */
    public function getIdentifier();

    public function setStatusId($statusId);

    public function getStatusId();

    public function setApiDocument($apiDocument);

    public function getApiDocument();

    /**
     * @param $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt();

    /**
     * @param $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt);

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt();

    /**
     * @param $indexed
     * @return $this
     */
    public function setIndexed($indexed);

    /**
     * Get indexed
     *
     * @return boolean
     */
    public function getIndexed();

    /**
     * @return ArrayCollection
     */
    public function getAggregates();

    /**
     * @param Aggregate $aggregate
     * @return self
     */
    public function removeFrom(Aggregate $aggregate): self;

    /**
     * @param Aggregate $aggregate
     * @return mixed
     */
    public function addToAggregates(Aggregate $aggregate);
}
