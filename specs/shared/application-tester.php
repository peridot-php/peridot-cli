<?php
use Evenement\EventEmitter;
use Peridot\Configuration;
use Peridot\Cli\Application;
use Peridot\Cli\Command;
use Peridot\Cli\Environment;
use Peridot\Cli\InputDefinition;
use Peridot\Core\Suite;
use Peridot\Reporter\ReporterFactory;
use Peridot\Core\Runner;
use Symfony\Component\Console\Output\BufferedOutput;

beforeEach(function() {
    $this->emitter = new EventEmitter();
    $suite = new Suite("suite", function() {});
    $this->runner = new Runner($suite, $this->emitter);
    $this->output = new BufferedOutput();
    $this->factory = new ReporterFactory($this->output, $this->emitter);
    $this->definition = new InputDefinition();

    $this->configPath = __DIR__  . '/../../fixtures/peridot.php';
    $this->environment = new Environment($this->definition, $this->emitter, ['c' => $this->configPath]);
    $this->application = new Application($this->environment);

    $this->command = new Command($this->runner, $this->factory, $this->emitter);
    $this->command->setApplication($this->application);
});