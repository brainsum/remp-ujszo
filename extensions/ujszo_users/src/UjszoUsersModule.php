<?php

namespace Crm\UjszoUsersModule;

use Crm\ApplicationModule\CrmModule;
use Crm\ApplicationModule\Menu\MenuContainerInterface;
use Crm\ApplicationModule\Menu\MenuItem;
use Crm\UjszoUsersModule\Events\UserCreatedEventHandler;
use Crm\UjszoUsersModule\Events\UserUpdatedEventHandler;
use Crm\UsersModule\Events\UserCreatedEvent;
use Crm\UsersModule\Events\UserUpdatedEvent;
use Kdyby\Translation\Translator;
use League\Event\Emitter;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;
use Nette\DI\Container;
use Nette\Security\User;

class UjszoUsersModule extends CrmModule {

  private $user;

  public function __construct(
    Container $container,
    Translator $translator,
    User $user
  ) {
    parent::__construct($container, $translator);
    $this->user = $user;
  }

  /**
   * {@inheritdoc}
   */
  public function registerRoutes(RouteList $router) {
    //users/users/settings
    $router[] = new Route('profile', 'UjszoUsers:Profile:default');
    $router[] = new Route('sign/in', 'UjszoUsers:Sign:in');
    $router[] = new Route('sign/up', 'UjszoUsers:Sign:up');
    $router[] = new Route('sign/out', 'UjszoUsers:Sign:out');
    $router[] = new Route('users/sign/out', 'UjszoUsers:Sign:out');
  }

  public function registerFrontendMenuItems(MenuContainerInterface $menuContainer)
  {
    if ($this->user->isLoggedIn()) {
      $menuItem = new MenuItem($this->translator->translate('ujszo_users.menu.profile'), ':UjszoUsers:Profile:default', '', 1, true);
      $menuContainer->attachMenuItem($menuItem);
    }
  }

  public function registerEventHandlers(Emitter $emitter)
  {
    $emitter->addListener(
      UserCreatedEvent::class,
      $this->getInstance(UserCreatedEventHandler::class)
    );

    $emitter->addListener(
      UserUpdatedEvent::class,
      $this->getInstance(UserUpdatedEventHandler::class)
    );
  }

}