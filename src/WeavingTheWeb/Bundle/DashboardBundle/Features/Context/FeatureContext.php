<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Features\Context;

use Behat\Symfony2Extension\Context\KernelDictionary;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;

use Behat\MinkExtension\Context\MinkContext;

/**
 * Features context.
 */
class FeatureContext extends MinkContext
{
    use KernelDictionary;

    /** @BeforeScenario */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();

        foreach ($environment->getContexts() as $context) {
            if ($context instanceof \Behat\MinkExtension\Context\RawMinkContext) {
                $context->getSession('default')->setRequestHeader('Accept', 'text/html');

                $username = $this->kernel->getContainer()->getParameter('api_wtw_repositories_user_name_super');
                $password = $this->kernel->getContainer()->getParameter('api_wtw_repositories_password');

                $context->getSession('default')->setBasicAuth($username, $password);
            }
        }
    }
}
