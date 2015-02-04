<?php

namespace Pacsar\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class ServerCommand extends Command
{
	public function configure()
	{
		$this->setName('server:run');
	}

	/**
	 * {@inheritdoc}
	 */
	public function execute(InputInterface $input, OutputInterface $output)
	{
		$rootDir = $this->getApplication()->getRootDir();

		// Since we're going to call this script from init, lets make sure we chdir to the directory the script resides in
		// That way I know how to find class_dicom.php relatively.
		chdir($rootDir);

		$config = @file_get_contents($pathToParametersYaml = $rootDir.'/../app/config/parameters.yml');

		if (!$config) {
			throw new \RuntimeException(sprintf("File `%s` does not exist.", $pathToParametersYaml));
		}

		$parametersConfig = Yaml::parse($config);
		$parameters = $parametersConfig['parameters'];

		define('TOOLKIT_DIR', $parameters['dcmtk_bin_dir']);

		require_once $rootDir.'/../class_dicom_php/class_dicom.php';

		// We're going to build up the command and arguments we're going to use to run DCMTK's store server.
		$storescp_cmd = TOOLKIT_DIR . "/storescp -v -dhl -td 20 -ta 20 --fork " . // Be verbose, set timeouts, fork into multiple processes
			"-xf ./storescp.cfg Default " . // Our config file
			"-od ./temp/ " . // Where to put images we receive
			"-xcr \" ./import.php \"#p\" \"#f\" \"#c\" \"#a\"\" " . // Run this script with these args after image reception
			"1104 "; // Listen on this port

		$process = new Process($storescp_cmd);

		$process->run();

		if (!$process->isSuccessful()) {
			throw new \RuntimeException($process->getErrorOutput());
		}

		echo $process->getOutput();
	}

	/**
	 * @return \Pacsar\Console\PacsarApplication
	 */
	public function getApplication()
	{
		return parent::getApplication();
	}
}
