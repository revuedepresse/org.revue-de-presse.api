<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * @package WeavingTheWeb\Bundle\DashboardBundle\Controller
 */
class InspirationController extends Controller
{
    /**
     * @Extra\Route("/inspiration-of-the-day", name="weaving_the_web_dashboard_inspiration")
     * @Extra\Template("WeavingTheWebDashboardBundle:Inspiration:inspirationOfTheDay.html.twig")
     */
    public function inspirationOfTheDayAction()
    {
        setlocale(LC_TIME, "fr_FR");

        return [
            'today' => strftime("%d %B %Y"),
            'quoteOfTheDay' =>  $this->quoteOfTheDayAction(),
            'sayingOfTheDay' => $this->sayingOfTheDayAction(),
            'title' => 'Inspiration du jour',
            'displayMenu' => false,
        ];
    }

    protected function quoteOfTheDayAction()
    {
        return $this->csvToArray(
            'quotes.csv'
        );
    }

    protected function sayingOfTheDayAction()
    {
        return $this->csvToArray(
            'saying.csv'
        );
    }

    /**
     * @param $filePath
     * @return array
     */
    protected function csvToArray($filePath)
    {
        $handle = fopen($this->container->getParameter('kernel.root_dir') . '/../inspiration/' . $filePath, 'r');

        $collection = [];
        $i = 0;
        while ($item = fgetcsv($handle)) {
            if ($i === intval(date('z'))) {
                $collection[] = $item;
            }
            $i++;
        }

        return $collection;
    }
}
