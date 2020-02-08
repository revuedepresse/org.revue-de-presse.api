<?php

namespace WeavingTheWeb\Bundle\TwitterBundle\Tests\Command;

use WTW\CodeGeneration\QualityAssuranceBundle\Test\CommandTestCase;

/**
 * Class AuthenticateApplicationCommandTest
 * @package WeavingTheWeb\Bundle\TwitterBundle\Tests\Command
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class AuthenticateApplicationCommandTest extends CommandTestCase
{
    /**
     * @group requires-internet
     * @group cli-twitter
     * @group messaging-twitter
     * @group twitter
     */
    public function testExecute()
    {
        $this->client = $this->getClient();

        $this->commandClass = $this->getParameter('weaving_the_web_twitter.authenticate_application.class');
        $this->setUpApplication();

        $this->commandTester = $this->getCommandTester('wtw:tw:auth');
        $this->commandTester->execute(array('command' => $this->getCommandName()));

        $commandDisplay = $this->commandTester->getDisplay();

        /**
         * @var \Symfony\Component\Translation\Translator $translator
         */
        $translator = $this->get('translator');
        $successMessage = $translator->trans('twitter.success.authentication', [
                '{{ consumer_key }}' => $this->getParameter('weaving_the_web_twitter.consumer_key')
            ]);

        $this->assertContains($successMessage, $commandDisplay);
    }
}
