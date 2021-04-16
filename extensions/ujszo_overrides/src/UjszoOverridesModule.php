<?php

namespace Crm\UjszoOverridesModule;

use Crm\ApplicationModule\CrmModule;
use Nette\Application\Routers\RouteList;
use Nette\Application\Routers\Route;

class UjszoOverridesModule extends CrmModule {

  /**
   * {@inheritdoc}
   */
  public function registerRoutes(RouteList $router) {
    //users/users/settings
    $router[] = new Route('users/users/settings', 'Users:Users:settings');
    $router[] = new Route('subscriptions/subscriptions/my', 'Users:Users:settings');
    $router[] = new Route('payments/payments/my', 'Users:Users:settings');
    $router[] = new Route('products/orders/my', 'Users:Users:settings');
    $router[] = new Route('print/change-user-address-request/change-address-request', 'Users:Users:settings');
    $router[] = new Route('invoices/invoices/invoice-details', 'Users:Users:settings');
  }

}