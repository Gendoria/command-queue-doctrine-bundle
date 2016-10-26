<?php

namespace Gendoria\CommandQueueDoctrineDriverBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Master configuration key.
     *
     * @var string
     */
    private $alias;

    /**
     * Class constructor.
     *
     * @param string $alias
     */
    public function __construct($alias)
    {
        $this->alias = $alias;
    }

    /**
     * Get config tree builder instance.
     *
     * {@inheritdoc}
     *
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root($this->alias);
        $rootNode->children()
            ->arrayNode('drivers')
                ->prototype('array')
                    ->children()
                        ->scalarNode('connection')
                            ->cannotBeEmpty()
                            ->isRequired()
                            ->defaultValue('default')
                        ->end()
                        ->scalarNode('serializer')
                            ->cannotBeEmpty()
                            ->isRequired()
                            ->validate()
                                ->ifTrue(function($v) {
                                    return strpos($v, '@') !== 0;
                                })
                                ->thenInvalid('Serializer has to be in form "@service.id"')
                            ->end()
                        ->end()
                        ->scalarNode('table_name')
                            ->defaultValue('cmq')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
