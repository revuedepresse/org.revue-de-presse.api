<?php

namespace App\Tests\Features\Bootstrap;

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;

class HighlightsContext implements Context
{
    public function __construct()
    {
    }

    /**
     * @Given publications have been collected from French press media accounts
     */
    public function publicationsHaveBeenCollectedFromFrenchPressMediaAccounts()
    {
        throw new PendingException();
    }

    /**
     * @When I access the daily press review
     */
    public function iAccessTheDailyPressReview()
    {
        throw new PendingException();
    }

    /**
     * @Then I can see highlights for today
     */
    public function iCanSeeHighlightsForToday()
    {
        throw new PendingException();
    }
}
