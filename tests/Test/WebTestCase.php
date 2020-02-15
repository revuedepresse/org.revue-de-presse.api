<?php

namespace App\Test;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
abstract class WebTestCase extends CommandTestCase
{
    /**
     * @var \_Kernel
     */
    protected static $kernel;

    protected static $container;

    protected static $options = [];

    /**
     * @var $client Client
     */
    protected $client;

    public static function setUpBeforeClass()
    {
        if ((count(static::$options) > 0) && isset(static::$options['environment'])) {
            $options['environment'] = static::$options['environment'];
        } else {
            $options['environment'] = 'test';
        }

        if (null !== static::$kernel) {
            static::$kernel->shutdown();
        }

        self::$kernel = self::createKernel($options);
        self::$kernel->boot();
        self::$container = self::$kernel->getContainer();
    }

    public static function tearDownAfterClass()
    {
        if (null !== static::$kernel) {
            static::$kernel->shutdown();
        }
    }

    /**
     * @param $name
     * @param $value
     */
    public static function setOption($name, $value)
    {
        static::$options[$name] = $value;
    }

    public function detachSessionFromCookies()
    {
        if ('' !== session_id()) {
            session_destroy();
        }

        ini_set('session.use_only_cookies', false);
        ini_set('session.use_cookies', false);
        ini_set('session.use_trans_sid', false);
        ini_set('session.cache_limiter', null);
    }

    /**
     * @param $expectedCode
     * @param $message
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function assertResponseStatusCodeEquals($expectedCode, $message = '')
    {
        /**
         * @var \Symfony\Component\HttpFoundation\Response $response
         */
        $response = $this->client->getResponse();
        $statusCode = $response->getStatusCode();

        $crawler = $this->client->getCrawler();
        $title = $crawler->filter('title');

        if (count($title) > 0) {
            $outputMessage = $message . ' (' . trim($title->text()) . ')';
        } elseif ($statusCode === 500) {
            $outputMessage = $this->parseErrorMessage($response);
        } else {
            $outputMessage = $message;
        }

        $this->assertEquals($expectedCode, $statusCode, $outputMessage);

        return $response;
    }

    public function requiredFixtures()
    {
        return true;
    }

    /**
     * @param $response
     * @return mixed
     */
    protected function parseErrorMessage(Response $response)
    {
        $content = $response->getContent();
        $outputMessage = '';

        if (strlen(trim($content)) > 0) {
            $decodedJson = json_decode($response->getContent(), true);
            if (json_last_error() === JSON_ERROR_NONE && array_key_exists('error', $decodedJson)) {
                $outputMessage = $decodedJson['error'];
            }
        }

        return $outputMessage;
    }

    /**
     * @param $subject
     * @param array $translation
     * @param string $message
     */
    protected function assertContainsTranslation($subject, array $translation, $message = '')
    {
        if (!array_key_exists('parameters', $translation)) {
            $translation['parameters'] = [];
        }

        if (!array_key_exists('locale', $translation)) {
            $translation['locale'] = null;
        }

        /** @var \Symfony\Component\Translation\Translator $translator */
        $translator = $this->get('translator');
        $failureMessage = $translator->trans(
            $translation['key'],
            $translation['parameters'],
            $translation['dictionary'],
            $translation['locale']
        );

        $defaultMessage = 'It should display a translated text.';
        if (empty($message)) {
            $message = $defaultMessage;
        }

        $this->assertNotContains($translation['key'], $subject, 'It should not output a translation key');
        $this->assertContains($failureMessage, $subject, $message);
    }
}
