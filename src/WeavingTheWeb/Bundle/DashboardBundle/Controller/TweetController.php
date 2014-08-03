<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Controller;

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

        return $this->render(
            'WeavingTheWebDashboardBundle:Tweet:showGitHubTweets.html.twig', array(
                'active_menu_item' => 'tweets',
                'title'            => $translator->trans('title.tweet.github', [], 'dashboard')
            ));
    }
} 