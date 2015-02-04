<?php

namespace Pacsar\Console;

use Pacsar\Console\Command\ServerCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PacsarApplication extends Application implements ContainerAwareInterface
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @var bool
	 */
	private $commandsRegistered = false;

	/**
	 * @var string
	 */
	private $rootDir;

	/**
	 * {@inheritdoc}
	 */
	public function setContainer(ContainerInterface $container = null)
	{
		$this->container = $container;
	}

	/**
	 * {@inheritdoc}
	 */
	public function doRun(InputInterface $input, OutputInterface $output)
	{
		if (!$this->commandsRegistered) {
			$this->registerCommands();
		}

		return parent::doRun($input, $output);
	}

	/**
	 * @todo use compilier pass
	 *
	 * @see http://symfony.com/doc/current/components/dependency_injection/tags.html#create-a-compilerpass
	 */
	protected function registerCommands()
	{
		$this->add(new ServerCommand());
		$this->commandsRegistered = true;
	}

	/**
	 * @param mixed $rootDir
	 */
	public function setRootDir($rootDir)
	{
		$this->rootDir = $rootDir;
	}

	/**
	 * @return mixed
	 */
	public function getRootDir()
	{
		return $this->rootDir;
	}
}
