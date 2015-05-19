<?php
namespace Peridot\Cli;

use Peridot\Configuration;
use Peridot\Reporter\ReporterFactory;
use Peridot\Core\Runner;
use Peridot\Core\RunnerInterface;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Peridot\EventEmitter;

/**
 * The main Peridot application class.
 *
 * @package Peridot\Console
 */
class Application extends ConsoleApplication
{
    /**
     * @var Environment
     */
    protected $environment;

    /**
     * @var RunnerInterface
     */
    protected $runner;

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @param Environment $environment
     * @param string $name
     * @param string $version
     */
    public function __construct(Environment $environment, $name, $version)
    {
        $this->environment = $environment;
        $this->validateConfiguration();
        $this->environment->getEventEmitter()->emit('peridot.start', $this->environment, $this);
        parent::__construct($name, $version);
    }

    /**
     * {@inheritdoc}
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return int
     */
    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        if ($input !== null) {
            $in = $input;
        } else {
            $in = $this->getInput();
        }

        return parent::run($in, $output);
    }

    /**
     * Run the Peridot application
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $command = $this->getPeridotCommand($output);
        $this->add($command);
        $exitCode = parent::doRun($input, $output);
        $this->environment->getEventEmitter()->emit('peridot.end', $exitCode, $input, $output);
        return $exitCode;
    }

    /**
     * Get the Peridot command used to execute tests.
     *
     * @param OutputInterface $output
     * @return Peridot\Cli\Command
     */
    public function getPeridotCommand($output)
    {
        $factory = new ReporterFactory($output, $this->environment->getEventEmitter());
        $emitter = $this->environment->getEventEmitter();
        $context = $this->environment->getContext();
        return new Command($factory, $emitter, $context);
    }

    /**
     * Fetch the ArgvInput used by Peridot. If any exceptions are thrown due to
     * a mismatch between the option or argument requested and the input definition, the
     * exception will be rendered and Peridot will exit with an error code.
     *
     * @param array $argv An array of parameters from the CLI in the argv format.
     * @return ArgvInput
     */
    public function getInput(array $argv = null)
    {
        try {
            return new ArgvInput($argv, $this->environment->getDefinition());
        } catch (\Exception $e) {
            $this->renderException($e, new ConsoleOutput());
            exit(1);
        }
    }

    /**
     * Return's peridot as the sole command used by Peridot
     *
     * @param  InputInterface $input
     * @return string
     */
    public function getCommandName(InputInterface $input)
    {
        return 'peridot';
    }

    /**
     * Return the Environment used by the Peridot application.
     *
     * @return Environment
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * Factory method to create a new Application
     *
     * @param string $name
     * @param string $version
     * @param array $args
     * @return Application
     */
    public static function create($name, $version, $args)
    {
        $parser = new CliOptionParser(['-c', '--configuration'], $args);
        $environment = new Environment(
            new InputDefinition(),
            new EventEmitter(),
            $parser->parse()
        );
        return new Application($environment, $name, $version);
    }

    /**
     * Return the peridot input definition defined by Environment
     *
     * @return InputDefinition
     */
    protected function getDefaultInputDefinition()
    {
        return $this->environment->getDefinition();
    }

    /**
     * Validate that a supplied configuration exists.
     *
     * @return void
     */
    protected function validateConfiguration()
    {
        if (!$this->environment->load(getcwd() . DIRECTORY_SEPARATOR . 'peridot.php')) {
            fwrite(STDERR, "Configuration file specified but does not exist" . PHP_EOL);
            exit(1);
        }
    }
}
