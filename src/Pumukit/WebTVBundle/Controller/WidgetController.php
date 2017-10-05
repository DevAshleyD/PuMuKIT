<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class WidgetController extends Controller implements WebTVController
{
    public static $menuResponse = null;

    public function menuAction()
    {
        if (self::$menuResponse) {
            return self::$menuResponse;
        }

        if ($this->container->hasParameter('pumukit_new_admin.advance_live_event') and $this->container->getParameter('pumukit_new_admin.advance_live_event')) {
            $dm = $this->container->get('doctrine_mongodb')->getManager();
            $events = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findEventsMenu();
            $menuEvents = array();
            $nowOrFuture = false;
            foreach ($events as $event) {
                foreach ($event['data'] as $sessionData) {
                    $start = $sessionData['session']['start']->toDateTime();
                    $ends = clone $start;
                    $ends = $ends->add(new \DateInterval('PT'.(intval($sessionData['session']['duration'] / 60)).'M'.($sessionData['session']['duration'] % 60).'S'));
                    if (new \DateTime() < $ends) {
                        $nowOrFuture = true;
                    }

                    if ($nowOrFuture) {
                        $menuEvents[(string) $event['_id']] = array();
                        $menuEvents[(string) $event['_id']]['event'] = $sessionData['event'];
                        $menuEvents[(string) $event['_id']]['sessions'][] = $sessionData['session'];
                        $nowOrFuture = false;
                    }
                }
            }

            $events = $menuEvents;
            $channels = array(); // Not important with advance_live_events
            $liveEventTypeSession = true;
        } else {
            $channels = $this->get('doctrine_mongodb')->getRepository('PumukitLiveBundle:Live')->findAll();
            $events = $this->get('doctrine_mongodb')->getRepository('PumukitLiveBundle:Event')->findNextEvents();
            $liveEventTypeSession = false;
        }

        $selected = $this->container->get('request_stack')->getMasterRequest()->get('_route');

        $menuStats = $this->container->getParameter('menu.show_stats');
        $homeTitle = $this->container->getParameter('menu.home_title');
        $announcesTitle = $this->container->getParameter('menu.announces_title');
        $searchTitle = $this->container->getParameter('menu.search_title');
        $mediatecaTitle = $this->container->getParameter('menu.mediateca_title');
        $categoriesTitle = $this->container->getParameter('menu.categories_title');

        self::$menuResponse = $this->render('PumukitWebTVBundle:Widget:menu.html.twig', array(
            'advance_live_channels' => array(
                'events' => $events,
                'channels' => $channels,
                'type' => $liveEventTypeSession,
            ),
            'live_events' => $events, // PuMuKIT 2.3.x BC
            'live_channels' => $channels,  // PuMuKIT 2.3.x BC
            'menu_selected' => $selected,
            'menu_stats' => $menuStats,
            'home_title' => $homeTitle,
            'announces_title' => $announcesTitle,
            'search_title' => $searchTitle,
            'mediateca_title' => $mediatecaTitle,
            'categories_title' => $categoriesTitle,
        ));

        return self::$menuResponse;
    }

    /**
     * @Template()
     */
    public function breadcrumbsAction()
    {
        $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');

        return array('breadcrumbs' => $breadcrumbs->getBreadcrumbs());
    }

    /**
     * @Template()
     */
    public function statsAction()
    {
        $mmRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
        $seriesRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:series');

        $counts = array(
            'series' => $seriesRepo->countPublic(),
            'mms' => $mmRepo->count(),
            'hours' => bcdiv($mmRepo->countDuration(), 3600, 2),
        );

        return array('counts' => $counts);
    }

    /**
     * @Template()
     */
    public function contactAction()
    {
        return array();
    }

    /**
     * @Template("PumukitWebTVBundle:Widget:upcomingliveevents.html.twig")
     */
    public function upcomingLiveEventsAction()
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $eventRepo = $dm->getRepository('PumukitLiveBundle:Event');
        $events = $eventRepo->findFutureAndNotFinished(5);

        return array('events' => $events);
    }

    /**
     * @Template()
     */
    public function languageselectAction()
    {
        $array_locales = $this->container->getParameter('pumukit2.locales');
        if (count($array_locales) <= 1) {
            return new Response('');
        }

        return array('languages' => $array_locales);
    }
}
