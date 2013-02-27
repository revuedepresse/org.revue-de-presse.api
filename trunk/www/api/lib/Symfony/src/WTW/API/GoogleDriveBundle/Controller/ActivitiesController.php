<?php

namespace WTW\API\GoogleDriveBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class ActivitiesController extends Controller
{
    protected $apiKey;

    protected $clientId;

    protected $developerKey;

    protected $clientSecret;

    protected $libraryDir;

    protected $oauthRedirectUri;

    public function setLibraryDir()
    {
        $this->libraryDir = $this->container->getParameter('kernel.root_dir') .
            '/../../';
    }

    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }

    protected function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;
    }

    protected function setDeveloperKey($developerKey)
    {
        $this->developerKey = $developerKey;
    }

    protected function setLibrary($library)
    {
        $this->library = $library;
    }

    protected function setOauthRedirectUri($oauthRedirectUri)
    {
        $this->oauthRedirectUri = $oauthRedirectUri;
    }

    /**
     * @Route("/api/google/drive", name="api_google_drive")
     * @Template()
     */
    public function showActivitiesAction($code = null)
    {
        $this->importDependencies();
        $this->setApplicationSettings();

        $client = $this->getConfiguredClient();

        $this->authenticateClient($code, $client);
        $this->updateAccessToken($client);
        $activities = $this->showActivities($client);

        return array('activities' => $activities);
    }

    public function getConfiguredClient()
    {
        if (session_id() === '') {
            // Set your cached access token. Remember to replace $_SESSION with a
            // real database or memcached.
            session_start();
        }

        $client = new \Google_Client();
        $client->setApplicationName('Google+ PHP Starter Application');
        // Visit https://code.google.com/apis/console?api=plus to generate your
        // client id, client secret, and to register your redirect uri.
        $client->setClientId($this->clientId);
        $client->setClientSecret($this->clientSecret);
        $redirectUri = $this->oauthRedirectUri;

        $client->setRedirectUri($redirectUri);
        $client->setDeveloperKey($this->apiKey);

        return $client;
    }

    public function authenticateClient($code, $client)
    {
        if (isset($_GET['code']) || isset($code)) {
            $client->authenticate();
            $_SESSION['token'] = $client->getAccessToken();
            $redirect          = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
            header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
        }
    }

    public function updateAccessToken($client)
    {
        if (isset($_SESSION['token'])) {
            $client->setAccessToken($_SESSION['token']);
        }
    }

    public function showActivities($client)
    {
        if ($client->getAccessToken()) {
            $plus = new \Google_PlusService($client);
            $activities = $plus->activities->listActivities('me', 'public');
            $activities = json_encode($activities);

            // We're not done yet. Remember to update the cached access token.
            // Remember to replace $_SESSION with a real database or memcached.
            $_SESSION['token'] = $client->getAccessToken();
        } else {
            $authUrl = $client->createAuthUrl();
            $activities = "<a href='$authUrl'>Connect Me!</a>";
        }

        return $activities;
    }

    public function setApplicationSettings()
    {
        $this->setApiKey($this->container->getParameter('api_google_drive_api_key'));
        $this->setClientId($this->container->getParameter('api_google_drive_client_id'));
        $this->setClientSecret($this->container->getParameter('api_google_drive_client_secret'));
        $this->setOauthRedirectUri($this->container->getParameter('api_google_drive_redirect_uri'));
    }

    public function importDependencies()
    {
        $this->setLibraryDir();

        require_once $this->libraryDir . '/google-api-php-client/src/Google_Client.php';
        require_once $this->libraryDir . '/google-api-php-client/src/contrib/Google_PlusService.php';
    }
}