<?php

namespace Crm\UjszoModule\Events;

use Crm\ApplicationModule\Config\ApplicationConfig;
use Crm\SubscriptionsModule\Repository\SubscriptionTypesRepository;
use Crm\PaymentsModule\Repository\PaymentsRepository;
use League\Event\AbstractListener;
use League\Event\EventInterface;
use GuzzleHttp\Client as HttpClient;

class PaymentChangeStatusEventHandler extends AbstractListener {

  private $paymentsRepository;

  private $subscriptionTypesRepository;

  private $emitter;

  private $applicationConfig;

  public function __construct(
        SubscriptionTypesRepository $subscriptionTypesRepository,
        ApplicationConfig $applicationConfig,
        PaymentsRepository $paymentsRepository
  ) {
      $this->subscriptionTypesRepository = $subscriptionTypesRepository;
      $this->paymentsRepository = $paymentsRepository;
      $this->applicationConfig = $applicationConfig;
  }

  public function handle(EventInterface $event) {
    if ($event->isPaid()) {
      $payment = $event->getPayment();
      // hard reload, other handlers could have alter the payment already
      $payment = $this->paymentsRepository->find($payment->id);

      $subscriptionType = $this->subscriptionTypesRepository->find($payment->subscription_type_id);

      $client = new HttpClient();
      $mailer_host = getenv('MAILER_ADDR');
      $sso_token = getenv('SSO_TOKEN');

      $url = $mailer_host . '/api/v1/mailers/send-email';
      $body = [
        "mail_template_code" => "payment_successful",
        "email" => $this->applicationConfig->get('contact_email'),
        "params" => [
          'payment' => $payment->toArray(),
          'subscription_type' => $subscriptionType->toArray(),
          'currency' => $this->applicationConfig->get('currency')
        ]
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


}