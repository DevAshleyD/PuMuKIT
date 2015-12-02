<?php

namespace Pumukit\NotificationBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\NotificationBundle\Services\SenderService;

class SenderServiceTest extends WebTestCase
{
    private $dm;
    private $repo;
    private $senderService;
    private $mailer;
    private $templating;
    private $translator;
    private $enable;
    private $senderName;
    private $senterEmail;
    private $notificateErrorsToSender;
    private $environment;

    public function __construct()
    {
        $options = array('environment' => 'dev');
        $kernel = static::createKernel($options);
        $kernel->boot();
        $container = $kernel->getContainer();

        $this->dm = $container
          ->get('doctrine_mongodb')->getManager();

        $this->mailer = $container
          ->get('mailer');
        $this->templating = $container
          ->get('templating');
        $this->translator = $container
          ->get('translator');
        $this->enable = true;
        $this->senderEmail = 'mercefan@gmail.com';
        $this->senderName = 'Mercefan';
        $this->notificateErrorsToSender = true;
        $this->environment = 'dev';
    }

    public function setUp()
    {
        $this->senderService = new SenderService($this->mailer, $this->templating, $this->translator, $this->enable, $this->senderEmail, $this->senderName, $this->notificateErrorsToSender, $this->environment);
    }

    public function testIsEnabled()
    {
        $this->assertEquals($this->enable, $this->senderService->isEnabled());
    }

    public function testGetSenderEmail()
    {
        $this->assertEquals($this->senderEmail, $this->senderService->getSenderEmail());
    }

    public function testGetSenderName()
    {
        $this->assertEquals($this->senderName, $this->senderService->getSenderName());
    }

    public function testDoNotificateErrorsToSender()
    {
        $this->assertEquals($this->notificateErrorsToSender, $this->senderService->doNotificateErrorsToSender());
    }

    public function testSendNotification()
    {
        $this->markTestSkipped('S');

        $mailTo = 'mrey@teltek.es';
        $subject = 'Test sender service';
        $body = 'test send notification';
        $template = 'PumukitNotificationBundle:Email:notification.html.twig';
        $parameters = array('subject' => $subject, 'body' => $body, 'sender_name' => 'mercefan');
        $output = $this->senderService->sendNotification($mailTo, $subject, $template, $parameters, false);
        $this->assertEquals(1, $output);
    }

}