<?php

namespace Pumukit\AdminBundle\Controller;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Pagerfanta\Adapter\DoctrineCollectionAdapter;
use Pagerfanta\Pagerfanta;

use Pumukit\SchemaBundle\Document\Series;

class SeriesPicController extends ElementController
{
  /**
   * Render create.html
   */
  public function createAction(Request $request)
  {
    $config = $this->getConfiguration();

    if (null != $request->attributes->get('id')) {
      $id = $request->attributes->get('id');
      $picService = $this->get('pumukitschema.pic');
      $series = $picService->getResource('Series', $id);
    }else{
      $series = null;
    }
	 
    if (!isset($series)) {
      //raise error or show message
    }

    // TODO search in picservice according to page (in criteria)
    if ($request->get('page', null)) {
      $this->get('session')->set('admin/seriespic/page', $request->get('page', 1));
    }
    $page = $this->get('session')->get('admin/seriespic/page', 1);
    $limit = 12;

    list($collPics, $total) = $picService->getPics('Series', $id, $page, $limit);
    
    $adapter = new DoctrineCollectionAdapter($collPics);
    $pics = new Pagerfanta($adapter);

    $pics
      ->setCurrentPage($page, true, true)
      ->setMaxPerPage($limit)
      ;

    $view = $this
      ->view()
      ->setTemplate($config->getTemplate('create.html'))
      ->setData(array(
		      'resource' => $series,
		      'resource_name' => 'series',
		      'pics' => $pics,
		      'page' => $page,
		      'total' => $total
		      ));

    return $this->handleView($view);
  }



  public function listAction(Series $series)
  {
      return $this->render('PumukitAdminBundle:Pic:list.html.twig', array('series' => $series));
  }

  /**
   * Assign a picture from an url 
   * or from an existing one
   * to the series
   */
  public function updateAction(Request $request)
  {
    $config = $this->getConfiguration();

    if ($request->get('url', null)){
      $series_id = $request->attributes->get('id');
      $picService = $this->get('pumukitschema.pic');
      $series = $picService->setPicUrl('Series', $series_id, $request->get('url'));
    }
    
    $view = $this
      ->view()
      ->setTemplate($config->getTemplate('list.html'))
      ->setData(array('series' => $series));

    return $this->handleView($view);
  }


  public function deleteAction(Request $request)
  {
    $picId = $this->getRequest()->get('id');

    $repo = $this->get('doctrine_mongodb')
      ->getRepository('PumukitSchemaBundle:Series');
      
    if (!$series = $repo->findByPicId($picId)) {
      throw new NotFoundHttpException('Requested series does not exist');
    }

    $series->removePicById($picId);

    $dm = $this->get('doctrine_mongodb')->getManager();
    $dm->persist($series);
    $dm->flush();

    return $this->redirect($this->generateUrl('pumukitadmin_seriespic_list', array('id' => $series->getId())));
  }


  public function upAction(Request $request)
  {
    $picId = $this->getRequest()->get('id');

    $repo = $this->get('doctrine_mongodb')
      ->getRepository('PumukitSchemaBundle:Series');
      
    if (!$series = $repo->findByPicId($picId)) {
      throw new NotFoundHttpException('Requested series does not exist');
    }

    $series->upPicById($picId);

    $dm = $this->get('doctrine_mongodb')->getManager();
    $dm->persist($series);
    $dm->flush();


    return $this->redirect($this->generateUrl('pumukitadmin_seriespic_list', array('id' => $series->getId())));
  }


  public function downAction(Request $request)
  {
    $picId = $this->getRequest()->get('id');

    $repo = $this->get('doctrine_mongodb')
      ->getRepository('PumukitSchemaBundle:Series');
      
    if (!$series = $repo->findByPicId($picId)) {
      throw new NotFoundHttpException('Requested series does not exist');
    }

    $series->downPicById($picId);

    $dm = $this->get('doctrine_mongodb')->getManager();
    $dm->persist($series);
    $dm->flush();

    return $this->redirect($this->generateUrl('pumukitadmin_seriespic_list', array('id' => $series->getId())));
  }
}