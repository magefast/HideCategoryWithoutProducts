<?php

namespace Dragonfly\HideCategoryWithoutProducts\Console\Command;

use Dragonfly\HideCategoryWithoutProducts\Service\HideCategoryWithoutProducts;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Processing extends Command
{
    /**
     * @var HideCategoryWithoutProducts
     */
    private HideCategoryWithoutProducts $service;

    public function __construct(
        HideCategoryWithoutProducts $service,
        string  $name = null)
    {
        $this->service = $service;
        parent::__construct($name);
    }

    /**
     * Initialization of the command.
     */
    protected function configure()
    {
        $this->setName('hide:category');
        $this->setDescription('Hide Categories without products');
        parent::configure();
    }

    /**
     * CLI command description.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     * @throws LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->service->execute();
    }
}
