<?php

namespace Pumukit\CmarWebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Broadcast;

/**
 * @Route("/library")
 */
class MediaLibraryController extends Controller
{
    /**
     * @Route("/", name="pumukit_webtv_medialibrary_index")
     * @Route("/", name="pumukitcmarwebtv_library_index")
     */
    public function indexAction(Request $request)
    {
        return $this->redirect($this->generateUrl('pumukitcmarwebtv_library_mainconferences'));
    }

    /**
     * @Route("/gc")
     * @Route("/mainconferences", name="pumukitcmarwebtv_library_mainconferences")
     * @Template("PumukitCmarWebTVBundle:MediaLibrary:display.html.twig")
     */
    public function mainConferencesAction(Request $request)
    {
        $title = "Conferences";
        $tagName = 'PUDEPD1';

        return $this->action($title, $tagName, "pumukitcmarwebtv_library_mainconferences", $request);
    }


    /**
     * @Route("/pc")
     * @Route("/promotional", name="pumukitcmarwebtv_library_promotional")
     * @Template("PumukitCmarWebTVBundle:MediaLibrary:multidisplay.html.twig")
     */
    public function promotionalAction(Request $request)
    {
        $title = "Promotional and corporate";
        $tagName = 'PUDEPD2';

        return $this->action($title, $tagName, "pumukitcmarwebtv_library_promotional", $request);
    }


    /**
     * @Route("/ap")
     * @Route("/pressarea", name="pumukitcmarwebtv_library_pressarea")
     * @Template("PumukitCmarWebTVBundle:MediaLibrary:multidisplay.html.twig")
     */
    public function pressAreaAction(Request $request)
    {
        $title = "Press Area";
        $tagName = 'PUDEPD3';

        return $this->action($title, $tagName, "pumukitcmarwebtv_library_pressarea", $request);
    }


    /**
     * @Route("/ps")
     * @Route("/projectsupport", name="pumukitcmarwebtv_library_projectsupport")
     * @Template("PumukitCmarWebTVBundle:MediaLibrary:multidisplay.html.twig")
     */
    public function projectSupportAction(Request $request)
    {
        $title = "Project Support";
        $tagName = 'PUDEPD4';

        /* TODO $serials["all"] = SerialPeer::retrieveByPKs(array(6, 9, 7)); */
        return $this->action($title, $tagName, "pumukitcmarwebtv_library_projectsupport", $request);
    }

    /**
     * @Route("/c")
     * @Route("/congresses", name="pumukitcmarwebtv_library_congresses")
     * @Template("PumukitCmarWebTVBundle:MediaLibrary:multidisplay.html.twig")
     */
    public function congressesAction(Request $request)
    {
      $title = "Congresses";
      $tagName = 'PUDEPD5';

        // TODO review: check locale, check defintion of congresses
        // $series = $seriesRepo->findBy(array('keyword.en' => 'congress'), array('public_date' => 'desc'));
        return $this->action($title, $tagName, "pumukitcmarwebtv_library_congresses", $request);
    }

    /**
     * @Route("/i")
     * @Route("/institutional", name="pumukitcmarwebtv_library_institutional")
     * @Template("PumukitCmarWebTVBundle:MediaLibrary:multidisplay.html.twig")
     */
    public function institutionalAction(Request $request)
    {
        $title = "Institutional";
        $tagName = 'PUDEPD6';

        //TODO review
        //$tagCod = new \MongoRegex('/^U9901../'); // UNESCO tags for institutions
        //$series = $seriesRepo->findByTagCodAndDisplayStatus($tagCod, $display);
        return $this->action($title, $tagName, "pumukitcmarwebtv_library_institutional", $request);
    }


    /**
     * @Route("/all", name="pumukitcmarwebtv_library_all")
     * @Template("PumukitCmarWebTVBundle:MediaLibrary:multidisplay.html.twig")
     */
    public function allAction(Request $request)
    {
        $title = "All Videos";

        $seriesRepo = $this->get('doctrine_mongodb.odm.document_manager')->getRepository('PumukitSchemaBundle:Series');

        //TODO revew
        $series = $seriesRepo->findBy(array(), array('public_date' => -1));

        return array('title' => $title, 'series' => $series);
    }


    private function action($title, $tagName, $routeName, Request $request, array $sort=array('public_date' => -1))
    {
        $this->get('pumukit_web_tv.breadcrumbs')->addList($title, $routeName);

        $dm = $this->get('doctrine_mongodb.odm.document_manager');

        $tag = $dm->getRepository('PumukitSchemaBundle:Tag')->findOneByCod($tagName);
        if (!$tag) {
          throw $this->createNotFoundException('The tag does not exist');
        }

        $series = $dm->getRepository('PumukitSchemaBundle:Series')->findWithTag($tag, $sort);

        return array('title' => $title, 'series' => $series, 'tag_cod' => $tagName);
    }
}
