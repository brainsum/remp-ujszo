<?php

namespace Crm\UjszoUsersModule;

use Crm\ApplicationModule\CrmModule;
use Nette\Application\Routers\RouteList;
use Nette\Application\Routers\Route;

class UjszoUsersModule extends CrmModule {

    /**
   * {@inheritdoc}
   */
  public function registerRoutes(RouteList $router) {
    //users/users/settings
    $router[] = new Route('users/users/settings', 'UjszoUsers:Profile:default');
  }

}