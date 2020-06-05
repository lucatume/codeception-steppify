<?php

namespace tad\Codeception\Command;


use Codeception\Command\Shared\Config;
use Codeception\Command\Shared\FileSystem;
use Codeception\Configuration;
use Codeception\CustomCommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use tad\Codeception\Command\Generator\GherkinSteps;

class Steppify extends Command implements CustomCommandInterface {

	use Config;
	use FileSystem;

	/**
	 * returns the name of the command
	 *
	 * @return string
	 */
	public static function getCommandName() {
		return 'gherkin:steppify';
	}

	protected function configure() {
		$this
			->setDescription('Create step definitions from modules')
			->addArgument('module', InputArgument::REQUIRED,
				'The class name of the module from which the Gherkin steps should be generated;')
			->addOption('postfix', null, InputOption::VALUE_REQUIRED,
				'A postfix that should be appended to the the trait file name', '')
			->addOption('steps-config', null, InputOption::VALUE_REQUIRED,
				'The configuration file that should be used to generate the Gherkin steps', '')
			->addOption('namespace', null, InputOption::VALUE_REQUIRED,
				'The namespace of the generated Gherkin steps', '');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$module = $input->getArgument('module');

		if (!class_exists($module)) {
			$module = '\\Codeception\\Module\\' . $module;
			if (!class_exists($module)) {
				$output->writeln("<error>Module '$module' does not exist.</error>");
				return -1;
			}
		}

		$postfix = $input->getOption('postfix');
		$settings                 = [];
		$settings['name']         = $this->getClassNameFromModule($module);
		$settings['namespace']    = $input->getOption('namespace');
		$settings['postfix']      = $postfix;
		$settings['steps-config'] = $this->getStepsGenerationConfig($input);
		if (empty($settings['namespace']) && isset($settings['steps-config']['namespace'])) {
            $settings['namespace'] = $settings['steps-config']['namespace'];
        }

		// Deduplicate '\\' in namespace.
		if ( strpos( $settings['namespace'], '\\' ) !== false ) {
			$settings['namespace'] = str_replace( '\\\\', '\\', $settings['namespace'] );
		}

		$generator = new GherkinSteps($module, $settings);

		$output->writeln("<info>Generating Gherkin steps from module '{$module}...'</info>");

		if (!empty($settings['steps-config'])) {
			$output->writeln("<info>Reading configuration for module from '{$input->getOption('steps-config')}'</info>");
		}

		$content = $generator->produce();

		$file = $this->buildPath(Configuration::supportDir() . '_generated', $settings['name'], $settings['postfix'] );

		return $this->createFile($file, $content, true);
	}

	/**
	 * @param $module
	 *
	 * @return string
	 */
	protected function getClassNameFromModule($module) {
		$frags = explode('\\', $module);
		return end($frags);
	}

	/**
	 * @param InputInterface $input
	 *
	 * @return array|mixed
	 */
	protected function getStepsGenerationConfig(InputInterface $input) {
		$stepsConfigFile = empty($input->getOption('steps-config')) ?
			Configuration::testsDir() . 'steppify.yml' :
			$input->getOption('steps-config');

		$stepsConfig = file_exists($stepsConfigFile) ?
			Yaml::parse(file_get_contents($stepsConfigFile))
			: [];

		return $stepsConfig;
	}

	protected function buildPath($path, $class, $postfix = null) {
		$className = $this->getShortClassName($class);
		$path      = $this->createDirectoryFor($path, $class);

		$filename = $this->completeSuffix($className, 'GherkinSteps' . $postfix);

		return $path . $filename;
	}
}
