<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption;

use WeavingTheWeb\Bundle\ApiBundle\Entity\OAuth\Client;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class CreateOAuthClientCommand extends ContainerAwareCommand
{
    const OPTION_BASE_URL = 'base-url';

    /**
     * @var \Symfony\Component\Routing\Router
     */
    protected $router;

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('weaving-the-web:oauth:create-client')
            ->addOption(self::OPTION_BASE_URL, null, InputOption::VALUE_REQUIRED, 'Redirect URI')
            ->setDescription('Create OAuth client')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Symfony\Component\Routing\Router $router */
        $this->router = $this->getContainer()->get('router');

        /** @var \Symfony\Component\Translation\Translator $translator */
        $translator = $this->getContainer()->get('translator');

        /** @var \FOS\OAuthServerBundle\Entity\ClientManager $clientManager */
        $clientManager = $this->getContainer()->get('fos_oauth_server.client_manager.default');

        /** @var \WeavingTheWeb\Bundle\ApiBundle\Entity\OAuth\Client $client */
        $client = $clientManager->createClient();

        $baseUrl = $input->getOption(self::OPTION_BASE_URL);
        if (is_null($baseUrl)) {
            throw new \Exception('Invalid base URL');
        }

        $callbackUri = $this->router->generate('weaving_the_web_api_oauth_callback');
        $redirectUrl = $baseUrl . $callbackUri;

        $client->setRedirectUris(array($redirectUrl));
        $client->setAllowedGrantTypes(array('token', 'authorization_code'));
        $clientManager->updateClient($client);

        $authorizationUrl = $this->getAuthorizationUrl($redirectUrl, $client);

        $successMessage = $translator->trans(
            'oauth.client.authorization_url',
            [
                '{{ authorizaton_url }}' => $authorizationUrl,
                '{{ client_id }}' => $client->getPublicId(),
                '{{ client_secret }}' => $client->getSecret(),
            ], 'authorization'
        );
        $output->writeln($successMessage);

        $successMessage = $translator->trans('oauth.client.creation_success', [], 'authorization');
        $output->writeln($successMessage);
    }

    /**
     * @param $redirectUrl
     * @param Client $client
     * @return string
     */
    protected function getAuthorizationUrl($redirectUrl, Client $client)
    {
        return $this->router->generate(
            'fos_oauth_server_authorize',
            [
                'redirect_uri' => $redirectUrl,
                'client_id' => $client->getPublicId(),
                'response_type' => 'code'
            ]
        );
    }
}