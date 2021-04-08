<?php

namespace Crm\UjszoModule\Events;

use Crm\SubscriptionsModule\Repository\SubscriptionsRepository;
use Crm\UsersModule\Repository\UsersRepository;
use Crm\UsersModule\User\ISubscriptionGetter;
use Crm\UsersModule\User\IUserGetter;
use League\Event\AbstractListener;
use League\Event\Emitter;
use League\Event\EventInterface;
use Nette\Security\User;
use Crm\SalesFunnelModule\Repository\SalesFunnelsStatsRepository;

class SalesFunnelEventHandler extends AbstractListener {

  public function __construct(
    UsersRepository $usersRepository,
    Emitter $emitter,
    SubscriptionsRepository $subscriptionsRepository
  ) {
    $this->usersRepository = $usersRepository;
    $this->subscriptionsRepository = $subscriptionsRepository;
    $this->emitter = $emitter;
  }

  public function handle(EventInterface $event)
  {
    // dump($event->getSalesFunnel());

    // dump($event->getType());

    // dump($event->getEmail());

    // dump($event->getDeviceType());

    // dump($event->getUserAgent());

    // dump($this->getSession('sales_funnel'));
  }

}