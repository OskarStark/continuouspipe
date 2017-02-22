<?php

namespace AppBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class AppExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $this->applyConfiguration($container, $config);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
        $loader->load('controllers.xml');
        $loader->load('admin.xml');
        $loader->load('intercom.xml');
        $loader->load('billing.xml');
        $loader->load('alerts.xml');
    }

    private function applyConfiguration(ContainerBuilder $container, array $config)
    {
        $codes = isset($config['authenticator']['early_access']['codes'])
            ? $config['authenticator']['early_access']['codes']
            : [];
        $container->setParameter('app.authenticator.early_access.codes', $codes);
    }
}
