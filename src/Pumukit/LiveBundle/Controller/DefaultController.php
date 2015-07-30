<?php

namespace Pumukit\LiveBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Pumukit\LiveBundle\Document\Live;

class DefaultController extends Controller
{
    /**
     * @Route("/live/{id}", name="pumukit_live_id")
     * @Template("PumukitLiveBundle:Default:index.html.twig")
     */
    public function indexAction(Live $live)
    {
        $this->updateBreadcrumbs($live->getName(), "pumukit_live_id", array("id" => $live->getId()));

        $userAgent = $this->getRequest()->headers->get('user-agent');
        $mobileDetectorService = $this->get('mobile_detect.mobile_detector');
        $mobileDevice = ($mobileDetectorService->isMobile($userAgent) || $mobileDetectorService->isTablet($userAgent));
        $isIE = $mobileDetectorService->version('IE');
        $versionIE = 11.0;
        if ($isIE) {
            $versionIE = floatval($isIE);
        }

        return array(
                     'live' => $live,
                     'mobile_device' => $mobileDevice,
                     'isIE' => $isIE,
                     'versionIE' => $versionIE
                     );
    }


    /**
     * @Route("/live", name="pumukit_live")
     * @Template("PumukitLiveBundle:Default:index.html.twig")
     */
    public function defaultAction()
    {      
        $repo = $this
          ->get('doctrine_mongodb.odm.document_manager')
          ->getRepository('PumukitLiveBundle:Live');
        $live = $repo->findOneBy(array());

        if(!$live)
          throw $this->createNotFoundException('The live channel does not exist');
        
        $this->updateBreadcrumbs($live->getName(), "pumukit_live", array("id" => $live->getId()));

        $userAgent = $this->getRequest()->headers->get('user-agent');
        $mobileDetectorService = $this->get('mobile_detect.mobile_detector');
        $mobileDevice = ($mobileDetectorService->isMobile($userAgent) || $mobileDetectorService->isTablet($userAgent));
        $isIE = $mobileDetectorService->version('IE');
        $versionIE = 11.0;
        if ($isIE) {
            $versionIE = floatval($isIE);
        }

        return array(
                     'live' => $live,
                     'mobile_device' => $mobileDevice,
                     'isIE' => $isIE,
                     'versionIE' => $versionIE
                     );
    }


    private function updateBreadcrumbs($title, $routeName, array $routeParameters = array())
    {
        $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');
        $breadcrumbs->addList($title, $routeName, $routeParameters);
    }


    /**
     * @Route("/live/playlist/{id}", name="pumukit_live_playlist_id", defaults={"_format": "xml"})
     * @Template("PumukitLiveBundle:Default:playlist.xml.twig")
     */
    public function playlistAction(Live $live)
    {
      $intro = $this->container->hasParameter('pumukit2.intro') ?
        $this->container->getParameter('pumukit2.intro') :
        null;
      return array('live' => $live, 'intro' => $intro);
    }

    /**
     * @Route("/live/jwplayer/error", name="pumukit_live_jwplayererror")
     * @Template("PumukitLiveBundle:Default:jwplayererror.html.twig")
     */
    public function jwplayererrorAction(Request $request)
    {
        $errorMessage = $request->query->get('errorMessage');
        return array('error' => $errorMessage);
    }
}
