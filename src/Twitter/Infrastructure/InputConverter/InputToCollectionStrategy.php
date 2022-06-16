<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\InputConverter;

use App\Twitter\Infrastructure\Curation\PublicationStrategy;
use App\Twitter\Domain\Curation\PublicationStrategyInterface;
use App\Twitter\Infrastructure\Operation\Correlation\CorrelationIdAwareCommandTrait;
use Symfony\Component\Console\Input\InputInterface;
use function array_walk;
use function explode;

class InputToCollectionStrategy
{
    use CorrelationIdAwareCommandTrait;

    public static function convertInputToCollectionStrategy(
        InputInterface $input
    ): PublicationStrategyInterface {
        $strategy = new PublicationStrategy(self::convertInputOptionIntoCorrelationId($input));

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
        return $input->getArgument(PublicationStrategyInterface::RULE_SCREEN_NAME);
    }

    /**
     * @param InputInterface               $input
     * @param PublicationStrategyInterface $strategy
     */
    private static function listRestriction(
        InputInterface $input,
        PublicationStrategyInterface $strategy
    ): void {
        if (
            $input->hasOption(PublicationStrategyInterface::RULE_LIST)
            && $input->getOption(PublicationStrategyInterface::RULE_LIST) !== null
        ) {
            $strategy->willApplyListRestrictionToAList($input->getOption(PublicationStrategyInterface::RULE_LIST));
        }
    }

    /**
     * @param InputInterface               $input
     * @param PublicationStrategyInterface $strategy
     */
    private static function listCollectionRestriction(
        InputInterface $input,
        PublicationStrategyInterface $strategy
    ): void {
        if ($input->hasOption(PublicationStrategyInterface::RULE_LISTS) && $input->getOption(PublicationStrategyInterface::RULE_LISTS) !== null) {
            $listCollectionRestriction = explode(
                ',',
                $input->getOption(PublicationStrategyInterface::RULE_LISTS)
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
     * @param PublicationStrategyInterface $strategy
     */
    private static function collectionSchedule(
        InputInterface $input,
        PublicationStrategyInterface $strategy
    ): void {
        if (
            $input->hasOption(PublicationStrategyInterface::RULE_BEFORE)
            && $input->getOption(PublicationStrategyInterface::RULE_BEFORE) !== null
        ) {
            $strategy->willCollectPublicationsPreceding(
                $input->getOption(PublicationStrategyInterface::RULE_BEFORE)
            );
        }
    }

    /**
     * @param InputInterface               $input
     * @param PublicationStrategyInterface $strategy
     */
    private static function memberRestriction(
        InputInterface $input,
        PublicationStrategyInterface $strategy
    ): void {
        if (
            $input->hasOption(PublicationStrategyInterface::RULE_MEMBER_RESTRICTION)
            && $input->getOption(
                PublicationStrategyInterface::RULE_MEMBER_RESTRICTION
            )
        ) {
            $strategy->willApplyRestrictionToAMember($input->getOption(PublicationStrategyInterface::RULE_MEMBER_RESTRICTION));
        }
    }

    /**
     * @param InputInterface               $input
     * @param PublicationStrategyInterface $strategy
     */
    private static function ignoreWhispers(
        InputInterface $input,
        PublicationStrategyInterface $strategy
    ): void {
        if (
            $input->hasOption(PublicationStrategyInterface::RULE_IGNORE_WHISPERS)
            && $input->getOption(
                PublicationStrategyInterface::RULE_IGNORE_WHISPERS
            )
        ) {
            $strategy->willIgnoreWhispers($input->getOption(PublicationStrategyInterface::RULE_IGNORE_WHISPERS));
        }
    }

    /**
     * @param InputInterface               $input
     * @param PublicationStrategyInterface $strategy
     */
    private static function includeOwner(
        InputInterface $input,
        PublicationStrategyInterface $strategy
    ): void {
        if (
            $input->hasOption(PublicationStrategyInterface::RULE_INCLUDE_OWNER)
            && $input->getOption(PublicationStrategyInterface::RULE_INCLUDE_OWNER)
        ) {
            $strategy->willIncludeOwner($input->getOption(PublicationStrategyInterface::RULE_INCLUDE_OWNER));
        }
    }

    private static function fromCursor(InputInterface $input, PublicationStrategyInterface $strategy): void
    {
        if (
            $input->hasOption(PublicationStrategyInterface::RULE_CURSOR) &&
            $input->getOption(PublicationStrategyInterface::RULE_CURSOR)
        ) {
            $strategy->fromCursor(
                (int) $input->getOption(PublicationStrategyInterface::RULE_CURSOR)
            );
        }
    }
}