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
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersGetRequest;

class Paypal extends GatewayAbstract
{
    private const CLIENT_ID = 'ATS2lBak3X2HLnOqu8dVHAEk4wTlpt8r3DGiZUJoQ4QYmI_aNJsu7nDPyh31h8W1XszrH7kFvXePldn6';

    private const CLIENT_SECRET = 'EEne5Hhr8BUWIyOTAy3f6krb6ggiAHPn4KRsFn3eIaDHhJuGjeRXepwxXlcfEBhNO6LSF1DiGWt57rYt';

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
      $environment = new SandboxEnvironment(self::CLIENT_ID, self::CLIENT_SECRET);
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
      }catch (HttpException $ex) {
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
