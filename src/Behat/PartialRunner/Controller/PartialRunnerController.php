<?php

namespace Behat\PartialRunner\Controller;

use Behat\Gherkin\Gherkin;
use Behat\Testwork\Cli\Controller;
use InvalidArgumentException;
use Behat\PartialRunner\Filter\PartialRunnerFilter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PartialRunnerController implements Controller
{
    public function __construct(
        private readonly Gherkin $gherkin,
    ) {
    }

    public function configure(Command $command): void
    {
        $command
            ->addOption(
                '--count-workers',
                null,
                InputOption::VALUE_REQUIRED,
                'Specify the count of workers',
                1,
            )
            ->addOption(
                '--worker-number',
                null,
                InputOption::VALUE_REQUIRED,
                'Number of current worker',
                0,
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $total = (int) $input->getOption('count-workers');
        $worker = (int) $input->getOption('worker-number');

        if ($total < 0 || $worker < 0) {
            throw new InvalidArgumentException("--worker-number ($worker) and --count-workers ($total) must be greater than 0.");
        }

        if ($worker >= $total) {
            throw new InvalidArgumentException("--worker-number ($worker) must be less than --count-workers ($total).");
        }

        $this->gherkin->addFilter(new PartialRunnerFilter($total, $worker));

        return null;
    }
}
