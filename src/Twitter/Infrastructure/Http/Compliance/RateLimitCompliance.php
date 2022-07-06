<?php
declare(strict_types=1);

<<<<<<<< HEAD:src/Twitter/Infrastructure/Api/Moderator/ApiLimitModerator.php
namespace App\Twitter\Infrastructure\Api\Moderator;
|||||||| 3ea9802d:src/WeavingTheWeb/Bundle/ApiBundle/Moderator/ApiLimitModerator.php
namespace WeavingTheWeb\Bundle\ApiBundle\Moderator;
========
namespace App\Twitter\Infrastructure\Http\Compliance;
>>>>>>>> worker-v3.4.0:src/Twitter/Infrastructure/Http/Compliance/RateLimitCompliance.php

<<<<<<<< HEAD:src/Twitter/Infrastructure/Api/Moderator/ApiLimitModerator.php
use App\Twitter\Infrastructure\Api\Throttling\ApiLimitModeratorInterface;
|||||||| 3ea9802d:src/WeavingTheWeb/Bundle/ApiBundle/Moderator/ApiLimitModerator.php
========
use App\Twitter\Infrastructure\Http\Throttling\RateLimitComplianceInterface;
>>>>>>>> worker-v3.4.0:src/Twitter/Infrastructure/Http/Compliance/RateLimitCompliance.php
use Psr\Log\LoggerInterface;
use function strtr;

/**
 * @author revue-de-presse.org <thierrymarianne@users.noreply.github.com>
 */
<<<<<<<< HEAD:src/Twitter/Infrastructure/Api/Moderator/ApiLimitModerator.php
class ApiLimitModerator implements ApiLimitModeratorInterface
|||||||| 3ea9802d:src/WeavingTheWeb/Bundle/ApiBundle/Moderator/ApiLimitModerator.php
class ApiLimitModerator
========
class RateLimitCompliance implements RateLimitComplianceInterface
>>>>>>>> worker-v3.4.0:src/Twitter/Infrastructure/Http/Compliance/RateLimitCompliance.php
{
<<<<<<<< HEAD:src/Twitter/Infrastructure/Api/Moderator/ApiLimitModerator.php
    /**
     * @var LoggerInterface $logger
     */
    protected LoggerInterface $logger;
|||||||| 3ea9802d:src/WeavingTheWeb/Bundle/ApiBundle/Moderator/ApiLimitModerator.php
    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    protected $logger;
========
    protected LoggerInterface $logger;
>>>>>>>> worker-v3.4.0:src/Twitter/Infrastructure/Http/Compliance/RateLimitCompliance.php

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * @param $seconds
     * @param array $parameters
     */
    public function waitFor($seconds, array $parameters = []): void
    {
        if ($this->logger !== null) {
            if ($seconds < 60) {
                $humanlyReadableWaitTime = $seconds.' more seconds';
            } else {
                $humanlyReadableWaitTime = floor($seconds / 60).' more minutes';
            }

            $message = 'API limit has been reached for token "{{ token }}...", '.
                'operations are currently frozen (waiting for {{ wait_time }} )';
            $parameters['{{ wait_time }}'] = $humanlyReadableWaitTime;
            $this->logger->info(strtr($message, $parameters));
        }

        if ($seconds > 0) {
            sleep($seconds);
        }
    }
}
