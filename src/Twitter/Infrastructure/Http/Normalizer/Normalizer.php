<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\Normalizer;

use App\Twitter\Infrastructure\Publication\Dto\TaggedTweet;
use App\Twitter\Infrastructure\Operation\Collection\Collection;
use App\Twitter\Domain\Operation\Collection\CollectionInterface;
use Closure;
use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use stdClass;
use function json_encode;
use function property_exists;
use function sha1;
use const JSON_THROW_ON_ERROR;

class Normalizer implements NormalizerInterface
{
    /**
     * @throws Exception
     */
    public static function normalizeStatusProperties(
        stdClass $properties,
        Closure $onFinish = null
    ): TaggedTweet {
        $text = $properties->full_text ?? $properties->text;

        $normalizedProperties = $onFinish(
            [
                'hash'         => sha1($text . $properties->id_str),
                'text'         => $text,
                'screen_name'  => $properties->user->screen_name,
                'name'         => $properties->user->name,
                'user_avatar'  => $properties->user->profile_image_url,
                'status_id'    => $properties->id_str,
                'api_document' => json_encode($properties, JSON_THROW_ON_ERROR),
                'created_at'   => new DateTime(
                    $properties->created_at
                ),
            ]
        );

        return TaggedTweet::fromLegacyProps($normalizedProperties);
    }

    /**
     * @param array           $statuses
     * @param callable        $setter
     * @param LoggerInterface $logger
     *
     * @return CollectionInterface
     */
    public static function normalizeAll(
        array $statuses,
        callable $setter,
        LoggerInterface $logger
    ): CollectionInterface {
        $normalizedStatusCollection = new Collection();

        foreach ($statuses as $status) {
            if (
                !property_exists($status, 'text')
                && !property_exists($status, 'full_text')
            ) {
                continue;
            }

            try {
                $normalizedStatusCollection[] = self::normalizeStatusProperties(
                    $status,
                    $setter
                );
            } catch (\Exception $exception) {
                $logger->error($exception->getMessage());
            }
        }

        return $normalizedStatusCollection;
    }

}