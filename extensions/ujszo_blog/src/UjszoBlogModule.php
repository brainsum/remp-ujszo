<?php

namespace Crm\UjszoBlogModule;

use Crm\ApplicationModule\CrmModule;
use Crm\ApplicationModule\Menu\MenuContainerInterface;
use Crm\ApplicationModule\Menu\MenuItem;
use Crm\UsersModule\Auth\Permissions;
use Crm\UsersModule\Repository\UsersRepository;
use Kdyby\Translation\Translator;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;
use Nette\DI\Container;
use Nette\Security\User;

class UjszoBlogModule extends CrmModule {

  private $user;

  private $permissions;

  private $usersRepository;

  public function __construct(
    Container $container,
    Translator $translator,
    User $user,
    Permissions $permissions,
    UsersRepository $usersRepository
  ) {
    parent::__construct($container, $translator);
    $this->user = $user;
    $this->permissions = $permissions;
    $this->usersRepository = $usersRepository;
  }

  public function registerFrontendMenuItems(MenuContainerInterface $menuContainer)
  {
    if ($this->user->isLoggedIn()) {
      $menuItem = new MenuItem($this->translator->translate('blog.menu.dashboard'), ':UjszoBlog:Blog:default', '', 0, true);
      $menuContainer->attachMenuItem($menuItem);
    }
  }

}