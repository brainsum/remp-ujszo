<?php

namespace Crm\UjszoModule;

use Crm\ApiModule\Api\ApiRoutersContainerInterface;
use Crm\ApiModule\Router\ApiIdentifier;
use Crm\ApiModule\Router\ApiRoute;
use Crm\ApplicationModule\Commands\CommandsContainerInterface;
use Crm\ApplicationModule\Seeders\CalendarSeeder;
use Crm\ApplicationModule\Seeders\CountriesSeeder;
use Crm\ApplicationModule\Seeders\SnippetsSeeder;
use Crm\ApplicationModule\SeederManager;
use Crm\ApplicationModule\CrmModule;
use Crm\ApplicationModule\LayoutManager;
use Crm\UjszoModule\Seeders\PaymentGatewaysSeeder;
use Crm\UjszoModule\Seeders\ConfigsSeeder;
use League\Event\Emitter;
use Nette\DI\Container;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;

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

    // $emitter->addListener(
    //   \Crm\SubscriptionsModule\Events\SubscriptionUpdatedEvent::class,
    //   $this->getInstance(\Crm\UjszoModule\Events\SubscriptionEventHandler::class)
    // );

    $emitter->addListener(
      \Crm\UsersModule\Events\UserChangePasswordEvent::class,
      $this->getInstance(\Crm\UjszoModule\Events\UserChangePasswordEventHandler::class)
    );

    $emitter->addListener(
      \Crm\UsersModule\Events\UserChangePasswordRequestEvent::class,
      $this->getInstance(\Crm\UjszoModule\Events\UserChangePasswordRequestEventHandler::class)
    );

    $emitter->addListener(
      \Crm\PaymentsModule\Events\PaymentChangeStatusEvent::class,
      $this->getInstance(\Crm\UjszoModule\Events\PaymentChangeStatusEventHandler::class),
      600
    );

    $emitter->addListener(
      \Crm\InvoicesModule\Events\InvoiceCreatedEvent::class,
      $this->getInstance(\Crm\UjszoModule\Events\InvoiceCreatedEventHandler::class)
    );
  }

  public function registerLayouts(LayoutManager $layoutManager)
  {
    $layoutManager->registerLayout('ujszo', realpath(__DIR__ . '/templates/@ujszo_layout.latte'));
    $layoutManager->registerLayout('ujszo_plain', realpath(__DIR__ . '/templates/@ujszo_layout_plain.latte'));
  }

  public function registerAuthenticators(\Crm\ApplicationModule\Authenticator\AuthenticatorManagerInterface $authenticatorManager)
  {
    $authenticatorManager->registerAuthenticator(
      $this->getInstance(\Crm\UjszoModule\Authenticator\ContentAuthenticator::class)
    );
  }

  public function registerCommands(CommandsContainerInterface $commandsContainer)
  {
    $commandsContainer->registerCommand($this->getInstance(\Crm\UjszoModule\Commands\EndingSubscriptionsCommand::class));
  }


  public function registerSeeders(SeederManager $seederManager)
  {
    $seederManager->addSeeder($this->getInstance(PaymentGatewaysSeeder::class));
    $seederManager->addSeeder($this->getInstance(ConfigsSeeder::class));
  }
}
