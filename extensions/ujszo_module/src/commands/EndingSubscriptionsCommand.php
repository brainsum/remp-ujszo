<?php

namespace Crm\UjszoModule\Commands;

use Crm\PaymentsModule\Repository\PaymentsRepository;
use Crm\PaymentsModule\Repository\RecurrentPaymentsRepository;
use Crm\SubscriptionsModule\Repository\SubscriptionsRepository;
use Nette\Utils\DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EndingSubscriptionsCommand extends Command
{
  private $subscriptionsRepository;

    private $paymentsRepository;

  private $recurrentPaymentsRepository;

  public function __construct(
    SubscriptionsRepository $subscriptionsRepository,
    PaymentsRepository $paymentsRepository,
    RecurrentPaymentsRepository $recurrentPaymentsRepository
  ) {
    parent::__construct();
    $this->subscriptionsRepository = $subscriptionsRepository;
    $this->paymentsRepository = $paymentsRepository;
    $this->recurrentPaymentsRepository = $recurrentPaymentsRepository;
  }

  protected function configure()
  {
    $this->setName('ujszo:ending_subscriptions')
      ->setDescription('Checks and notifies users with ending subscriptions');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {

    $endTimeStart = new DateTime;
    $endTimeStart->modify('+7days');
    $endTimeStart->setTime(0,0);

    $endTimeEnd = new DateTime($endTimeStart);
    $endTimeEnd->modify('+1day');

    $this->notifyEndingSubscriptions($endTimeStart, $endTimeEnd);

    return 0;
  }

  protected function notifyEndingSubscriptions(DateTime $endTimeStart, DateTime $endTimeEnd)
  {
    $subscriptions = $this->subscriptionsRepository->subscriptionsEndBetween($endTimeStart, $endTimeEnd);
    foreach ($subscriptions as $subscription) {
      if ($subscription->subscription_type->disable_notifications) {
        continue;
      }

      $params = [
        'subscription' => $subscription->toArray(),
        'subscription_type' => $subscription->subscription_type->toArray(),
      ];

      if ($subscription->next_subscription_id) {
        $result = false;
        continue;
      }

      if ($subscription->is_recurrent && !$this->recurrentPaymentsRepository->isStoppedBySubscription($subscription)) {
        $payment = $this->paymentsRepository->subscriptionPayment($subscription);
        if ($payment) {
          $recurrentPayment = $this->recurrentPaymentsRepository->recurrent($payment);
          $params['recurrent_payment'] = $recurrentPayment->toArray();
        }
      } elseif ($this->subscriptionsRepository->hasSubscriptionEndAfter($subscription->user->id, $subscription->end_time)) {
        // TODO: Ignore free subscription
        // continue;
      }

      $result = false;
      if (!$subscription->user->active) {
        continue;
      }

      $email = $subscription->user->email;
      $context = "subscription.{$subscription->id}";

      $result = true;

      $client = new \GuzzleHttp\Client();
      $mailer_host = getenv('MAILER_ADDR');
      $sso_token = getenv('SSO_TOKEN');

      $template_code = "subscription_ending";

      $url = $mailer_host . '/api/v1/mailers/send-email';
      $body = [
        "mail_template_code" => $template_code,
        "email" => $email,
        "params" => $params
      ];

      $res = $client->post($url, [
        'headers' => [
          'Content-Type' => 'application/json',
          'Authorization'=> 'Bearer ' . $sso_token,
        ],
        'body' => json_encode($body)
      ]);

    }
  }

}
