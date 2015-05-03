<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Controller;

use Elastica\Exception\Connection\HttpException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Handles responses related to tweets
 *
 * @package WeavingTheWeb\Bundle\DashboardBundle\Controller
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 *
 * @Extra\Route("/tweet")
 */
class TweetController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Extra\Route("/github", name="weaving_the_web_dashboard_tweet_github")
     * @Extra\Method({"GET"})
     */
    public function showGitHubTweetsAction()
    {
        return $this->showTweetsContaining('github');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Extra\Route("/links", name="weaving_the_web_dashboard_tweet_links")
     * @Extra\Method({"GET"})
     */
    public function showTweetsWithLinksAction()
    {
        return $this->showTweetsContaining('t.co');
    }

    /**
     * @param $subject
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function showTweetsContaining($subject)
    {
        $finder = $this->getFinder();
        $gitHubRelatedTweets = array();

        try {
            $gitHubRelatedTweets = $finder->find($subject);
        } catch (HttpException $exception) {
            $this->get('logger')->error($exception->getMessage());
        }

        return $this->render(
            'WeavingTheWebDashboardBundle:Tweet:showTweets.html.twig',
            [
                'active_menu_item' => 'tweets',
                'tweets' => $gitHubRelatedTweets,
            ]
        );
    }

    /**
     * @return \FOS\ElasticaBundle\Finder\FinderInterface
     */
    protected function getFinder()
    {
        $searchIndex = $this->container->getParameter('twitter_search_index');

        return $this->container->get('fos_elastica.finder.' . $searchIndex . '.user_status');
    }
}