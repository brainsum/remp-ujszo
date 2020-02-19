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

class UserChangePasswordEventHandler extends AbstractListener
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
        $r = $this->userAuthenticator->authenticate(['username' => $user->email, 'alwaysLogin' => true]);
    }

}
