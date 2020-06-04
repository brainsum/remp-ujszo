<?php

namespace Crm\UjszoModule\Events;

use Crm\ApplicationModule\User\UserData;
use Crm\UsersModule\User\IUserGetter;
use Crm\UsersModule\Repository\UsersRepository;
use Crm\UsersModule\Repository\PasswordResetTokensRepository;
use League\Event\AbstractListener;
use League\Event\EventInterface;
use Nette\Security\User;
use GuzzleHttp\Client as HttpClient;
use Tracy\Debugger;

class NotificationEventHandler extends AbstractListener
{

    private $handledTemplateCodes = [
      'reset_password_with_password'
    ];

    public function handle(EventInterface $event)
    {
      $user = $event->getUser();
      $params = $event->getParams();
      $template_code = $event->getTemplateCode();

      if (in_array($template_code, $this->handledTemplateCodes)) {
        $client = new HttpClient();
        $mailer_host = getenv('MAILER_ADDR');
        $sso_token = getenv('SSO_TOKEN');

        $url = $mailer_host . '/api/v1/mailers/send-email';
        $body = [
          "mail_template_code" => $template_code,
          "email" => $user->email,
          "params" => $params
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
