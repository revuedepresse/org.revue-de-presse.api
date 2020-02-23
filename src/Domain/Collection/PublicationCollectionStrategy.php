<?php
declare(strict_types=1);

namespace App\Domain\Collection;

use App\Amqp\SkippableMemberException;
use App\Membership\Entity\MemberInterface;
use App\Domain\Resource\MemberIdentity;
use App\Domain\Resource\PublicationList;
use function array_key_exists;
use function count;
use function sprintf;

class PublicationCollectionStrategy implements PublicationStrategyInterface
{
    private string $screenName;

    private ?bool $collectPublicationsPrecedingThoseAlreadyCollected = null;

    private ?string $listRestriction = null;

    private array $listCollectionRestriction = [];

    private bool $weightedAggregates = false;

    private ?string $queryRestriction = null;

    private ?string $memberRestriction = null;

    private bool $ignoreWhispers = false;

    private bool $includeOwner = false;

    /**
     * @return bool|null
     */
    public function collectPublicationsPrecedingThoseAlreadyCollected(): ?bool
    {
        return $this->collectPublicationsPrecedingThoseAlreadyCollected;
    }

    /**
     * @param string $screenName
     *
     * @return $this
     */
    public function forMemberHavingScreenName(string $screenName): self
    {
        $this->screenName = $screenName;

        return $this;
    }

    /**
     * @return string
     */
    public function forWhichList(): string
    {
        return $this->listRestriction;
    }

    /**
     * @return bool
     */
    public function noListRestriction(): bool
    {
        return $this->listRestriction === null;
    }

    /**
     * @return bool
     */
    public function listRestriction(): bool
    {
        return !$this->noListRestriction();
    }

    /**
     * @return bool
     */
    private function emptyListCollection(): bool
    {
        return count($this->listCollectionRestriction) === 0;
    }

    /**
     * @return bool
     */
    public function shouldApplyListCollectionRestriction(): bool
    {
        return !$this->emptyListCollection();
    }

    /**
     * @return bool
     */
    private function noMemberRestriction(): bool
    {
        return $this->forSpecificMember() === null;
    }

    /**
     * @return bool
     */
    private function shouldNotApplyListRestriction(): bool
    {
        return $this->noListRestriction()
            && $this->emptyListCollection();
    }

    /**
     * @param $list
     *
     * @return bool
     */
    private function applyListRestriction(PublicationList $list): bool
    {
        return $list->name() === $this->listRestriction;
    }

    /**
     * @param $list
     *
     * @return bool
     */
    private function applyListRestrictionAmongOthers(PublicationList $list): bool
    {
        return array_key_exists(
            $list->name(),
            $this->listCollectionRestriction
        );
    }

    /**
     * @param $list
     *
     * @return bool
     */
    public function shouldProcessList(PublicationList $list): bool
    {
        return $this->shouldNotApplyListRestriction()
            || $this->applyListRestriction($list)
            || $this->applyListRestrictionAmongOthers($list);
    }

    /**
     * @return string
     */
    private function forSpecificMember(): ?string
    {
        return $this->memberRestriction;
    }

    /**
     * @param MemberIdentity $memberIdentity
     *
     *
     * @return bool
     */
    public function restrictDispatchToSpecificMember(MemberIdentity $memberIdentity): bool
    {
        if ($this->noMemberRestriction()) {
            return false;
        }

        return $memberIdentity->screenName() !== $this->forSpecificMember();
    }

    /**
     * @return string|null
     */
    public function forWhichQuery(): ?string
    {
        return $this->queryRestriction;
    }

    /**
     * @return bool
     */
    public function shouldSearchByQuery(): bool {
        return $this->queryRestriction !== null;
    }

    /**
     * @return bool
     */
    public function shouldNotSearchByQuery(): bool {
        return !$this->shouldSearchByQuery();
    }

    /**
     * @return bool
     */
    public function noQueryRestriction(): bool
    {
        return $this->forWhichQuery() === null;
    }

    /**
     * @return string
     */
    public function onBehalfOfWhom(): string
    {
        return $this->screenName;
    }

    /**
     * @return bool
     */
    public function shouldIgnoreWhispers(): bool
    {
        return $this->ignoreWhispers;
    }

    /**
     * @param MemberInterface $member
     *
     * @return bool
     */
    public function shouldIgnoreMemberWhenWhispering(MemberInterface $member): bool
    {
        return $this->shouldIgnoreWhispers() && $member->isAWhisperer();
    }

    /**
     * @param MemberInterface $member
     * @param MemberIdentity               $memberIdentity
     *
     * @throws SkippableMemberException
     */
    public function guardAgainstWhisperingMember(
        MemberInterface $member,
        MemberIdentity $memberIdentity
    ): void {
        if ($this->shouldIgnoreMemberWhenWhispering($member)) {
            throw new SkippableMemberException(
                sprintf(
                    'Ignoring whisperer with screen name "%s"',
                    $memberIdentity->screenName()
                )
            );
        }
    }

    /**
     * @return bool
     */
    public function shouldPrioritizeLists(): bool
    {
        return $this->weightedAggregates;
    }

    /**
     * @return bool
     */
    public function allListsAreEquivalent(): bool
    {
        return !$this->shouldPrioritizeLists();
    }

    /**
     * @param string|null $listRestriction
     *
     * @return PublicationCollectionStrategy
     */
    public function willApplyListRestrictionToAList(string $listRestriction): self
    {
        $this->listRestriction = $listRestriction;

        return $this;
    }

    /**
     * @param string $queryRestriction
     *
     * @return PublicationCollectionStrategy
     */
    public function willApplyQueryRestriction(string $queryRestriction): self
    {
        $this->queryRestriction = $queryRestriction;

        return $this;
    }

    /**
     * @param array $listCollectionRestriction
     *
     * @return PublicationCollectionStrategy
     */
    public function willApplyRestrictionToAListCollection(array $listCollectionRestriction): self
    {
        $this->listCollectionRestriction = $listCollectionRestriction;

        return $this;
    }

    /**
     * @param string $memberRestriction
     *
     * @return PublicationCollectionStrategy
     */
    public function willApplyRestrictionToAMember(string $memberRestriction): self
    {
        $this->memberRestriction = $memberRestriction;

        return $this;
    }

    /**
     * @param bool|null $before
     *
     * @return PublicationCollectionStrategy
     */
    public function willCollectPublicationsPrecedingThoseAlreadyCollected(?bool $before): self
    {
        $this->collectPublicationsPrecedingThoseAlreadyCollected = $before;

        return $this;
    }

    /**
     * @param bool $ignoreWhispers
     *
     * @return PublicationCollectionStrategy
     */
    public function willIgnoreWhispers(bool $ignoreWhispers): self
    {
        $this->ignoreWhispers = $ignoreWhispers;

        return $this;
    }

    /**
     * @param bool $includeOwner
     *
     * @return $this
     */
    public function willIncludeOwner(bool $includeOwner): self
    {
        $this->includeOwner = $includeOwner;

        return $this;
    }

    /**
     * @return bool
     */
    public function shouldIncludeOwner(): bool
    {
        return $this->includeOwner;
    }

    /**
     * @param bool $priorityToAggregates
     *
     * @return PublicationCollectionStrategy
     */
    public function willPrioritizeAggregates(bool $priorityToAggregates): self
    {
        $this->weightedAggregates = $priorityToAggregates;

        return $this;
    }
}