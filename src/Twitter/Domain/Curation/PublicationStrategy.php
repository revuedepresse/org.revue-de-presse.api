<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Curation;

use App\Twitter\Infrastructure\Amqp\Exception\SkippableMemberException;
use App\Twitter\Domain\Resource\MemberIdentity;
use App\Twitter\Domain\Resource\PublishersList;
use App\Membership\Domain\Entity\MemberInterface;
use function array_key_exists;
use function count;
use function sprintf;

class PublicationStrategy implements PublicationStrategyInterface
{
    private string $screenName;

    private ?string $dateBeforeWhichPublicationsAreCollected = null;

    private ?string $listRestriction = null;

    private array $listCollectionRestriction = [];

    private bool $weightedAggregates = false;

    private ?string $queryRestriction = null;

    private ?string $memberRestriction = null;

    private bool $ignoreWhispers = false;

    private bool $includeOwner = false;

    private bool $fetchLikes = false;

    private int $cursor = -1;

    /**
     * @return bool
     */
    public function allListsAreEquivalent(): bool
    {
        return !$this->shouldPrioritizeLists();
    }

    /**
     * @return string|null
     */
    public function dateBeforeWhichPublicationsAreCollected(): ?string
    {
        return $this->dateBeforeWhichPublicationsAreCollected;
    }

    /**
     * @param string $screenName
     *
     * @return $this
     */
    public function forMemberHavingScreenName(string $screenName): PublicationStrategyInterface
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
     * @return string|null
     */
    public function forWhichQuery(): ?string
    {
        return $this->queryRestriction;
    }

    public function fromCursor(int $cursor): PublicationStrategyInterface
    {
        $this->cursor = $cursor;

        return $this;
    }

    /**
     * @param MemberInterface $member
     * @param MemberIdentity  $memberIdentity
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
    public function listRestriction(): bool
    {
        return !$this->noListRestriction();
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
     * @return bool
     */
    public function shouldApplyListCollectionRestriction(): bool
    {
        return !$this->emptyListCollection();
    }

    public function shouldFetchLikes(): bool
    {
        return $this->fetchLikes;
    }

    public function shouldFetchPublicationsFromCursor(): ?int
    {
        return $this->cursor;
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
     * @return bool
     */
    public function shouldIgnoreWhispers(): bool
    {
        return $this->ignoreWhispers;
    }

    /**
     * @return bool
     */
    public function shouldIncludeOwner(): bool
    {
        return $this->includeOwner;
    }

    /**
     * @return bool
     */
    public function shouldNotSearchByQuery(): bool
    {
        return !$this->shouldSearchByQuery();
    }

    /**
     * @return bool
     */
    public function shouldPrioritizeLists(): bool
    {
        return $this->weightedAggregates;
    }

    /**
     * @param $list
     *
     * @return bool
     */
    public function shouldProcessList(PublishersList $list): bool
    {
        return $this->shouldNotApplyListRestriction()
            || $this->applyListRestriction($list)
            || $this->applyListRestrictionAmongOthers($list);
    }

    /**
     * @return bool
     */
    public function shouldSearchByQuery(): bool
    {
        return $this->queryRestriction !== null;
    }

    /**
     * @param string|null $listRestriction
     *
     * @return PublicationStrategyInterface
     */
    public function willApplyListRestrictionToAList(string $listRestriction): PublicationStrategyInterface
    {
        $this->listRestriction = $listRestriction;

        return $this;
    }

    /**
     * @param string $queryRestriction
     *
     * @return PublicationStrategyInterface
     */
    public function willApplyQueryRestriction(string $queryRestriction): PublicationStrategyInterface
    {
        $this->queryRestriction = $queryRestriction;

        return $this;
    }

    /**
     * @param array $listCollectionRestriction
     *
     * @return PublicationStrategyInterface
     */
    public function willApplyRestrictionToAListCollection(array $listCollectionRestriction
    ): PublicationStrategyInterface {
        $this->listCollectionRestriction = $listCollectionRestriction;

        return $this;
    }

    /**
     * @param string $memberRestriction
     *
     * @return PublicationStrategyInterface
     */
    public function willApplyRestrictionToAMember(string $memberRestriction): PublicationStrategyInterface
    {
        $this->memberRestriction = $memberRestriction;

        return $this;
    }

    /**
     * @param string|null $date
     *
     * @return PublicationStrategyInterface
     */
    public function willCollectPublicationsPreceding(?string $date): PublicationStrategyInterface
    {
        $this->dateBeforeWhichPublicationsAreCollected = $date;

        return $this;
    }

    /**
     * @param bool $fetchLikes
     *
     * @return PublicationStrategyInterface
     */
    public function willFetchLikes(bool $fetchLikes = false): PublicationStrategyInterface
    {
        $this->fetchLikes = $fetchLikes;

        return $this;
    }

    /**
     * @param bool $ignoreWhispers
     *
     * @return PublicationStrategyInterface
     */
    public function willIgnoreWhispers(bool $ignoreWhispers): PublicationStrategyInterface
    {
        $this->ignoreWhispers = $ignoreWhispers;

        return $this;
    }

    /**
     * @param bool $includeOwner
     *
     * @return $this
     */
    public function willIncludeOwner(bool $includeOwner): PublicationStrategyInterface
    {
        $this->includeOwner = $includeOwner;

        return $this;
    }

    /**
     * @param bool $priorityToAggregates
     *
     * @return PublicationStrategy
     */
    public function willPrioritizeAggregates(bool $priorityToAggregates): PublicationStrategyInterface
    {
        $this->weightedAggregates = $priorityToAggregates;

        return $this;
    }

    /**
     * @param $list
     *
     * @return bool
     */
    private function applyListRestriction(PublishersList $list): bool
    {
        return $list->name() === $this->listRestriction;
    }

    /**
     * @param $list
     *
     * @return bool
     */
    private function applyListRestrictionAmongOthers(PublishersList $list): bool
    {
        return array_key_exists(
            $list->name(),
            $this->listCollectionRestriction
        );
    }

    /**
     * @return bool
     */
    private function emptyListCollection(): bool
    {
        return count($this->listCollectionRestriction) === 0;
    }

    /**
     * @return string
     */
    private function forSpecificMember(): ?string
    {
        return $this->memberRestriction;
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
}