<?php
use Peridot\Cli\Command;
use Peridot\Core\Suite;
use Peridot\Core\Test;
use Peridot\Core\Runner;
use Peridot\Core\RunnerInterface;
use Peridot\Core\SuiteLoader;
use Peridot\Reporter\SpecReporter;
use Symfony\Component\Console\Input\ArrayInput;
use Prophecy\Prophet;
use Prophecy\Argument;

describe('Command', function() {

    include __DIR__ . '/shared/application-tester.php';
    $this->prophet = new Prophet();

    describe('loader accessors', function() {
        it('should allow setting and getting of loader', function() {
            $loader = new SuiteLoader('*.stub.php');
            $this->command->setLoader($loader);
            assert($loader === $this->command->getLoader(), 'loader should be accessible from command');
        });

        it('should allow getting a default loader', function() {
            assert(!is_null($this->command->getLoader()), 'command should have default loader');
        });
    });

    describe('runner accessors', function() {
        beforeEach(function() {
            $this->runner = new Runner(new Suite('desc', function() {}), $this->environment->getEventEmitter());
        });

        it('should allow access to runner', function() {
            $this->command->setRunner($this->runner);
            assert($this->command->getRunner() === $this->runner);
        });

        context('when getting Runner', function() {
            it('should return a default runner if none set', function() {
                $runner = $this->command->getRunner();
                assert($runner instanceof RunnerInterface);
            });
        });
    });

    describe('->getSynopsis()', function() {
        it('should return a simplified synopsis', function() {
            $synopsis = $this->command->getSynopsis();
            $expected = $this->command->getName()  . ' [options] [files]';
            assert($synopsis == $expected);
        });
    });

    describe('->run()', function() {
        it('should emit an execute event', function() {
            $input = null;
            $output = null;
            $this->emitter->on('peridot.execute', function($i, $o) use (&$input, &$output) {
                $input = $i;
                $output = $o;
            });

            $this->command->run(new ArrayInput([], $this->definition), $this->output);

            assert(!is_null($input), "input should have been received by event");
            assert(!is_null($output), "output should have been received by event");
        });

        it('should emit a reporters event', function() {
            $input = null;
            $factory = null;
            $this->emitter->on('peridot.reporters', function($i, $f) use (&$input, &$factory) {
                $input = $i;
                $factory = $f;
            });

            $this->command->run(new ArrayInput([], $this->definition), $this->output);

            assert(!is_null($input), "input should have been received by event");
            assert($factory === $this->factory, "reporter factory should have been received by event");
        });

        it('should set the runner stop on failure option', function () {
            $this->command->run(new ArrayInput(['--bail' => true], $this->definition), $this->output);
            assert($this->runner->shouldStopOnFailure() === true);
        });

        /**
         * Create a Command with a mocked reporter factory.
         */
        $withMockFactory = function () {
            $this->factory = $this->prophet->prophesize('Peridot\Reporter\ReporterFactory');
            $this->command = new Command($this->factory->reveal(), $this->emitter);
            $this->command->setRunner($this->runner);
            $this->command->setApplication($this->application);
        };

        context('when using the --no-colors option', function () use ($withMockFactory) {
            beforeEach($withMockFactory);
            afterEach([$this->prophet, 'checkPredictions']);

            it('allows setting whether the reporter uses colors', function () {
                $reporter = new SpecReporter($this->output, $this->emitter);
                $this->factory->create(Argument::any())->willReturn($reporter);

                $this->command->run(new ArrayInput(['--no-colors' => true]));

                assert($reporter->areColorsEnabled() === false);
            });
        });

        context('when using the -r option', function () use ($withMockFactory) {
            beforeEach($withMockFactory);
            afterEach([$this->prophet, 'checkPredictions']);

            it('allows setting which reporter to use', function () {
                $reporter = new SpecReporter($this->output, $this->emitter);

                $this->factory->create('anon')->willReturn($reporter);

                $this->command->run(new ArrayInput(['-r' => 'anon']));
            });
        });

        context('when using the --grep option', function () {
            it('sets the pattern used by the loader', function () {
                $this->command->run(new ArrayInput(['--grep' => '*.test.php'], $this->definition), $this->output);
                $pattern = $this->command->getLoader()->getPattern();
                assert($pattern === '*.test.php');
            });
        });

        context('when using the --reporters option', function() {
            it('should list reporters', function() {
                $this->command->run(new ArrayInput(['--reporters' => true], $this->definition), $this->output);
                $reporters = $this->factory->getReporters();
                $content = $this->output->fetch();
                foreach ($reporters as $name => $info) {
                    assert(strstr($content, "$name - " . $info['description']) !== false,  "reporter $name should be displayed");
                }
            });
        });

        it('should emit a load event', function() {
            $command = null;
            $this->emitter->on('peridot.load', function($cmd, $cfg) use (&$command) {
                $command = $cmd;
            });

            $this->command->run(new ArrayInput([], $this->definition), $this->output);

            assert($command === $this->command, "command should have been received by event");
        });

        context('when there are failing tests', function() {
            it('should return an exit code', function() {
                $suite = new Suite("fail suite", function() {});
                $test = new Test('fail', function() { throw new Exception('fail'); });
                $suite->addTest($test);
                $runner = new Runner($suite, $this->emitter);
                $command = new Command($this->factory, $this->emitter);
                $command->setRunner($runner);
                $command->setApplication($this->application);
                $exit = $command->run(new ArrayInput([], $this->definition), $this->output);

                assert($exit == 1, "exit code should be 1");
            });
        });
    });
});
