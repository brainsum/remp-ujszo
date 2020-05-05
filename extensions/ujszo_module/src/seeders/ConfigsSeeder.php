<?php

namespace Crm\UjszoModule\Seeders;

use Crm\ApplicationModule\Builder\ConfigBuilder;
use Crm\ApplicationModule\Config\ApplicationConfig;
use Crm\ApplicationModule\Config\Repository\ConfigCategoriesRepository;
use Crm\ApplicationModule\Config\Repository\ConfigsRepository;
use Crm\ApplicationModule\Seeders\ConfigsTrait;
use Crm\ApplicationModule\Seeders\ISeeder;
use Nette\Database\Connection;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigsSeeder implements ISeeder
{
    use ConfigsTrait;

    private $configCategoriesRepository;

    private $configsRepository;

    private $configBuilder;

    public function __construct(
        ConfigCategoriesRepository $configCategoriesRepository,
        ConfigsRepository $configsRepository,
        ConfigBuilder $configBuilder
    ) {
        $this->configCategoriesRepository = $configCategoriesRepository;
        $this->configsRepository = $configsRepository;
        $this->configBuilder = $configBuilder;
    }

    public function seed(OutputInterface $output)
    {
      $categoryName = 'payments.config.category';
      $category = $this->configCategoriesRepository->loadByName($categoryName);
      if (!$category) {
        $category = $this->configCategoriesRepository->add($categoryName, 'fa fa-credit-card', 300);
        $output->writeln('  <comment>* config category <info>Platby</info> created</comment>');
      } else {
        $output->writeln('  * config category <info>Platby</info> exists');
      }


      $this->addConfig(
        $output,
        $category,
        'paypal_client_id',
        ApplicationConfig::TYPE_STRING,
        'ujszo.config.paypal.client_id.label',
        'ujszo.config.paypal.client_id.description',
        '',
        1014
      );

      $this->addConfig(
        $output,
        $category,
        'paypal_client_secret',
        ApplicationConfig::TYPE_STRING,
        'ujszo.config.paypal.client_secret.label',
        'ujszo.config.paypal.client_secret.description',
        '',
        1014
      );
    }
}
