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

class UserCreatedEventHandler extends AbstractListener
{
    private $userData;

    private $usersRepository;

    private $passwordResetTokensRepository;

    public function __construct(
        UserData $userData,
        UsersRepository $usersRepository,
        PasswordResetTokensRepository $passwordResetTokensRepository
    ) {
        $this->userData = $userData;
        $this->passwordResetTokensRepository = $passwordResetTokensRepository;
        $this->usersRepository = $usersRepository;
    }

    public function handle(EventInterface $event)
    {
        if ($event->sendEmail()) {
          $password = $event->getOriginalPassword();
          $user = $event->getUser();

          $passwordResetToken = $this->passwordResetTokensRepository->add($user);

          $client = new HttpClient();
          $mailer_host = getenv('MAILER_ADDR');
          $sso_token = getenv('SSO_TOKEN');

          $url = $mailer_host . '/api/v1/mailers/send-email';
          $body = [
            "mail_template_code" => "user_created",
            "email" => $user->email,
            "params" => [
              'email' => $user->email,
              'token' => $passwordResetToken->token
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
