<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\InputConverter;

use App\Twitter\Infrastructure\Curation\CurationRuleset;
use App\Twitter\Domain\Curation\CurationRulesetInterface;
use App\Twitter\Infrastructure\Operation\Correlation\CorrelationIdAwareCommandTrait;
use Symfony\Component\Console\Input\InputInterface;
use function array_walk;
use function explode;

class InputToCurationRuleset
{
    use CorrelationIdAwareCommandTrait;

    public static function convertInputToCurationRuleset(
        InputInterface $input
    ): CurationRulesetInterface {
        $ruleset = new CurationRuleset(self::convertInputOptionIntoCorrelationId($input));

        $ruleset->curatingOnBehalfOfMemberHavingScreenName(self::memberScreenName($input));

        self::collectionSchedule($input, $ruleset);
        self::fromCursor($input, $ruleset);
        self::filterByList($input, $ruleset);
        self::filterByListCollection($input, $ruleset);
        self::filterBySearchByQuery($input, $ruleset);
        self::filterByTweetOwnerUsername($input, $ruleset);
        self::ignoreWhispers($input, $ruleset);
        self::includeListOwnerTweets($input, $ruleset);

        return $ruleset;
    }

    protected static function memberScreenName(
        InputInterface $input
    ): string {
        return $input->getArgument(CurationRulesetInterface::RULE_SCREEN_NAME);
    }

    private static function filterByList(
        InputInterface $input,
        CurationRulesetInterface $ruleset
    ): void {
        if (
            $input->hasOption(CurationRulesetInterface::RULE_LIST)
            && $input->getOption(CurationRulesetInterface::RULE_LIST) !== null
        ) {
            $ruleset->filterBySingleList($input->getOption(CurationRulesetInterface::RULE_LIST));
        }
    }

    private static function filterByListCollection(
        InputInterface $input,
        CurationRulesetInterface $ruleset
    ): void {
        if ($input->hasOption(CurationRulesetInterface::RULE_LISTS) && $input->getOption(CurationRulesetInterface::RULE_LISTS) !== null) {
            $listCollectionRestriction = explode(
                ',',
                $input->getOption(CurationRulesetInterface::RULE_LISTS)
            );

            $restiction       = (object) [];
            $restiction->list = [];
            array_walk(
                $listCollectionRestriction,
                static function ($list) use ($restiction) {
                    $restiction->list[$list] = $list;
                }
            );
            $listCollectionRestriction = $restiction->list;
            $ruleset->filterByListCollection($listCollectionRestriction);
        }
    }

    private static function collectionSchedule(
        InputInterface $input,
        CurationRulesetInterface $ruleset
    ): void {
        if (
            $input->hasOption(CurationRulesetInterface::RULE_BEFORE)
            && $input->getOption(CurationRulesetInterface::RULE_BEFORE) !== null
        ) {
            $ruleset->filterByTweetCreationDate(
                $input->getOption(CurationRulesetInterface::RULE_BEFORE)
            );
        }
    }

    private static function filterBySearchByQuery(InputInterface $input, CurationRuleset $ruleset)
    {
        if (
            $input->hasOption(CurationRulesetInterface::RULE_SEARCH_QUERY) &&
            $input->getOption(CurationRulesetInterface::RULE_SEARCH_QUERY)
        ) {
            $ruleset->filterBySearchQuery(
                trim(
                    $input->getOption(CurationRulesetInterface::RULE_SEARCH_QUERY)
                )
            );
        }
    }

    private static function filterByTweetOwnerUsername(
        InputInterface $input,
        CurationRulesetInterface $ruleset
    ): void {
        if (
            $input->hasOption(CurationRulesetInterface::RULE_FILTER_BY_TWEET_OWNER_USERNAME)
            && $input->getOption(
                CurationRulesetInterface::RULE_FILTER_BY_TWEET_OWNER_USERNAME
            )
        ) {
            $ruleset->filterByMember($input->getOption(CurationRulesetInterface::RULE_FILTER_BY_TWEET_OWNER_USERNAME));
        }
    }

    private static function ignoreWhispers(
        InputInterface $input,
        CurationRulesetInterface $ruleset
    ): void {
        if (
            $input->hasOption(CurationRulesetInterface::RULE_IGNORE_WHISPERS)
            && $input->getOption(
                CurationRulesetInterface::RULE_IGNORE_WHISPERS
            )
        ) {
            $ruleset->filterByPublicationVolume($input->getOption(CurationRulesetInterface::RULE_IGNORE_WHISPERS));
        }
    }

    private static function includeListOwnerTweets(
        InputInterface $input,
        CurationRulesetInterface $ruleset
    ): void {
        if (
            $input->hasOption(CurationRulesetInterface::RULE_INCLUDE_OWNER)
            && $input->getOption(CurationRulesetInterface::RULE_INCLUDE_OWNER)
        ) {
            $ruleset->isListOwnerIncluded($input->getOption(CurationRulesetInterface::RULE_INCLUDE_OWNER));
        }
    }

    private static function fromCursor(
        InputInterface $input,
        CurationRulesetInterface $ruleset
    ): void
    {
        if (
            $input->hasOption(CurationRulesetInterface::RULE_CURSOR) &&
            $input->getOption(CurationRulesetInterface::RULE_CURSOR)
        ) {
            $ruleset->filterByCurationCursor(
                (int) $input->getOption(CurationRulesetInterface::RULE_CURSOR)
            );
        }
    }
}
