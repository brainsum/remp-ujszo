<?php

namespace Crm\UjszoModule\Events;

use Crm\ApplicationModule\User\UserData;
use Crm\UsersModule\User\IUserGetter;
use Crm\UsersModule\Repository\UsersRepository;
use Crm\UsersModule\Repository\PasswordResetTokensRepository;
use Crm\UsersModule\Auth\UserAuthenticator;
use League\Event\AbstractListener;
use League\Event\EventInterface;
use Nette\Security\User;

class UserChangePasswordRequestEventHandler extends AbstractListener
{

    private $userAuthenticator;

    public function __construct(
        UserAuthenticator $userAuthenticator
    ) {
        $this->userAuthenticator = $userAuthenticator;
    }

    public function handle(EventInterface $event)
    {
        $user = $event->getUser();

        $token = $event->getToken();

        $client = new \GuzzleHttp\Client();
        $mailer_host = getenv('MAILER_ADDR');
        $sso_token = getenv('SSO_TOKEN');

        $url = $mailer_host . '/api/v1/mailers/send-email';
        $body = [
            "mail_template_code" => "forgotten_password",
            "email" => $user->email,
            "params" => [
                'email' => $user->email,
                'token' => $token
            ]
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
