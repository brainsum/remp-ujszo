<?php

namespace Crm\UjszoModule\Seeders;

use Crm\ApplicationModule\Seeders\ISeeder;
use Crm\PaymentsModule\Repository\PaymentGatewaysRepository;
use Symfony\Component\Console\Output\OutputInterface;

class PaymentGatewaysSeeder implements ISeeder
{
    private $paymentGatewaysRepository;

    public function __construct(PaymentGatewaysRepository $paymentGatewaysRepository)
    {
        $this->paymentGatewaysRepository = $paymentGatewaysRepository;
    }

    public function seed(OutputInterface $output)
    {
        if (!$this->paymentGatewaysRepository->exists('paypal_button')) {
            $this->paymentGatewaysRepository->add(
                'Paypal Button',
                'paypal_button',
                10,
                true
            );
            $output->writeln('  <comment>* payment gateway <info>paypal button</info> created</comment>');
        } else {
            $output->writeln('  * payment gateway <info>paypal button</info> exists');
        }
    }
}
