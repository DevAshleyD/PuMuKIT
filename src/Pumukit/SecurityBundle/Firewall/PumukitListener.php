<?php

namespace Pumukit\SecurityBundle\Firewall;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Pumukit\SecurityBundle\Services\CASService;

class PumukitListener extends AbstractAuthenticationListener
{
    protected $casService;

  /**
   * {@inheritdoc}
   */
  public function __construct(SecurityContextInterface $securityContext, AuthenticationManagerInterface $authenticationManager, SessionAuthenticationStrategyInterface $sessionStrategy, HttpUtils $httpUtils, $providerKey, AuthenticationSuccessHandlerInterface $successHandler, AuthenticationFailureHandlerInterface $failureHandler, array $options = array(), LoggerInterface $logger = null, EventDispatcherInterface $dispatcher = null, CASService $casService)
  {
      parent::__construct($securityContext, $authenticationManager, $sessionStrategy, $httpUtils, $providerKey, $successHandler, $failureHandler, $options, $logger, $dispatcher);
      $this->casService = $casService;
  }

  /**
   * {@inheritdoc}
   */
  protected function attemptAuthentication(Request $request)
  {
      $this->casService->forceAuthentication();
      $username = $this->casService->getUser();

      $token = new PreAuthenticatedToken($username, array('ROLE_USER'), $this->providerKey);

      return $this->authenticationManager->authenticate($token);
  }
}
