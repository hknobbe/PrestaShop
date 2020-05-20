<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace _PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Loader\Configurator;

use _PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Alias;
use _PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\ChildDefinition;
use _PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\ContainerBuilder;
use _PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Definition;
use _PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use _PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
/**
 * @author Nicolas Grekas <p@tchwork.com>
 *
 * @method InstanceofConfigurator instanceof($fqcn)
 */
class ServicesConfigurator extends \_PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Loader\Configurator\AbstractConfigurator
{
    const FACTORY = 'services';
    private $defaults;
    private $container;
    private $loader;
    private $instanceof;
    public function __construct(\_PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\ContainerBuilder $container, \_PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Loader\PhpFileLoader $loader, array &$instanceof)
    {
        $this->defaults = new \_PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Definition();
        $this->container = $container;
        $this->loader = $loader;
        $this->instanceof =& $instanceof;
        $instanceof = [];
    }
    /**
     * Defines a set of defaults for following service definitions.
     *
     * @return DefaultsConfigurator
     */
    public final function defaults()
    {
        return new \_PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Loader\Configurator\DefaultsConfigurator($this, $this->defaults = new \_PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Definition());
    }
    /**
     * Defines an instanceof-conditional to be applied to following service definitions.
     *
     * @param string $fqcn
     *
     * @return InstanceofConfigurator
     */
    protected final function setInstanceof($fqcn)
    {
        $this->instanceof[$fqcn] = $definition = new \_PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\ChildDefinition('');
        return new \_PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Loader\Configurator\InstanceofConfigurator($this, $definition, $fqcn);
    }
    /**
     * Registers a service.
     *
     * @param string      $id
     * @param string|null $class
     *
     * @return ServiceConfigurator
     */
    public final function set($id, $class = null)
    {
        $defaults = $this->defaults;
        $allowParent = !$defaults->getChanges() && empty($this->instanceof);
        $definition = new \_PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Definition();
        $definition->setPublic($defaults->isPublic());
        $definition->setAutowired($defaults->isAutowired());
        $definition->setAutoconfigured($defaults->isAutoconfigured());
        $definition->setBindings($defaults->getBindings());
        $definition->setChanges([]);
        $configurator = new \_PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator($this->container, $this->instanceof, $allowParent, $this, $definition, $id, $defaults->getTags());
        return null !== $class ? $configurator->class($class) : $configurator;
    }
    /**
     * Creates an alias.
     *
     * @param string $id
     * @param string $referencedId
     *
     * @return AliasConfigurator
     */
    public final function alias($id, $referencedId)
    {
        $ref = static::processValue($referencedId, \true);
        $alias = new \_PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Alias((string) $ref, $this->defaults->isPublic());
        $this->container->setAlias($id, $alias);
        return new \_PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Loader\Configurator\AliasConfigurator($this, $alias);
    }
    /**
     * Registers a PSR-4 namespace using a glob pattern.
     *
     * @param string $namespace
     * @param string $resource
     *
     * @return PrototypeConfigurator
     */
    public final function load($namespace, $resource)
    {
        $allowParent = !$this->defaults->getChanges() && empty($this->instanceof);
        return new \_PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Loader\Configurator\PrototypeConfigurator($this, $this->loader, $this->defaults, $namespace, $resource, $allowParent);
    }
    /**
     * Gets an already defined service definition.
     *
     * @param string $id
     *
     * @return ServiceConfigurator
     *
     * @throws ServiceNotFoundException if the service definition does not exist
     */
    public final function get($id)
    {
        $allowParent = !$this->defaults->getChanges() && empty($this->instanceof);
        $definition = $this->container->getDefinition($id);
        return new \_PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator($this->container, $definition->getInstanceofConditionals(), $allowParent, $this, $definition, $id, []);
    }
    /**
     * Registers a service.
     *
     * @param string      $id
     * @param string|null $class
     *
     * @return ServiceConfigurator
     */
    public final function __invoke($id, $class = null)
    {
        return $this->set($id, $class);
    }
}
