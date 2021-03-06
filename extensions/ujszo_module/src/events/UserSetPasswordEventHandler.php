<?php

namespace Crm\UjszoModule\Events;

use Crm\UsersModule\User\IUserGetter;
use Crm\UsersModule\Repository\UsersRepository;
use Crm\UsersModule\Repository\PasswordResetTokensRepository;
use Crm\UsersModule\Auth\UserAuthenticator;
use League\Event\AbstractListener;
use League\Event\EventInterface;
use Nette\Security\User;
use GuzzleHttp\Client as HttpClient;

class UserSetPasswordEventHandler extends AbstractListener
{

    private $userAuthenticator;

    public function __construct(
        UserAuthenticator $userAuthenticator
    ) {
        $this->userAuthenticator = $userAuthenticator;
    }

    public function handle(EventInterface $event)
    {
        if ($event->shouldNotify()) {
            $user = $event->getUser();

            $client = new HttpClient();
            $mailer_host = getenv('MAILER_ADDR');
            $sso_token = getenv('SSO_TOKEN');

            $url = $mailer_host . '/api/v1/mailers/send-email';
            $body = [
                "mail_template_code" => "password_set",
                "email" => $user->email
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

}
