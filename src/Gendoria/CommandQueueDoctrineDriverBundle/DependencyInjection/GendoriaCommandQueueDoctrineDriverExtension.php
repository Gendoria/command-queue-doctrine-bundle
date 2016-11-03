<?php

namespace Gendoria\CommandQueueDoctrineDriverBundle\DependencyInjection;

use Gendoria\CommandQueueBundle\DependencyInjection\Pass\WorkerRunnersPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class GendoriaCommandQueueDoctrineDriverExtension extends Extension
{
    /**
     * Get extension alias.
     *
     * @return string
     */
    public function getAlias()
    {
        return 'gendoria_command_queue_rabbit_mq_driver';
    }

    /**
     * Load extension.
     *
     * @param array            $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration($this->getAlias());
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $this->loadDrivers($config, $container);
        $container->removeDefinition('gendoria_command_queue_doctrine_driver.send_driver');
        $container->removeDefinition('gendoria_command_queue_doctrine_driver.worker');
        $container->removeDefinition('gendoria_command_queue_doctrine_driver.worker_runner');
    }
    
    private function loadDrivers(array $config, ContainerBuilder $container)
    {
        foreach ($config['drivers'] as $driverId => $driver) {
            $serializer = substr($driver['serializer'], 1);
            $connectionService = sprintf('doctrine.dbal.%s_connection', $driver['connection']);
            
            $this->prepareSendDriver($container, $driverId, $driver, $connectionService, $serializer);
            $this->prepareNewWorker($container, $driverId, $driver, $serializer);
            $this->prepareNewWorkerRunner($container, $driverId, $driver, $connectionService);
        }
        $container->setParameter('gendoria_command_queue_doctrine_driver.drivers', $config['drivers']);
    }
    
    private function prepareSendDriver(ContainerBuilder $container, $driverId, $driver, $connectionService, $serializer)
    {
        $newDriver = clone $container->getDefinition('gendoria_command_queue_doctrine_driver.send_driver');
        $newDriver->replaceArgument(0, new Reference($serializer));
        $newDriver->replaceArgument(1, new Reference($connectionService));
        $newDriver->replaceArgument(2, $driver['table_name']);
        $newDriver->replaceArgument(3, $driverId);
        $container->setDefinition('gendoria_command_queue_doctrine_driver.driver.'.$driverId, $newDriver);
    }
    
    private function prepareNewWorker(ContainerBuilder $container, $driverId, $driver, $serializer)
    {
        $newWorker = clone $container->getDefinition('gendoria_command_queue_doctrine_driver.worker');
        $newWorker->replaceArgument(2, new Reference($serializer));
        $newWorker->replaceArgument(3, $driver['table_name']);
        $newWorker->replaceArgument(4, $driverId);
        $container->setDefinition('gendoria_command_queue_doctrine_driver.worker.'.$driverId, $newWorker);
    }
    
    private function prepareNewWorkerRunner(ContainerBuilder $container, $driverId, $driver, $connectionService)
    {
        $newRunner = clone $container->getDefinition('gendoria_command_queue_doctrine_driver.worker_runner');
        $newRunner->replaceArgument(0, new Reference('gendoria_command_queue_doctrine_driver.worker.'.$driverId));
        $newRunner->replaceArgument(1, new Reference($connectionService));
        $newRunner->replaceArgument(2, $driver['table_name']);
        $newRunner->replaceArgument(3, $driverId);
        $newRunner->addTag(WorkerRunnersPass::WORKER_RUNNER_TAG, array('name' => 'doctrine.'.$driverId, 'options' => json_encode($driver)));
        $container->setDefinition('gendoria_command_queue_doctrine_driver.worker_runner.'.$driverId, $newRunner);
    }    
}
