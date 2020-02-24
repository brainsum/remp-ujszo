<?php

namespace Crm\UjszoModule;

use Crm\ApiModule\Api\ApiRoutersContainerInterface;
use Crm\ApiModule\Router\ApiIdentifier;
use Crm\ApiModule\Router\ApiRoute;
use Crm\ApplicationModule\Commands\CommandsContainerInterface;
use Crm\ApplicationModule\Seeders\CalendarSeeder;
use Crm\ApplicationModule\Seeders\ConfigsSeeder;
use Crm\ApplicationModule\Seeders\CountriesSeeder;
use Crm\ApplicationModule\Seeders\SnippetsSeeder;
use Crm\ApplicationModule\CrmModule;
use Crm\ApplicationModule\LayoutManager;
use League\Event\Emitter;
use Nette\DI\Container;

class UjszoModule extends CrmModule
{

  public function registerEventHandlers(\League\Event\Emitter $emitter)
  {
    $emitter->addListener(
      \Crm\UsersModule\Events\UserCreatedEvent::class,
      $this->getInstance(\Crm\UjszoModule\Events\UserCreatedEventHandler::class)
    );

    $emitter->addListener(
      \Crm\SubscriptionsModule\Events\NewSubscriptionEvent::class,
      $this->getInstance(\Crm\UjszoModule\Events\SubscriptionEventHandler::class)
    );

    $emitter->addListener(
      \Crm\SubscriptionsModule\Events\SubscriptionUpdatedEvent::class,
      $this->getInstance(\Crm\UjszoModule\Events\SubscriptionEventHandler::class)
    );

    $emitter->addListener(
      \Crm\UsersModule\Events\UserChangePasswordEvent::class,
      $this->getInstance(\Crm\UjszoModule\Events\UserChangePasswordEventHandler::class)
    );

    $emitter->addListener(
      \Crm\UsersModule\Events\UserChangePasswordRequestEvent::class,
      $this->getInstance(\Crm\UjszoModule\Events\UserChangePasswordRequestEventHandler::class)
    );
  }

  public function registerLayouts(LayoutManager $layoutManager)
  {
    $layoutManager->registerLayout('ujszo', realpath(__DIR__ . '/templates/@ujszo_layout.latte'));
  }

  public function registerAuthenticators(\Crm\ApplicationModule\Authenticator\AuthenticatorManagerInterface $authenticatorManager)
  {
    $authenticatorManager->registerAuthenticator(
      $this->getInstance(\Crm\UjszoModule\Authenticator\ContentAuthenticator::class)
    );
  }
}
