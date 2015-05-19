<?php
namespace Peridot\Cli;

use Peridot\EventEmitterInterface;
use Peridot\Configuration;
use Peridot\Core\HasEventEmitterTrait;
use Peridot\Core\TestResult;
use Peridot\Reporter\ReporterFactory;
use Peridot\Core\Runner;
use Peridot\Core\RunnerInterface;
use Peridot\Core\SuiteLoader;
use Peridot\Core\SuiteLoaderInterface;
use Peridot\Core\Context;
use Symfony\Component\Console\Command\Command as ConsoleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The default Peridot CLI command. Responsible for loading and
 * executing tests.
 *
 * @package Peridot\Console
 */
class Command extends ConsoleCommand
{
    use HasEventEmitterTrait;

    /**
     * @var \Peridot\Core\RunnerInterface
     */
    protected $runner;

    /**
     * @var \Peridot\Reporter\ReporterFactory
     */
    protected $factory;

    /**
     * @var \Peridot\Core\Context
     */
    protected $context;

    /**
     * @var \Peridot\Core\SuiteLoaderInterface
     */
    protected $loader;

    /**
     * @param ReporterFactory $factory
     * @param EventEmitterInterface $eventEmitter
     */
    public function __construct(
        ReporterFactory $factory,
        EventEmitterInterface $eventEmitter,
        Context $context
    ) {
        parent::__construct('peridot');
        $this->factory = $factory;
        $this->eventEmitter = $eventEmitter;
        $this->context = $context;
    }

    /**
     * Set the loader used by the Peridot command
     *
     * @param SuiteLoaderInterface $loader
     * @return $this
     */
    public function setLoader(SuiteLoaderInterface $loader)
    {
        $this->loader = $loader;
        return $this;
    }

    /**
     * Fetch the loader used by the Peridot command. Defaults to
     * a glob based loader
     *
     * @return SuiteLoaderInterface
     */
    public function getLoader()
    {
        if ($this->loader === null) {
            $this->loader = new SuiteLoader('*.spec.php', $this->context);
        }
        return $this->loader;
    }

    /**
     * Set the suite runner used by the Peridot command.
     *
     * @param RunnerInterface $runner
     * @return $this
     */
    public function setRunner(RunnerInterface $runner)
    {
        $this->runner = $runner;
        return $this;
    }

    /**
     * Get the RunnerInterface being used by the Peridot command.
     * If one is not set, a default Runner will be used.
     *
     * @return RunnerInterface
     */
    public function getRunner()
    {
        if ($this->runner === null) {
            $this->runner = new Runner(
                $this->context->getCurrentSuite(),
                $this->getEventEmitter()
            );
        }
        return $this->runner;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getSynopsis($short = false)
    {
        return $this->getName() . ' [options] [files]';
    }

    /**
     * {@inheritdoc}
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        $this->eventEmitter->emit('peridot.execute', $input, $output);
        $this->eventEmitter->emit('peridot.reporters', $input, $this->factory);
        return parent::run($input, $output);
    }

    /**
     * {@inheritdoc}
     *
     * Configures Peridot based on user input.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->eventEmitter->emit('peridot.load', $this);
        $this->getRunner()->setStopOnFailure($input->getOption('bail'));

        $reporter = $input->getOption('reporter') ?: 'spec';
        $reporter = $this->factory->create($reporter);
        $reporter->setColorsEnabled(! $input->getOption('no-colors'));

        $grep = $input->getOption('grep') ?: '*.spec.php';
        $this->getLoader()->setPattern($grep);
    }

    /**
     * Load and run Suites and Tests
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('reporters')) {
            $this->listReporters($output);
            return 0;
        }

        $path = $input->getArgument('path') ?: getcwd() . '/specs';
        return $this->getResult($path);
    }

    /**
     * Output available reporters
     *
     * @param OutputInterface $output
     */
    protected function listReporters(OutputInterface $output)
    {
        $output->writeln("");
        foreach ($this->factory->getReporters() as $name => $info) {
            $output->writeln(sprintf("    %s - %s", $name, $info['description']));
        }
        $output->writeln("");
    }

    /**
     * Return the result as an integer.
     *
     * @return int
     */
    protected function getResult($path)
    {
        $result = new TestResult($this->eventEmitter);
        $this->getLoader()->load($path);
        $this->getRunner()->run($result);

        if ($result->getFailureCount() > 0) {
            return 1;
        }

        return 0;
    }
}
