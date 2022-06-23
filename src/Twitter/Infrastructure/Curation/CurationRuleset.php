<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Curation;

use App\Twitter\Domain\Curation\CurationRulesetInterface;
use App\Twitter\Infrastructure\Amqp\Exception\SkippableMemberException;
use App\Twitter\Domain\Resource\MemberIdentity;
use App\Twitter\Domain\Resource\PublishersList;
use App\Membership\Domain\Model\MemberInterface;
use App\Twitter\Infrastructure\Operation\Correlation\CorrelationIdAwareInterface;
use App\Twitter\Infrastructure\Operation\Correlation\CorrelationIdAwareTrait;
use App\Twitter\Infrastructure\Operation\Correlation\CorrelationIdInterface;
use function array_key_exists;
use function count;
use function sprintf;

class CurationRuleset implements CurationRulesetInterface, CorrelationIdAwareInterface
{
    use CorrelationIdAwareTrait;

    private int $curationCursor = -1;

    private ?string $tweetCreationDateFilter = null;

    private bool $filterByPublicationVolume = false;

    private bool $isListOwnerTweetsCurationActive = false;

    private array $listCollectionFilter = [];

    private ?string $singleListFilter = null;

    private ?string $singleMemberFilter = null;

    private ?string $queryRestriction = null;

    private string $screenName;

    public function __construct(CorrelationIdInterface $correlationId)
    {
        $this->correlationId = $correlationId;
    }

    public function tweetCreationDateFilter(): ?string
    {
        return $this->tweetCreationDateFilter;
    }

    public function curatingOnBehalfOfMemberHavingScreenName(string $screenName): CurationRulesetInterface
    {
        $this->screenName = $screenName;

        return $this;
    }

    public function singleListFilter(): string
    {
        return $this->singleListFilter;
    }

    public function filterByCurationCursor(int $curationCursor): CurationRulesetInterface
    {
        $this->curationCursor = $curationCursor;

        return $this;
    }

    public function skipLowVolumeTweetingMember(
        MemberInterface $member,
        MemberIdentity $memberIdentity
    ): void {
        return;

        if ($this->shouldIgnoreMemberWhenWhispering($member)) {
            throw new SkippableMemberException(
                sprintf(
                    'Ignoring whisperer with screen name "%s"',
                    $memberIdentity->screenName()
                )
            );
        }
    }

    public function isSingleListFilterActive(): bool
    {
        return !$this->isSingleListFilterInactive();
    }

    public function isSingleListFilterInactive(): bool
    {
        return $this->singleListFilter === null;
    }

    public function whoseListSubscriptionsAreCurated(): string
    {
        return $this->screenName;
    }

    public function isSingleMemberCurationActive(MemberIdentity $memberIdentity): bool
    {
        if ($this->isMultiMemberCuration()) {
            return false;
        }

        return $memberIdentity->screenName() !== $this->singleMemberFilter;
    }

    public function isCurationCursorActive(): ?int
    {
        return $this->curationCursor;
    }

    public function shouldIgnoreMemberWhenWhispering(MemberInterface $member): bool
    {
        return $this->isPublicationVolumeFilterActive() && $member->isLowVolumeTweetWriter();
    }

    public function isPublicationVolumeFilterActive(): bool
    {
        return $this->filterByPublicationVolume;
    }

    public function isListOwnerTweetsCurationActive(): bool
    {
        return $this->isListOwnerTweetsCurationActive;
    }

    public function isCurationByListActive(PublishersList $list): bool
    {
        return $this->isListRelatedFilterActive()
            || $this->isFilterByListActive($list)
            || $this->isFilterByListCollectionActive($list);
    }

    public function filterBySingleList(string $listRestriction): CurationRulesetInterface
    {
        $this->singleListFilter = $listRestriction;

        return $this;
    }

    public function filterByListCollection(array $listCollectionRestriction
    ): CurationRulesetInterface {
        $this->listCollectionFilter = $listCollectionRestriction;

        return $this;
    }

    public function filterByMember(string $memberName): CurationRulesetInterface
    {
        $this->singleMemberFilter = $memberName;

        return $this;
    }

    public function filterByTweetCreationDate(?string $tweetCreationDate): CurationRulesetInterface
    {
        $this->tweetCreationDateFilter = $tweetCreationDate;

        return $this;
    }

    public function filterByPublicationVolume(bool $isPublicationVolumeFilterActive): CurationRulesetInterface
    {
        $this->filterByPublicationVolume = $isPublicationVolumeFilterActive;

        return $this;
    }

    public function isListOwnerIncluded(bool $includeOwner): CurationRulesetInterface
    {
        $this->isListOwnerTweetsCurationActive = $includeOwner;

        return $this;
    }

    private function isFilterByListActive(PublishersList $list): bool
    {
        return $list->name() === $this->singleListFilter;
    }

    private function isFilterByListCollectionActive(PublishersList $list): bool
    {
        return array_key_exists(
            $list->name(),
            $this->listCollectionFilter
        );
    }

    private function isListCollectionEmpty(): bool
    {
        return count($this->listCollectionFilter) === 0;
    }

    private function isSingleMemberCuration(): bool
    {
        return $this->singleMemberFilter !== null;
    }

    private function isMultiMemberCuration(): bool
    {
        return $this->isSingleMemberCuration() === false;
    }

    private function isListRelatedFilterActive(): bool
    {
        return $this->isSingleListFilterInactive()
            && $this->isListCollectionEmpty();
    }
}