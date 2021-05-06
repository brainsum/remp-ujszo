<?php

namespace Crm\UjszoModule\Events;

use Crm\ApplicationModule\Config\ApplicationConfig;
use Crm\InvoicesModule\InvoiceGenerator;
use Crm\PaymentsModule\Repository\PaymentsRepository;
use Crm\SubscriptionsModule\Repository\SubscriptionTypesRepository;
use Crm\UsersModule\Repository\AddressesRepository;
use Crm\UsersModule\Repository\UsersRepository;
use GuzzleHttp\Client as HttpClient;
use League\Event\AbstractListener;
use League\Event\EventInterface;
use Tracy\Debugger;

class InvoiceCreatedEventHandler extends AbstractListener {

  private $paymentsRepository;

  private $subscriptionTypesRepository;

  private $emitter;

  private $applicationConfig;

  private $invoiceGenerator;

  private $usersRepository;

  private $addressesRepository;

  public function __construct(
    UsersRepository $usersRepository,
    SubscriptionTypesRepository $subscriptionTypesRepository,
    ApplicationConfig $applicationConfig,
    PaymentsRepository $paymentsRepository,
    InvoiceGenerator $invoiceGenerator,
    AddressesRepository $addressesRepository
  ) {
    $this->usersRepository = $usersRepository;
    $this->subscriptionTypesRepository = $subscriptionTypesRepository;
    $this->paymentsRepository = $paymentsRepository;
    $this->applicationConfig = $applicationConfig;
    $this->invoiceGenerator = $invoiceGenerator;
    $this->addressesRepository = $addressesRepository;
  }

  public function handle(EventInterface $event) {
    $payment = $event->getPayment();

    $row = $this->usersRepository->find($payment->user->id);

    $invoiceAddress = $this->addressesRepository->address($row, 'invoice');
    $address = [];

    if ($invoiceAddress) {
      $address = [
        'invoice' => $row->invoice,
        'company_name' => $invoiceAddress->company_name ? $invoiceAddress->company_name : '',
        'address' => $invoiceAddress->address ? $invoiceAddress->address : '',
        'number' => $invoiceAddress->number,
        'city' => $invoiceAddress->city,
        'zip' => $invoiceAddress->zip,
        'company_id' => $invoiceAddress->company_id,
        'company_tax_id' => $invoiceAddress->company_tax_id,
        'company_vat_id' => $invoiceAddress->company_vat_id
      ];
    }

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
        'currency' => $this->applicationConfig->get('currency'),
        'address' => $address
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
      Debugger::log($e, Debugger::ERROR);
    }
  }

}