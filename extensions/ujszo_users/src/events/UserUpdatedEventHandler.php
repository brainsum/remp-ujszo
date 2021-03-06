<?php

namespace Crm\UjszoUsersModule\Events;

use Crm\UsersModule\User\IUserGetter;
use Crm\UsersModule\Repository\UsersRepository;
use Crm\UsersModule\Repository\PasswordResetTokensRepository;
use League\Event\AbstractListener;
use League\Event\EventInterface;
use Nette\Security\User;
use GuzzleHttp\Client as HttpClient;
use Tracy\Debugger;
use Crm\UjszoUsersModule\Repository\DrupalUserRepository;

class UserUpdatedEventHandler extends AbstractListener
{
    private $usersRepository;

    private $drupalUserRepository;

    public function __construct(
      UsersRepository $usersRepository,
      DrupalUserRepository $drupalUserRepository
    ) {
      $this->drupalUserRepository = $drupalUserRepository;
      $this->usersRepository = $usersRepository;
    }

    public function handle(EventInterface $event)
    {
      $updatedUser = $event->getUser();

      $this->drupalUserRepository->syncUser($updatedUser);
    }

}
