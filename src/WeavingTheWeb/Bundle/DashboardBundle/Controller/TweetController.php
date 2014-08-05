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
    public function showGitHubTweets()
    {
        $translator = $this->get('translator');

        $searchIndex = $this->container->getParameter('twitter_search_index');

        /** @var \FOS\ElasticaBundle\Finder\FinderInterface $finder */
        $finder = $this->container->get('fos_elastica.finder.' . $searchIndex . '.user_status');
        $gitHubRelatedTweets = array();

        try {
            $gitHubRelatedTweets = $finder->find('github');
        } catch (HttpException $exception) {
            $this->get('logger')->error($exception->getMessage());
        }

        return $this->render(
            'WeavingTheWebDashboardBundle:Tweet:showGitHubTweets.html.twig', [
                'active_menu_item' => 'tweets',
                'tweets'           => $gitHubRelatedTweets,
                'title'            => $translator->trans('title.tweet.github', [], 'dashboard')
            ]);
    }
} 