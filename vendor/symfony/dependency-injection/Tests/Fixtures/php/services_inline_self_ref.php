<?php

namespace _PhpScoper5ea00cc67502b;

use _PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use _PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\ContainerInterface;
use _PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Container;
use _PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use _PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Exception\LogicException;
use _PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Exception\RuntimeException;
use _PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\ParameterBag\FrozenParameterBag;
/**
 * This class has been auto-generated
 * by the Symfony Dependency Injection Component.
 *
 * @final since Symfony 3.3
 */
class Symfony_DI_PhpDumper_Test_Inline_Self_Ref extends \_PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Container
{
    private $parameters = [];
    private $targetDirs = [];
    public function __construct()
    {
        $this->services = [];
        $this->normalizedIds = ['_PhpScoper5ea00cc67502b\\app\\foo' => '_PhpScoper5ea00cc67502b\\App\\Foo'];
        $this->methodMap = ['_PhpScoper5ea00cc67502b\\App\\Foo' => 'getFooService'];
        $this->aliases = [];
    }
    public function getRemovedIds()
    {
        return ['_PhpScoper5ea00cc67502b\\Psr\\Container\\ContainerInterface' => \true, '_PhpScoper5ea00cc67502b\\Symfony\\Component\\DependencyInjection\\ContainerInterface' => \true];
    }
    public function compile()
    {
        throw new \_PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Exception\LogicException('You cannot compile a dumped container that was already compiled.');
    }
    public function isCompiled()
    {
        return \true;
    }
    public function isFrozen()
    {
        @\trigger_error(\sprintf('The %s() method is deprecated since Symfony 3.3 and will be removed in 4.0. Use the isCompiled() method instead.', __METHOD__), \E_USER_DEPRECATED);
        return \true;
    }
    /**
     * Gets the public 'App\Foo' shared service.
     *
     * @return \App\Foo
     */
    protected function getFooService()
    {
        $a = new \_PhpScoper5ea00cc67502b\App\Bar();
        $b = new \_PhpScoper5ea00cc67502b\App\Baz($a);
        $b->bar = $a;
        $this->services['App\\Foo'] = $instance = new \_PhpScoper5ea00cc67502b\App\Foo($b);
        $a->foo = $instance;
        return $instance;
    }
}
/**
 * This class has been auto-generated
 * by the Symfony Dependency Injection Component.
 *
 * @final since Symfony 3.3
 */
\class_alias('_PhpScoper5ea00cc67502b\\Symfony_DI_PhpDumper_Test_Inline_Self_Ref', 'Symfony_DI_PhpDumper_Test_Inline_Self_Ref', \false);
