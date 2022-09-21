<?php
declare(strict_types=1);

namespace App\Search\Domain\Ruleset;

use App\Twitter\Domain\Curation\CurationRulesetInterface;
use App\Twitter\Domain\Http\Model\TokenInterface;

interface SearchQueryRulesetProcessorInterface
{
    public function processSearchQuery(CurationRulesetInterface $ruleset, TokenInterface $token);
}
