<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\InputConverter;

use App\Twitter\Infrastructure\Curation\CurationStrategy;
use App\Twitter\Domain\Curation\CurationStrategyInterface;
use App\Twitter\Infrastructure\Operation\Correlation\CorrelationIdAwareCommandTrait;
use Symfony\Component\Console\Input\InputInterface;
use function array_walk;
use function explode;

class InputToCollectionStrategy
{
    use CorrelationIdAwareCommandTrait;

    public static function convertInputToCollectionStrategy(
        InputInterface $input
    ): CurationStrategyInterface {
        $strategy = new CurationStrategy(self::convertInputOptionIntoCorrelationId($input));

        $strategy->forMemberHavingScreenName(self::screenName($input));

        self::listRestriction($input, $strategy);
        self::listCollectionRestriction($input, $strategy);
        self::collectionSchedule($input, $strategy);
        self::memberRestriction($input, $strategy);
        self::ignoreWhispers($input, $strategy);
        self::includeOwner($input, $strategy);
        self::fromCursor($input, $strategy);

        return $strategy;
    }

    /**
     * @param InputInterface $input
     *
     * @return string
     */
    protected static function screenName(
        InputInterface $input
    ): string {
        return $input->getArgument(CurationStrategyInterface::RULE_SCREEN_NAME);
    }

    /**
     * @param InputInterface               $input
     * @param CurationStrategyInterface $strategy
     */
    private static function listRestriction(
        InputInterface $input,
        CurationStrategyInterface $strategy
    ): void {
        if (
            $input->hasOption(CurationStrategyInterface::RULE_LIST)
            && $input->getOption(CurationStrategyInterface::RULE_LIST) !== null
        ) {
            $strategy->willApplyListRestrictionToAList($input->getOption(CurationStrategyInterface::RULE_LIST));
        }
    }

    /**
     * @param InputInterface               $input
     * @param CurationStrategyInterface $strategy
     */
    private static function listCollectionRestriction(
        InputInterface $input,
        CurationStrategyInterface $strategy
    ): void {
        if ($input->hasOption(CurationStrategyInterface::RULE_LISTS) && $input->getOption(CurationStrategyInterface::RULE_LISTS) !== null) {
            $listCollectionRestriction = explode(
                ',',
                $input->getOption(CurationStrategyInterface::RULE_LISTS)
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
            $strategy->willApplyRestrictionToAListCollection($listCollectionRestriction);
        }
    }

    /**
     * @param InputInterface               $input
     * @param CurationStrategyInterface $strategy
     */
    private static function collectionSchedule(
        InputInterface $input,
        CurationStrategyInterface $strategy
    ): void {
        if (
            $input->hasOption(CurationStrategyInterface::RULE_BEFORE)
            && $input->getOption(CurationStrategyInterface::RULE_BEFORE) !== null
        ) {
            $strategy->willCollectPublicationsPreceding(
                $input->getOption(CurationStrategyInterface::RULE_BEFORE)
            );
        }
    }

    /**
     * @param InputInterface               $input
     * @param CurationStrategyInterface $strategy
     */
    private static function memberRestriction(
        InputInterface $input,
        CurationStrategyInterface $strategy
    ): void {
        if (
            $input->hasOption(CurationStrategyInterface::RULE_FILTER_BY_TWEET_OWNER_USERNAME)
            && $input->getOption(
                CurationStrategyInterface::RULE_FILTER_BY_TWEET_OWNER_USERNAME
            )
        ) {
            $strategy->willFilterByMember($input->getOption(CurationStrategyInterface::RULE_FILTER_BY_TWEET_OWNER_USERNAME));
        }
    }

    /**
     * @param InputInterface               $input
     * @param CurationStrategyInterface $strategy
     */
    private static function ignoreWhispers(
        InputInterface $input,
        CurationStrategyInterface $strategy
    ): void {
        if (
            $input->hasOption(CurationStrategyInterface::RULE_IGNORE_WHISPERS)
            && $input->getOption(
                CurationStrategyInterface::RULE_IGNORE_WHISPERS
            )
        ) {
            $strategy->willIgnoreWhispers($input->getOption(CurationStrategyInterface::RULE_IGNORE_WHISPERS));
        }
    }

    /**
     * @param InputInterface               $input
     * @param CurationStrategyInterface $strategy
     */
    private static function includeOwner(
        InputInterface $input,
        CurationStrategyInterface $strategy
    ): void {
        if (
            $input->hasOption(CurationStrategyInterface::RULE_INCLUDE_OWNER)
            && $input->getOption(CurationStrategyInterface::RULE_INCLUDE_OWNER)
        ) {
            $strategy->willIncludeOwner($input->getOption(CurationStrategyInterface::RULE_INCLUDE_OWNER));
        }
    }

    private static function fromCursor(InputInterface $input, CurationStrategyInterface $strategy): void
    {
        if (
            $input->hasOption(CurationStrategyInterface::RULE_CURSOR) &&
            $input->getOption(CurationStrategyInterface::RULE_CURSOR)
        ) {
            $strategy->fromCursor(
                (int) $input->getOption(CurationStrategyInterface::RULE_CURSOR)
            );
        }
    }
}