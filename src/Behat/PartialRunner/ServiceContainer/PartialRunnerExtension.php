<?php

namespace Behat\PartialRunner\ServiceContainer;

use Behat\Behat\Gherkin\ServiceContainer\GherkinExtension;
use Behat\Testwork\Cli\ServiceContainer\CliExtension;
use Behat\Testwork\ServiceContainer\Extension as ExtensionInterface;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class PartialRunnerExtension implements ExtensionInterface
{
    public function load(ContainerBuilder $container, array $config): void
    {
        $definition = new Definition('Behat\PartialRunner\Controller\PartialRunnerController', [
            new Reference(GherkinExtension::MANAGER_ID),
        ]);
        $definition->addTag(CliExtension::CONTROLLER_TAG, ['priority' => 1]);
        $container->setDefinition(CliExtension::CONTROLLER_TAG . '.partial_runner', $definition);
    }

    public function configure(ArrayNodeDefinition $builder): void
    {
    }

    public function getConfigKey(): string
    {
        return 'partial_runner';
    }

    public function initialize(ExtensionManager $extensionManager): void
    {
    }

    public function process(ContainerBuilder $container): void
    {
    }
}
