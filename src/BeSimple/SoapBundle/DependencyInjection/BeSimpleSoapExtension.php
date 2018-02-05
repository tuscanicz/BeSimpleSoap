<?php

/*
 * This file is part of the BeSimpleSoapBundle.
 *
 * (c) Christian Kerl <christian-kerl@web.de>
 * (c) Francis Besset <francis.besset@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace BeSimple\SoapBundle\DependencyInjection;

use BeSimple\SoapCommon\Cache;

use BeSimple\SoapCommon\SoapOptions\SoapOptions;
use Carpages\Core\Entity\ContactPhone;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * BeSimpleSoapExtension.
 *
 * @author Christian Kerl <christian-kerl@web.de>
 * @author Francis Besset <francis.besset@gmail.com>
 */
class BeSimpleSoapExtension extends Extension
{
    // maps config options to service suffix
    private $bindingConfigToServiceSuffixMap = array(
        'rpc-literal'      => 'rpcliteral',
        'document-wrapped' => 'documentwrapped',
    );

    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $loader->load('request.xml');
        $loader->load('soap.xml');

        $loader->load('loaders.xml');
        $loader->load('converters.xml');
        $loader->load('webservice.xml');

        $processor     = new Processor();
        $configuration = new Configuration();

        $config = $processor->process($configuration->getConfigTree(), $configs);

        $this->registerCacheConfiguration($config['cache'], $container, $loader);

        if ( ! empty($config['clients'])) {
            $this->registerClientConfiguration($config, $container, $loader);
        }

        $container->setParameter('besimple.soap.definition.dumper.options.stylesheet', $config['wsdl_dumper']['stylesheet']);

        foreach($config['services'] as $name => $serviceConfig) {
            $serviceConfig['name'] = $name;
            $this->createWebServiceContext($serviceConfig, $container);
        }

        $container->setParameter('besimple.soap.exception_listener.controller', $config['exception_controller']);
    }

    private function registerCacheConfiguration(array $config, ContainerBuilder $container, XmlFileLoader $loader)
    {
        $config['type'] = $this->getCacheType($config['type']);

        foreach (array('type', 'file') as $key) {
            $container->setParameter('besimple.soap.cache.'.$key, $config[$key]);
        }
    }

    private function registerClientConfiguration(array $config, ContainerBuilder $container, XmlFileLoader $loader)
    {
        $loader->load('client.xml');

        foreach ($config['clients'] as $client => $options) {
            $soapClientOpts = new DefinitionDecorator('besimple.soap.client_options');
            $soapOpts       = new DefinitionDecorator('besimple.soap.options');

            $soapClientOptsService = sprintf('besimple.soap.client_options.%s', $client);
            $soapOptsService       = sprintf('besimple.soap.options.%s', $client);

            // configure SoapClient
            $definition     = new DefinitionDecorator('besimple.soap.client');

            $container->setDefinition(
                sprintf('besimple.soap.client.%s', $client),
                $definition
            );
            $container->setDefinition(
                $soapClientOptsService,
                $soapClientOpts
            );
            $container->setDefinition(
                $soapOptsService,
                $soapOpts
            );

            $definition->replaceArgument(0, new Reference($soapClientOptsService));
            $definition->replaceArgument(1, new Reference($soapOptsService));

            // configure proxy
            $proxy = $options['proxy'];
            if (false !== $proxy['host']) {
                if (null !== $proxy['auth']) {
                    if ('basic' === $proxy['auth']) {
                        $proxy['auth'] = \CURLAUTH_BASIC;
                    } elseif ('ntlm' === $proxy['auth']) {
                        $proxy['auth'] = \CURLAUTH_NTLM;
                    }
                }

                $proxy = $this->createClientProxy($client, $proxy, $container);
                $soapClientOpts->setFactory([
                    '%besimple.soap.client_options_builder.class%',
                    'createWithProxy'
                ]);
                $soapClientOpts->setArgument(0, new Reference($proxy));
            }

            // configure SoapOptions for client
            $classMap = $this->createClientClassMap($client, $options['classmap'], $container);

            $soapOpts->replaceArgument(0, $config['cache']['file']);
            $soapOpts->replaceArgument(1, new Reference($classMap));
            $soapOpts->replaceArgument(2, $this->getCacheType($config['cache']['type']));

            if ($config['cache']['version'] == SoapOptions::SOAP_VERSION_1_1) {
                $soapOpts->setFactory([
                    '%besimple.soap.options_builder.class%',
                    'createWithClassMapV11'
                ]);
            }
        }
    }

    private function createClientClassMap($client, array $classmap, ContainerBuilder $container)
    {
        $definition = new DefinitionDecorator('besimple.soap.classmap');
        $container->setDefinition(sprintf('besimple.soap.classmap.%s', $client), $definition);

        if ( ! empty($classmap)) {
            $definition->setMethodCalls(array(
                array('__construct', array($classmap)),
            ));
        }

        return sprintf('besimple.soap.classmap.%s', $client);
    }

    private function createClientProxy($client, array $proxy, ContainerBuilder $container)
    {
        $definition = new DefinitionDecorator('besimple.soap.client.proxy');
        $container->setDefinition(sprintf('besimple.soap.client.proxy.%s', $client), $definition);

        if ( ! empty($proxy)) {
            $definition->replaceArgument(0, $proxy['host']);
            $definition->replaceArgument(1, $proxy['port']);
            $definition->replaceArgument(2, $proxy['login']);
            $definition->replaceArgument(3, $proxy['password']);
            $definition->replaceArgument(4, $proxy['auth']);
        }

        return sprintf('besimple.soap.client.proxy.%s', $client);
    }

    private function createWebServiceContext(array $config, ContainerBuilder $container)
    {
        $bindingSuffix = $this->bindingConfigToServiceSuffixMap[$config['binding']];
        unset($config['binding']);

        $contextId  = 'besimple.soap.context.'.$config['name'];
        $definition = new DefinitionDecorator('besimple.soap.context.'.$bindingSuffix);
        $container->setDefinition($contextId, $definition);

        if (isset($config['cache_type'])) {
            $config['cache_type'] = $this->getCacheType($config['cache_type']);
        }

        $options = $container
            ->getDefinition('besimple.soap.context.'.$bindingSuffix)
            ->getArgument(2);

        $definition->replaceArgument(2, array_merge($options, $config));
    }

    private function getCacheType($type)
    {
        switch ($type) {
            case 'none':
                return Cache::TYPE_NONE;

            case 'disk':
                return Cache::TYPE_DISK;

            case 'memory':
                return Cache::TYPE_MEMORY;

            case 'disk_memory':
                return Cache::TYPE_DISK_MEMORY;
        }
    }
}
