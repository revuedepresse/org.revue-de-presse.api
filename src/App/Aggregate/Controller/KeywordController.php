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
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getKeywords(Request $request)
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->getCorsOptionsResponse(
                $this->environment,
                $this->allowedOrigin
            );
        }

        $keywords = $this->keywordRepository->getKeywords();

        return new JsonResponse($keywords);
    }
}
