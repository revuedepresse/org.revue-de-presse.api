<?php

namespace WTW\API\GithubBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Starred repositories serialization command
 *
 * @author Thierry Marianne <thierrym@theodo.fr>
 */
class SerializeStarredRepositoriesCommand extends ContainerAwareCommand
{
    protected $client;

    /**
     * Configures executable commands
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('wtw:api:github:starred:serialize')
            ->setDescription('Serialize response returned when accessing starred repositories')
            ->setAliases(array('wtw:api:gth:str'));
    }

    /**
     * Logs performance metrics to server logs and compile them
     *
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $client = $this->setUpClient();

        $users     = json_decode($client->getFollowedUsers(), true);
        $lastError = json_last_error();

        if (JSON_ERROR_NONE === $lastError) {
            $this->getUsersStarredRepositories($users, $client, $output);
        } else {
            throw new \Exception('JSON decoding failure (error code: ' . $lastError . ')');
        }
    }

    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @return object
     */
    protected function setUpClient()
    {
        if (is_null($this->client)) {
            $container = $this->getContainer();
            $client = $container->get('api.github.client');
        } else {
            $client = $this->client;
        }

        return $client;
    }

    /**
     * Get repositories starred by a user
     *
     * @param $user
     * @param $client
     *
     * @return string
     */
    protected function getUserStarredRepositories($user, $client)
    {
        $starredRepositories = json_decode($client->getStarredRepositories($user, true));
        $data                = array(
            'user'      => $user,
            'data'      => $starredRepositories,
            'data_type' => 'starred_repositories'
        );

        return json_encode($data);
    }

    /**
     * Get repositories starred by multiple users
     *
     * @param $users
     * @param $client
     * @param $output
     */
    protected function getUsersStarredRepositories($users, $client, $output)
    {

        foreach ($users as $user) {
            $encodedData = $this->getUserStarredRepositories($user['login'], $client);
            $client->saveRepositories($encodedData);
            $serializationSuccessMessage = $this->getSerializationSuccessMessage($user);
            $output->writeln($serializationSuccessMessage);
        }
    }

    /**
     * @param $user
     *
     * @return string
     */
    protected function getSerializationSuccessMessage($user)
    {
        $translator = $this->getContainer()->get('translator');
        $serializationSuccessMessage = '[' . date('Y-m-d H:i') . '] ' .
            $translator->trans(
                'repositories_serialization_success',
                array('{{ user }}' => $user['login'])
            );

        return $serializationSuccessMessage;
    }
}
