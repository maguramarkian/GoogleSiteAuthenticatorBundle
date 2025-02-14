<?php

namespace Happyr\GoogleSiteAuthenticatorBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('happyr_google_site_authenticator');
        // Keep compatibility with symfony/config < 4.2
        if (!method_exists($treeBuilder, 'getRootNode')) {
            $root = $treeBuilder->root('happyr_google_site_authenticator');
        } else {
            $root = $treeBuilder->getRootNode();
        }

        $root->children()
            ->scalarNode('cache_service')->cannotBeEmpty()->end()
            ->append($this->getTokenNode())
        ->end();

        return $treeBuilder;
    }

    /**
     * @return \Symfony\Component\Config\Definition\Builder\NodeDefinition
     */
    private function getTokenNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('tokens');
        $node
            ->isRequired()
            ->requiresAtLeastOneElement()
            ->useAttributeAsKey('name')
            ->prototype('array')
            ->children()
                ->scalarNode('client_id')->isRequired()->end()
                ->scalarNode('client_secret')->isRequired()->end()
                ->scalarNode('redirect_url')->isRequired()->end()
                ->variableNode('scopes')->end()
            ->end()
        ->end();

        return $node;
    }
}
