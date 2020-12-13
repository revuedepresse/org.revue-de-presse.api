<?php
declare(strict_types=1);

namespace App\Tests\Twitter\Domain\Status\Decorator;

use App\Twitter\Domain\Publication\Decorator\StatusDecorator;
use App\Twitter\Infrastructure\Publication\Exception\DocumentException;
use PHPUnit\Framework\TestCase;
use function json_encode;
use const JSON_THROW_ON_ERROR;

/**
 * @group status_decoration
 */
class StatusDecoratorTest extends TestCase
{
    private StatusDecorator $decorator;

    public function getInvalidStatus(): array
    {
        return [
            [
                [
                    [
                        'original_document' => '',
                        'id'                => 1
                    ]
                ],
                DocumentException::EXCEPTION_CODE_EMPTY_DOCUMENT,
                'It should throw an empty document exception'
            ],
            [
                [
                    [
                        'original_document' => json_encode(
                            [
                                'retweeted_status' => [
                                    'user' => [
                                        'screen_name' => 'maric'
                                    ]
                                ]
                            ],
                            JSON_THROW_ON_ERROR
                        ),
                        'id'                => 1
                    ]
                ],
                DocumentException::EXCEPTION_CODE_INVALID_PROPERTY,
                'It should throw an invalid property document exception'
            ],

        ];
    }

    /**
     * @dataProvider getInvalidStatus
     *
     * @param $invalidStatus
     * @param $expectedExceptionCode
     * @param $failureMessage
     *
     * @test
     */
    public function it_can_not_decorate_a_status(
        $invalidStatus,
        $expectedExceptionCode,
        $failureMessage
    ): void {
        try {
            StatusDecorator::decorateStatus(
                $invalidStatus
            );
        } catch (DocumentException $exception) {
            self::assertEquals(
                $expectedExceptionCode,
                $exception->getCode()
            );

            return;
        }

        self::fail($failureMessage);
    }

    /**
     * @test
     *
     * @throws
     */
    public function it_should_decorate_a_status(): void
    {
        $decoratedStatus = StatusDecorator::decorateStatus(
            [
                [
                    'original_document' => json_encode(
                        [
                            'retweeted_status' => [
                                'user'      => [
                                    'screen_name' => 'pierrec',
                                ],
                                'full_text' => 'This is a long publication.',
                            ]
                        ],
                        JSON_THROW_ON_ERROR
                    )
                ]
            ]
        );

        self::assertCount(1, $decoratedStatus);
        self::assertArrayHasKey('text', $decoratedStatus[0]);
        self::assertEquals(
            'RT @pierrec: This is a long publication.',
            $decoratedStatus[0]['text']
        );
    }
}
