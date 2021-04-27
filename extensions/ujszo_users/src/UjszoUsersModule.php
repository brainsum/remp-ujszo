<?php

namespace Crm\UjszoUsersModule;

use Crm\ApplicationModule\CrmModule;
use Crm\UjszoUsersModule\Events\UserCreatedEventHandler;
use Crm\UsersModule\Events\UserCreatedEvent;
use League\Event\Emitter;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;

class UjszoUsersModule extends CrmModule {

    /**
   * {@inheritdoc}
   */
  public function registerRoutes(RouteList $router) {
    //users/users/settings
    $router[] = new Route('users/users/settings', 'UjszoUsers:Profile:default');
    $router[] = new Route('sign/in', 'UjszoUsers:Sign:in');
    $router[] = new Route('sign/up', 'UjszoUsers:Sign:up');
    $router[] = new Route('sign/out', 'UjszoUsers:Sign:out');
    $router[] = new Route('users/sign/out', 'UjszoUsers:Sign:out');
  }

  public function registerEventHandlers(Emitter $emitter)
  {
    $emitter->addListener(
      UserCreatedEvent::class,
      $this->getInstance(UserCreatedEventHandler::class)
    );
  }

}