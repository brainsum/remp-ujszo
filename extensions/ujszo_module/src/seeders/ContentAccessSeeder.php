<?php

namespace Crm\UjszoModule\Seeders;

use Crm\ApplicationModule\Seeders\ISeeder;
use Crm\SubscriptionsModule\Repository\ContentAccessRepository;
use Symfony\Component\Console\Output\OutputInterface;

class ContentAccessSeeder implements ISeeder
{
    private $contentAccessRepository;

    /** @var OutputInterface */
    private $output;

    public function __construct(ContentAccessRepository $contentAccessRepository)
    {
        $this->contentAccessRepository = $contentAccessRepository;
    }

    public function seed(OutputInterface $output)
    {
        $this->output = $output;

        $name = 'ujszo';
        $description = 'Ujszo.com';
        $class = 'label label-primary';
        $sorting = 10;
        $this->seedContentAccess($name, $description, $class, $sorting);

        $name = 'vasarnap';
        $description = 'Vasarnap.com';
        $class = 'label label-danger';
        $sorting = 20;
        $this->seedContentAccess($name, $description, $class, $sorting);
    }

    private function seedContentAccess($name, $description, $class = '', $sorting = 100)
    {
        if (!$this->contentAccessRepository->exists($name)) {
            $this->contentAccessRepository->add(
                $name,
                $description,
                $class,
                $sorting
            );
            $this->output->writeln("  <comment>* content access <info>{$name}</info> created</comment>");
        } else {
            $seedId = $this->contentAccessRepository->getId($name);
            $seed = $this->contentAccessRepository->find($seedId);
            $this->contentAccessRepository->update($seed, [
                'name' => $name,
                'description' => $description,
                'class' => $class,
                'sorting' => $sorting
            ]);
            $this->output->writeln("  * content access <info>{$name}</info> updated");
        }
    }
}
