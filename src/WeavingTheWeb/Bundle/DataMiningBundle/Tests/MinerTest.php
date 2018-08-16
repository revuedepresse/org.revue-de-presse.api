<?php

namespace WeavingTheWeb\Bundle\DataMiningBundle\Tests;

use WTW\CodeGeneration\QualityAssuranceBundle\Test\WebTestCase;

class MinerTest extends WebTestCase
{
    /**
     * @group requires-internet
     * @group data-mining
     * @group isolated-testing
     */
    public function testGetFeed()
    {
        /**
         * @var $miner \WeavingTheWeb\Bundle\DataMiningBundle\Miner
         */
        $miner = $this->get('weaving_the_web_data_mining.miner');
        $miner->setEndpoint('http://localhost');

        $responseMockBuilder = $this->getMockBuilder('\Symfony\Component\BrowserKit\Response');
        $responseMockBuilder->disableOriginalConstructor()->setMethods(['getContent', 'getStatus']);
        $responseMock = $responseMockBuilder->getMock();
        $responseMock ->expects($this->any())->method('getContent')
            ->will($this->returnValue(json_encode(['content' => 'response content'])));
        $responseMock ->expects($this->any())->method('getStatus')
            ->will($this->returnValue(200));

        $clientMockBuilder = $this->getMockBuilder('\Goutte\Client');
        $clientMockBuilder->disableOriginalConstructor()->setMethods(['request', 'getResponse']);
        $clientMock = $clientMockBuilder->getMock();
        $clientMock->expects($this->any())->method('getResponse')->will($this->returnValue($responseMock));
        $clientMock->expects($this->any())->method('request');

        $miner->setClient($clientMock);

        $feed  = $miner->getFeed();

        $decodedFeed = json_decode($feed, true);
        $json_error = json_last_error();

        if ($json_error === JSON_ERROR_NONE) {
            $this->assertInternalType('array', $decodedFeed);
            return;
        } elseif ($json_error === JSON_ERROR_SYNTAX) {
            $message = 'Please check json fixtures has been loaded (jsn_id = 295)';
        } else {
            $message = sprintf('JSON decoding error with code %d', $json_error);
        }

        throw new \Exception($message);
    }
}
