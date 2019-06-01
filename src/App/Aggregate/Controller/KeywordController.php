<?php


namespace App\Aggregate\Controller;

use App\Cache\RedisCache;
use App\Security\Cors\CorsHeadersAwareTrait;
use App\Status\Repository\KeywordRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class KeywordController
{
    use CorsHeadersAwareTrait;

    /**
     * @var string
     */
    public $allowedOrigin;

    /**
     * @var string
     */
    public $environment;

    /**
     * @var RedisCache
     */
    public $redisCache;

    /**
     * @var KeywordRepository
     */
    public $keywordRepository;

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getKeywords(Request $request)
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->getCorsOptionsResponse(
                $this->environment,
                $this->allowedOrigin
            );
        }

        $startDate = new \DateTime($request->query->get('startDate'));
        $endDate = new \DateTime($request->query->get('endDate'));

        $keywords = $this->keywordRepository->getKeywords($startDate, $endDate);

        return new JsonResponse($keywords);
    }
}
