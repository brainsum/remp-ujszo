<?php

namespace Crm\UjszoModule\Presenters;

use Crm\ApplicationModule\Presenters\FrontendPresenter;
use Crm\ApiModule\Api\JsonResponse;
use Crm\PaymentsModule\Repository\PaymentsRepository;
use Crm\PaymentsModule\Repository\PaymentMetaRepository;
use Nette\Application\BadRequestException;
use Nette\Application\LinkGenerator;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersGetRequest;

class CheckoutPresenter extends FrontendPresenter
{
    private $paymentsRepository;

    private $paymentMetaRepository;

    protected $linkGenerator;

    public function __construct(
        PaymentsRepository $paymentsRepository,
        PaymentMetaRepository $paymentMetaRepository,
        LinkGenerator $linkGenerator
    ) {
        parent::__construct();
        $this->paymentsRepository = $paymentsRepository;
        $this->paymentMetaRepository = $paymentMetaRepository;
        $this->linkGenerator = $linkGenerator;
    }

    public function startup()
    {
        parent::startup();
        if ($this->layoutManager->exists($this->getLayoutName() . '_plain')) {
          $this->setLayout($this->getLayoutName() . '_plain');
        }
    }

    public function renderOrder($payment_id, $vs) {
      $payment = $this->paymentsRepository->findByVS($vs);
      $gateway = $payment->payment_gateway;

      $this->template->payment_id = $payment_id;
      $this->template->order_id_url = $this->linkGenerator->link('Ujszo:Checkout:OrderId');
      $this->template->return_url = $this->linkGenerator->link('Payments:Return:gateway', ['gatewayCode' => $payment->payment_gateway->code, 'VS' => $vs, 'paypal_success' => 1]);
      $this->template->error_url = $this->linkGenerator->link('Payments:Return:gateway', ['gatewayCode' => $payment->payment_gateway->code, 'VS' => $vs, 'paypal_success' => 0]);
      $this->template->client_id = $this->applicationConfig->get('paypal_client_id');
    }

    public function renderOrderId() {
      $json = file_get_contents('php://input');
      $post = json_decode($json, true);

      if ($payment_id = $post['payment_id']) {
        $payment = $this->paymentsRepository->findBy('id', $payment_id);
        $order_id = $this->paymentMetaRepository->findByPaymentAndKey($payment, 'order_id');

        $order = $this->getPaypalOrder($order_id->value);

        if (
          $order->statusCode == 200 &&
          $order->result->status == "CREATED"
        ) {
          // $this->template->order_id = $order_id;
          $this->sendResponse(new JsonResponse([
            'orderId' => $order_id->value,
          ]));
        }
      }

      throw new BadRequestException('Order not found');
    }

    private function getPaypalOrder($order_id) {
      if ($this->applicationConfig->get('paypal_mode') == 'live') {
        $environmentClass = \PayPalCheckoutSdk\Core\ProductionEnvironment::class;
      } else {
        $environmentClass = \PayPalCheckoutSdk\Core\SandboxEnvironment::class;
      }

      $environment = new $environmentClass(
        $this->applicationConfig->get('paypal_client_id'),
        $this->applicationConfig->get('paypal_client_secret')
      );
      $client = new PayPalHttpClient($environment);
      return $client->execute(new OrdersGetRequest($order_id));
    }

}
