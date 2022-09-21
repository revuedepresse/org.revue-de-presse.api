<?php
declare(strict_types=1);

namespace App\Search\Domain\Ruleset;

interface SearchQueryRulesetProcessorAwareInterface
{
    public function setSearchQueryRulesetProcessor(SearchQueryRulesetProcessorInterface $searchQueryProcessor);
}
