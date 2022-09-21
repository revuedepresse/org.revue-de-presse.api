<?php
declare(strict_types=1);

namespace App\Search\Infrastructure\DependencyInjection;

use App\Search\Domain\Ruleset\SearchQueryRulesetProcessorAwareInterface;
use App\Search\Domain\Ruleset\SearchQueryRulesetProcessorInterface;

trait SearchQueryProcessorTrait
{
    private SearchQueryRulesetProcessorInterface $searchQueryProcessor;

    public function setSearchQueryRulesetProcessor(
        SearchQueryRulesetProcessorInterface $searchQueryProcessor
    ): SearchQueryRulesetProcessorAwareInterface {
        $this->searchQueryProcessor = $searchQueryProcessor;

        return $this;
    }
}
