<?php

namespace App\Tests;

use WTW\CodeGeneration\QualityAssuranceBundle\Test\WebTestCase;

abstract class StatusConsumptionTestCase extends WebTestCase
{
    /**
     * @var $accessor \WeavingTheWeb\Bundle\TwitterBundle\Api\Accessor
     */
    protected $accessor;

    public static function setUpBeforeClass()
    {
        self::setOption('environment', 'test');

        parent::setUpBeforeClass();
    }

    public function setUp()
    {
        parent::setUp();

        $this->client = $this->getClient();
        $this->accessor = $this->get('weaving_the_web_twitter.api_accessor');
    }
}
