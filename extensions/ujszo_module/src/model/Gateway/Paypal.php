<?php

namespace Crm\UjszoModule\Gateways;

use Crm\ApplicationModule\Config\ApplicationConfig;
use Crm\PaymentsModule\Repository\PaymentMetaRepository;
use Crm\PaymentsModule\Gateways\GatewayAbstract;
use Nette\Application\LinkGenerator;
use Nette\Http\Response;
use Nette\Localization\ITranslator;
use Omnipay\Omnipay;
use Omnipay\PayPal\ExpressGateway;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Orders\OrdersGetRequest;

class Paypal extends GatewayAbstract
{

  private $paymentMetaRepository;

    public function __construct(
        LinkGenerator $linkGenerator,
        ApplicationConfig $applicationConfig,
        Response $httpResponse,
        PaymentMetaRepository $paymentMetaRepository,
        ITranslator $translator
    ) {
        parent::__construct($linkGenerator, $applicationConfig, $httpResponse, $translator);
        $this->paymentMetaRepository = $paymentMetaRepository;
    }

    protected function initialize()
    {
      if ($this->applicationConfig->get('paypal_mode') == 'live') {
        $environmentClass = \PayPalCheckoutSdk\Core\ProductionEnvironment::class;
      } else {
        $environmentClass = \PayPalCheckoutSdk\Core\SandboxEnvironment::class;
      }

      $environment = new $environmentClass(
        $this->applicationConfig->get('paypal_client_id'),
        $this->applicationConfig->get('paypal_client_secret')
      );
      $this->client = new PayPalHttpClient($environment);
    }

    public function begin($payment)
    {
      $this->initialize();
      $request = new OrdersCreateRequest();
      $request->prefer('return=representation');
      $request->body = [
          "intent" => "CAPTURE",
          "purchase_units" => [[
            "reference_id" => "sample",
            "amount" => [
              "value" => $payment->amount,
              "currency_code" => $this->applicationConfig->get('currency')
            ],
            "description" => "Sample subscription on UJSZO",
            "custom_id" => $payment->id,
            ]],
          "application_context" => [
            "return_url" => $this->generateReturnUrl($payment, ['paypal_success' => '1', 'VS' => $payment->variable_symbol]),
            "cancel_url" => $this->generateReturnUrl($payment, ['paypal_success' => '0', 'VS' => $payment->variable_symbol]),
          ]
      ];

      try {
        // Call API with your client and get a response for your call
        $response = $this->client->execute($request);

        // If call returns body in response, you can get the deserialized version from the result attribute of the response
        $order_id = $response->result->id;

        $this->paymentMetaRepository->add($payment, 'order_id', $order_id);

        $url = $this->linkGenerator->link(
          'Ujszo:Checkout:order', [
            'payment_id' => $payment->id,
            'vs' => $payment->variable_symbol
          ]
        );

        $this->httpResponse->redirect($url);
        exit();
      } catch (HttpException $ex) {
        // echo $ex->statusCode;
        dump($ex->getMessage());
      }

    }

    public function complete($payment): ?bool
    {
      $this->initialize();

      $order_id = $this->paymentMetaRepository->findByPaymentAndKey($payment, 'order_id');

      $order = $this->client->execute(new OrdersGetRequest($order_id->value));

      if (
        $order->statusCode == 200 &&
        $order->result->status == "COMPLETED"
      ) {
        return true;
      }

      return false;
    }

}
