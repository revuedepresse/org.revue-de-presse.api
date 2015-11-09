<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Command;

use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class CreateOAuthClientCommand extends Command
{
    const OPTION_BASE_URL = 'base-url';

    /**
     * @var \Symfony\Component\Routing\Router
     */
    public $router;

    /**
     * @var \WeavingTheWeb\Bundle\ApiBundle\Repository\OAuth\ClientRepository
     */
    public $clientRepository;

    /**
     * @var \Symfony\Component\Translation\Translator
     */
    public $translator;

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('weaving-the-web:oauth:create-client')
            ->addOption(self::OPTION_BASE_URL, null, InputOption::VALUE_REQUIRED, 'Redirect URI')
            ->setDescription('Create OAuth client');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $baseUrl = $input->getOption(self::OPTION_BASE_URL);
        if (is_null($baseUrl)) {
            throw new \Exception('Invalid base URL');
        }

        $callbackUri = $this->router->generate('weaving_the_web_api_oauth_callback');
        $redirectUrl = $baseUrl . $callbackUri;

        $client = $this->clientRepository->make($redirectUrl);

        $successMessage = $this->translator->trans(
            'oauth.client.authorization_url',
            [
                '{{ authorizaton_url }}' => $client->getAuthorizationUrl(),
                '{{ client_id }}' => $client->getPublicId(),
                '{{ client_secret }}' => $client->getSecret(),
            ],
            'authorization'
        );
        $output->writeln($successMessage);

        $successMessage = $this->translator->trans('oauth.client.creation_success', [], 'authorization');
        $output->writeln($successMessage);
        $returnCode = 0;

        return $returnCode;
    }
}
