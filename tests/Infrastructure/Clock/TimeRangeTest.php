<?php

namespace App\Tests\Infrastructure\Clock;

use App\Twitter\Domain\Curation\Entity\NullStatus;
use App\Twitter\Infrastructure\Publication\Entity\PublishersList;
use App\Twitter\Infrastructure\PublishersList\Entity\TimelyStatus;
use PHPUnit\Framework\TestCase;

/**
 * @group time_range
 */
class TimeRangeTest extends TestCase
{
    /**
     * @test
     */
    public function it_declares_time_as_belonging_to_first_time_range()
    {
        $seconds = 300 - 1; // 5 minutes ago - 1 second
        $publicationDate = new \DateTime("now - {$seconds} seconds", new \DateTimeZone('UTC'));
        $status = new NullStatus();
        $status->setCreatedAt($publicationDate);
        $timelyStatus = new TimelyStatus(
            $status,
            new PublishersList('test', 'test'),
            $publicationDate
        );

        // Assert
        $expectedTimeRange = 0;
        self::assertEquals(
            $expectedTimeRange,
            $timelyStatus->timeRange(), 
            'The range code should be '.$expectedTimeRange
        );
    }
    
    /**
     * @test
     */
    public function it_declares_time_as_belonging_to_second_time_range()
    {
        $seconds = 300; // 5 minutes ago
        $publicationDate = new \DateTime("now - {$seconds} seconds", new \DateTimeZone('UTC'));
        $status = new NullStatus();
        $status->setCreatedAt($publicationDate);
        $timelyStatus = new TimelyStatus(
            $status,
            new PublishersList('test', 'test'),
            $publicationDate
        );

        // Assert
        $expectedTimeRange = 1;
        self::assertEquals(
            $expectedTimeRange,
            $timelyStatus->timeRange(), 
            'The range code should be '.$expectedTimeRange
        );
    }

    /**
     * @test
     */
    public function it_declares_time_as_belonging_to_second_time_range_bis()
    {
        $seconds = 600 - 1; // 10 min ago - 1 second
        $publicationDate = new \DateTime("now - {$seconds} seconds", new \DateTimeZone('UTC'));
        $status = new NullStatus();
        $status->setCreatedAt($publicationDate);
        $timelyStatus = new TimelyStatus(
            $status,
            new PublishersList('test', 'test'),
            $publicationDate
        );
        $expectedTimeRange = 1;
        self::assertEquals(
            $expectedTimeRange,
            $timelyStatus->timeRange(), 
            'The range code should be '.$expectedTimeRange
        );
    }
    
    /**
     * @test
     */
    public function it_declares_time_as_belonging_to_third_time_range()
    {
        $seconds = 600; // 10 min ago
        $publicationDate = new \DateTime("now - {$seconds} seconds", new \DateTimeZone('UTC'));
        $status = new NullStatus();
        $status->setCreatedAt($publicationDate);
        $timelyStatus = new TimelyStatus(
            $status,
            new PublishersList('test', 'test'),
            $publicationDate
        );
        $expectedTimeRange = 2;
        self::assertEquals(
            $expectedTimeRange,
            $timelyStatus->timeRange(), 
            'The range code should be '.$expectedTimeRange
        );
    }
    
    /**
     * @test
     */
    public function it_declares_time_as_belonging_to_third_time_range_bis()
    {
        $seconds = 1800 - 1; // 30 min ago - 1 second
        $publicationDate = new \DateTime("now - {$seconds} seconds", new \DateTimeZone('UTC'));
        $status = new NullStatus();
        $status->setCreatedAt($publicationDate);
        $timelyStatus = new TimelyStatus(
            $status,
            new PublishersList('test', 'test'),
            $publicationDate
        );
        $expectedTimeRange = 2;
        self::assertEquals(
            $expectedTimeRange,
            $timelyStatus->timeRange(), 
            'The range code should be '.$expectedTimeRange
        );
    }
    
    /**
     * @test
     */
    public function it_declares_time_as_belonging_to_fourth_time_range()
    {
        $seconds = 1800; // 30 min ago
        $publicationDate = new \DateTime("now - {$seconds} seconds", new \DateTimeZone('UTC'));
        $status = new NullStatus();
        $status->setCreatedAt($publicationDate);
        $timelyStatus = new TimelyStatus(
            $status,
            new PublishersList('test', 'test'),
            $publicationDate
        );
        $expectedTimeRange = 3;
        self::assertEquals(
            $expectedTimeRange,
            $timelyStatus->timeRange(), 
            'The range code should be '.$expectedTimeRange
        );
    }
    
    /**
     * @test
     */
    public function it_declares_time_as_belonging_to_fourth_time_range_bis()
    {
        $seconds = 86400 - 1; // 24 hours ago - 1 second
        $publicationDate = new \DateTime("now - {$seconds} seconds", new \DateTimeZone('UTC'));
        $status = new NullStatus();
        $status->setCreatedAt($publicationDate);
        $timelyStatus = new TimelyStatus(
            $status,
            new PublishersList('test', 'test'),
            $publicationDate
        );
        $expectedTimeRange = 3;
        self::assertEquals(
            $expectedTimeRange,
            $timelyStatus->timeRange(), 
            'The range code should be '.$expectedTimeRange
        );
    }
    
    /**
     * @test
     */
    public function it_declares_time_as_belonging_to_fifth_time_range()
    {
        $seconds = 604800 - 1; // 1 week ago - 1 second
        $publicationDate = new \DateTime("now - {$seconds} seconds", new \DateTimeZone('UTC'));
        $status = new NullStatus();
        $status->setCreatedAt($publicationDate);
        $timelyStatus = new TimelyStatus(
            $status,
            new PublishersList('test', 'test'),
            $publicationDate
        );
        $expectedTimeRange = 4;
        self::assertEquals(
            $expectedTimeRange,
            $timelyStatus->timeRange(), 
            'The range code should be '.$expectedTimeRange
        );
    }
    
    /**
     * @test
     */
    public function it_declares_time_as_belonging_to_sixth_time_range()
    {
        $seconds = 604800; // 1 week ago
        $publicationDate = new \DateTime("now - {$seconds} seconds", new \DateTimeZone('UTC'));
        $status = new NullStatus();
        $status->setCreatedAt($publicationDate);
        $timelyStatus = new TimelyStatus(
            $status,
            new PublishersList('test', 'test'),
            $publicationDate
        );
        $expectedTimeRange = 5;
        self::assertEquals(
            $expectedTimeRange,
            $timelyStatus->timeRange(), 
            'The range code should be '.$expectedTimeRange
        );
    }

}
