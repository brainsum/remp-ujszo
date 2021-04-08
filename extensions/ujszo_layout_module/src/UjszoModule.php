<?php

namespace Crm\UjszoLayoutModule;

use Crm\ApplicationModule\CrmModule;
use Crm\ApplicationModule\LayoutManager;

class UjszoLayoutModule extends CrmModule
{

  public function registerLayouts(LayoutManager $layoutManager)
  {
    $layoutManager->registerLayout('ujszo', realpath(__DIR__ . '/templates/@ujszo_layout.latte'));
    $layoutManager->registerLayout('ujszo_plain', realpath(__DIR__ . '/templates/@ujszo_layout_plain.latte'));
  }

}