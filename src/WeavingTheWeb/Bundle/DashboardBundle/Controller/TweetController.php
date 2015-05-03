<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Controller;

use Elastica\Exception\Connection\HttpException;

use Elastica\Query;

use Elastica\QueryBuilder\DSL\Aggregation;

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
     * @Extra\Route("/from-people-talking-about/{keywords}", name="weaving_the_web_dashboard_people_talking_about")
     * @Extra\Method({"GET"})
     */
    public function aggregateFilteredTermsAction($keywords)
    {
        $match = new Query\Match();
        $match->setField('text', $keywords);

        $query = new Query($match);

        $aggregation = new Aggregation();
        $termsAggregation = $aggregation->terms('aggregation_on_screen_name');
        $termsAggregation->setField('screenName');

        $query->addAggregation($termsAggregation);
        $query->setSize(100);
        $query->setExplain(true);

        /** @var \FOS\ElasticaBundle\Finder\FinderInterface $finder */
        $finder = $this->getFinder();
        $screenNames = $finder->find($query);

        /** @var \Symfony\Component\Translation\Translator $translator */
        $translator = $this->get('translator');
        $title = $translator->trans(
            'title.tweet.people_talking_about',
            ['{{ keywords }}' => $keywords],
            'dashboard'
        );

        return $this->render(
            'WeavingTheWebDashboardBundle:Tweet:showScreenNames.html.twig',
            [
                'active_menu_item' => 'tweets',
                'title' => $title,
                'screen_names' => $screenNames,
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