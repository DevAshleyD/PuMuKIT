<?php

namespace Pumukit\SchemaBundle\Listener;

use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Init the locale of the i18n Documents when loaded.
 * Use default locale in console commands and current request locale in web request.
 */
class LocaleListener implements EventSubscriberInterface
{
    private $requestStack;
    private $defaultLocale;
    private $pumukit2Locales;

    public function __construct(RequestStack $requestStack, $defaultLocale = 'en', $pumukit2Locales = array())
    {
        $this->requestStack = $requestStack;
        $this->defaultLocale = $defaultLocale;
        $this->pumukit2Locales = $pumukit2Locales;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
      if ($event->getRequestType() !== HttpKernelInterface::MASTER_REQUEST) {
          return;
      }

      $request = $event->getRequest();
      $requestLocale = $request->attributes->get('_locale');
      $sessionLocale = $request->getSession()->get('_locale');

      // try to see if the locale has been set as a _locale routing parameter
      if ($requestLocale && in_array($requestLocale, $this->pumukit2Locales)) {
          $request->getSession()->set('_locale', $requestLocale);
      } else {
          if(!$sessionLocale || !in_array($sessionLocale, $this->pumukit2Locales)) {
              if (in_array($this->defaultLocale, $this->pumukit2Locales)) {
                  $request->getSession()->set('_locale', $this->defaultLocale);
              } elseif (!empty($this->pumukit2Locales)) {
                  $request->getSession()->set('_locale', $this->pumukit2Locales[0]);
              } else {
                  throw new \Exception('Pumukit2.Locales is empty. You should define it in your parameters.');
              }
          }
          
          $request->setLocale($request->getSession()->get('_locale'));
      }
    }

    public static function getSubscribedEvents()
    {
      return array(
                   // must be registered before the default Locale listener
                   KernelEvents::REQUEST => array(array('onKernelRequest', 17)),
                   );
    }

    public function postLoad(LifecycleEventArgs $args)
    {
        $document = $args->getDocument();

        if (method_exists($document, 'setLocale')) {
            if ($request = $this->requestStack->getCurrentRequest()) {
                $document->setLocale($request->getLocale());
            } else {
                $document->setLocale($this->defaultLocale);
            }
        }
    }
}
