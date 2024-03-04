<?php

declare(strict_types=1);

namespace SyliusLabs\SuiteTagsExtension\ServiceContainer;

use Behat\Testwork\Cli\ServiceContainer\CliExtension;
use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Behat\Testwork\Suite\ServiceContainer\SuiteExtension;
use SyliusLabs\SuiteTagsExtension\Suite\Cli\SuiteController;
use SyliusLabs\SuiteTagsExtension\Suite\Cli\FilteredTagsSuiteController;
use SyliusLabs\SuiteTagsExtension\Suite\MutableSuiteRegistry;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class SuiteTagsExtension implements Extension
{
    public function process(ContainerBuilder $container): void
    {
    }

    public function getConfigKey(): string
    {
        return 'sylius_labs_suite_tags';
    }

    public function initialize(ExtensionManager $extensionManager): void
    {
    }

    public function configure(ArrayNodeDefinition $builder): void
    {
    }

    public function load(ContainerBuilder $container, array $config): void
    {
        $this->overwriteSuiteRegistry($container);
        $this->overwriteSuiteController($container);

        $controllerDefinition = new Definition(FilteredTagsSuiteController::class, [
            new Reference(SuiteExtension::REGISTRY_ID),
        ]);
        $controllerDefinition->addTag(CliExtension::CONTROLLER_TAG, array('priority' => 1000));
        $container->setDefinition(CliExtension::CONTROLLER_TAG . '.filtered_tags_suite', $controllerDefinition);
    }

    private function overwriteSuiteRegistry(ContainerBuilder $container): void
    {
        $definition = new Definition(MutableSuiteRegistry::class);
        $container->setDefinition(SuiteExtension::REGISTRY_ID, $definition);
    }

    private function overwriteSuiteController(ContainerBuilder $container): void
    {
        $container
            ->getDefinition(CliExtension::CONTROLLER_TAG . '.suite')
            ->setClass(SuiteController::class)
        ;
    }
}
