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
            $userStreamRepository = $this->get('weaving_the_web_twitter.repository.read.user_stream');
            $userStreamRepository->setOauthToken(
                $request->get(
                    'token',
                    null,
                    $this->container->getParameter('weaving_the_web_twitter.oauth_token.default')
                )
            );

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
     * @Extra\Route("/tweet/star/{statusId}", name="weaving_the_web_twitter_tweet_star")
     * @Extra\Method({"POST", "OPTIONS"})
     * @Extra\ParamConverter(
     *      "userStream",
     *      class="WeavingTheWebApiBundle:UserStream",
     *      options={"entity_manager"="write"}
     * )
     *
     * @param UserStream $userStream
     * @return JsonResponse
     */
    public function starAction(UserStream $userStream)
    {
        return $this->toggleStarringStatus($userStream, $starring = true);
    }

    /**
     * @Extra\Route("/tweet/unstar/{statusId}", name="weaving_the_web_twitter_tweet_unstar")
     * @Extra\Method({"POST", "OPTIONS"})
     * @Extra\ParamConverter(
     *      "userStream",
     *      class="WeavingTheWebApiBundle:UserStream",
     *      options={"entity_manager"="write"}
     * )
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
        /** @var \Symfony\Component\HttpFoundation\RequestStack $requestStack */
        $requestStack = $this->get('request_stack');
        $request = $requestStack->getMasterRequest();

        if ($request->isMethod('POST')) {
            $userStream->setStarred($starred);

            $clonedUserStream = clone $userStream;
            $clonedUserStream->setUpdatedAt(new \DateTime());

            $entityManager = $this->getDoctrine()->getManager('write');

            $entityManager->remove($userStream);
            $entityManager->flush();

            $entityManager->persist($clonedUserStream);
            $entityManager->flush();

            return new JsonResponse([
                'status' => $userStream->getStatusId()
            ], 200, ['Access-Control-Allow-Origin' => '*']);
        } else {
            return $this->getCorsOptionsResponse();
        }
    }
}