<?php

namespace Pumukit\NewAdminBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class WidgetController extends AbstractController
{
    /**
     * @Template("PumukitNewAdminBundle:Widget:languageselect.html.twig")
     */
    public function languageselectAction()
    {
        $array_locales = $this->container->getParameter('pumukit.locales');
        if (count($array_locales) <= 1) {
            return new Response('');
        }

        return ['languages' => $array_locales];
    }
}
