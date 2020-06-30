<?php

namespace Crm\UjszoModule\Events;

use Crm\ApplicationModule\Config\ApplicationConfig;
use Crm\SubscriptionsModule\Repository\SubscriptionTypesRepository;
use Crm\PaymentsModule\Repository\PaymentsRepository;
use Crm\InvoicesModule\InvoiceGenerator;
use League\Event\AbstractListener;
use League\Event\EventInterface;
use GuzzleHttp\Client as HttpClient;

class InvoiceCreatedEventHandler extends AbstractListener {

  private $paymentsRepository;

  private $subscriptionTypesRepository;

  private $emitter;

  private $applicationConfig;

  private $invoiceGenerator;

  public function __construct(
        SubscriptionTypesRepository $subscriptionTypesRepository,
        ApplicationConfig $applicationConfig,
        PaymentsRepository $paymentsRepository,
        InvoiceGenerator $invoiceGenerator
  ) {
      $this->subscriptionTypesRepository = $subscriptionTypesRepository;
      $this->paymentsRepository = $paymentsRepository;
      $this->applicationConfig = $applicationConfig;
      $this->invoiceGenerator = $invoiceGenerator;
  }

  public function handle(EventInterface $event) {
    $payment = $event->getPayment();
    $pdf = $event->getPDF();
    // hard reload, other handlers could have alter the payment already
    $payment = $this->paymentsRepository->find($payment->id);

    $subscriptionType = $this->subscriptionTypesRepository->find($payment->subscription_type_id);

    $client = new HttpClient();
    $mailer_host = getenv('MAILER_ADDR');
    $sso_token = getenv('SSO_TOKEN');
    $url = $mailer_host . '/api/v1/mailers/send-email';

    $body = [
      "mail_template_code" => "invocie_generated",
      "email" => $this->applicationConfig->get('contact_email'),
      "params" => [
        'payment' => $payment->toArray(),
        'user' => $payment->user->toArray(),
        'subscription_type' => $subscriptionType->toArray(),
        'currency' => $this->applicationConfig->get('currency')
      ],
    ];

    try {
      $res = $client->post($url, [
        'headers' => [
          'Content-Type' => 'application/json',
          'Authorization'=> 'Bearer ' . $sso_token,
        ],
        'body' => json_encode($body)
      ]);
    } catch (Exception $e) {
      Debugger::log($e);
    }
  }


}