<?php

namespace WeavingTheWeb\Bundle\TwitterBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\JsonResponse,
    Symfony\Component\HttpFoundation\Request;
use WeavingTheWeb\Bundle\ApiBundle\Entity\UserStream;

/**
 * Class TweetController
 * @package WeavingTheWeb\Bundle\TwitterBundle\Controller
 */
class TweetController extends Controller
{
    /**
     * @Extra\Route("/tweet/latest", name="weaving_the_web_twitter_tweet_latest")
     * @Extra\Method({"GET", "OPTIONS"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function latestAction(Request $request)
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->getCorsOptionsResponse();
        } else {
            $userStreamRepository = $this->get('weaving_the_web_twitter.repository.user_stream');

            return new JsonResponse($userStreamRepository->findLatest(), 200, ['Access-Control-Allow-Origin' => '*']);
        }
    }

    /**
     * @return JsonResponse
     */
    protected function getCorsOptionsResponse()
    {
        return new JsonResponse(
            [],
            200,
            [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Headers' => implode(
                    ', ',
                    [
                        'Keep-Alive',
                        'User-Agent',
                        'X-Requested-With',
                        'If-Modified-Since',
                        'Cache-Control',
                        'Content-Type'
                    ]
                )
            ]
        );
    }

    /**
     * @Extra\Route("/tweet/star/{id}", name="weaving_the_web_twitter_tweet_star")
     * @Extra\Method({"POST", "OPTIONS"})
     *
     * @param UserStream $userStream
     * @return JsonResponse
     */
    public function starAction(UserStream $userStream)
    {
        return $this->toggleStarringStatus($userStream, $starring = true);
    }

    /**
     * @Extra\Route("/tweet/unstar/{id}", name="weaving_the_web_twitter_tweet_unstar")
     * @Extra\Method({"POST", "OPTIONS"})
     *
     * @param UserStream $userStream
     * @return JsonResponse
     */
    public function unstarAction(UserStream $userStream)
    {
        return $this->toggleStarringStatus($userStream, $starring = false);
    }

    /**
     * @param UserStream $userStream
     * @param bool $starred
     * @return JsonResponse
     */
    protected function toggleStarringStatus(UserStream $userStream, $starred = false)
    {
        $requestStack = $this->get('request_stack');
        $request = $requestStack->getMasterRequest();

        if ($request->isMethod('POST')) {
            $userStream->setStarred($starred);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($userStream);
            $entityManager->flush();

            return new JsonResponse([
                'status' => $userStream->getId()
            ], 200, ['Access-Control-Allow-Origin' => '*']);
        } else {
            return $this->getCorsOptionsResponse();
        }
    }
}