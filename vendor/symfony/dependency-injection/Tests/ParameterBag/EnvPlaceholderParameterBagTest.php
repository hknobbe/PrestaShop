<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace _PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Tests\ParameterBag;

use _PhpScoper5eddef0da618a\PHPUnit\Framework\TestCase;
use _PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ParameterBag\EnvPlaceholderParameterBag;
class EnvPlaceholderParameterBagTest extends \_PhpScoper5eddef0da618a\PHPUnit\Framework\TestCase
{
    public function testGetThrowsInvalidArgumentExceptionIfEnvNameContainsNonWordCharacters()
    {
        $this->expectException('_PhpScoper5eddef0da618a\\Symfony\\Component\\DependencyInjection\\Exception\\InvalidArgumentException');
        $bag = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ParameterBag\EnvPlaceholderParameterBag();
        $bag->get('env(%foo%)');
    }
    public function testMergeWillNotDuplicateIdenticalParameters()
    {
        $envVariableName = 'DB_HOST';
        $parameter = \sprintf('env(%s)', $envVariableName);
        $firstBag = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ParameterBag\EnvPlaceholderParameterBag();
        // initialize placeholders
        $firstBag->get($parameter);
        $secondBag = clone $firstBag;
        $firstBag->mergeEnvPlaceholders($secondBag);
        $mergedPlaceholders = $firstBag->getEnvPlaceholders();
        $placeholderForVariable = $mergedPlaceholders[$envVariableName];
        $placeholder = \array_values($placeholderForVariable)[0];
        $this->assertCount(1, $placeholderForVariable);
        $this->assertIsString($placeholder);
        $this->assertStringContainsString($envVariableName, $placeholder);
    }
    public function testMergeWhereFirstBagIsEmptyWillWork()
    {
        $envVariableName = 'DB_HOST';
        $parameter = \sprintf('env(%s)', $envVariableName);
        $firstBag = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ParameterBag\EnvPlaceholderParameterBag();
        $secondBag = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ParameterBag\EnvPlaceholderParameterBag();
        // initialize placeholder only in second bag
        $secondBag->get($parameter);
        $this->assertEmpty($firstBag->getEnvPlaceholders());
        $firstBag->mergeEnvPlaceholders($secondBag);
        $mergedPlaceholders = $firstBag->getEnvPlaceholders();
        $placeholderForVariable = $mergedPlaceholders[$envVariableName];
        $placeholder = \array_values($placeholderForVariable)[0];
        $this->assertCount(1, $placeholderForVariable);
        $this->assertIsString($placeholder);
        $this->assertStringContainsString($envVariableName, $placeholder);
    }
    public function testMergeWherePlaceholderOnlyExistsInSecond()
    {
        $uniqueEnvName = 'DB_HOST';
        $commonEnvName = 'DB_USER';
        $uniqueParamName = \sprintf('env(%s)', $uniqueEnvName);
        $commonParamName = \sprintf('env(%s)', $commonEnvName);
        $firstBag = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ParameterBag\EnvPlaceholderParameterBag();
        // initialize common placeholder
        $firstBag->get($commonParamName);
        $secondBag = clone $firstBag;
        // initialize unique placeholder
        $secondBag->get($uniqueParamName);
        $firstBag->mergeEnvPlaceholders($secondBag);
        $merged = $firstBag->getEnvPlaceholders();
        $this->assertCount(1, $merged[$uniqueEnvName]);
        // second bag has same placeholder for commonEnvName
        $this->assertCount(1, $merged[$commonEnvName]);
    }
    public function testMergeWithDifferentIdentifiersForPlaceholders()
    {
        $envName = 'DB_USER';
        $paramName = \sprintf('env(%s)', $envName);
        $firstBag = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ParameterBag\EnvPlaceholderParameterBag();
        $secondBag = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ParameterBag\EnvPlaceholderParameterBag();
        // initialize placeholders
        $firstPlaceholder = $firstBag->get($paramName);
        $secondPlaceholder = $secondBag->get($paramName);
        $firstBag->mergeEnvPlaceholders($secondBag);
        $merged = $firstBag->getEnvPlaceholders();
        $this->assertNotEquals($firstPlaceholder, $secondPlaceholder);
        $this->assertCount(2, $merged[$envName]);
    }
    public function testResolveEnvCastsIntToString()
    {
        $bag = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ParameterBag\EnvPlaceholderParameterBag();
        $bag->get('env(INT_VAR)');
        $bag->set('env(INT_VAR)', 2);
        $bag->resolve();
        $this->assertSame('2', $bag->all()['env(INT_VAR)']);
    }
    public function testResolveEnvAllowsNull()
    {
        $bag = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ParameterBag\EnvPlaceholderParameterBag();
        $bag->get('env(NULL_VAR)');
        $bag->set('env(NULL_VAR)', null);
        $bag->resolve();
        $this->assertNull($bag->all()['env(NULL_VAR)']);
    }
    public function testResolveThrowsOnBadDefaultValue()
    {
        $this->expectException('_PhpScoper5eddef0da618a\\Symfony\\Component\\DependencyInjection\\Exception\\RuntimeException');
        $this->expectExceptionMessage('The default value of env parameter "ARRAY_VAR" must be scalar or null, "array" given.');
        $bag = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ParameterBag\EnvPlaceholderParameterBag();
        $bag->get('env(ARRAY_VAR)');
        $bag->set('env(ARRAY_VAR)', []);
        $bag->resolve();
    }
    public function testGetEnvAllowsNull()
    {
        $bag = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ParameterBag\EnvPlaceholderParameterBag();
        $bag->set('env(NULL_VAR)', null);
        $bag->get('env(NULL_VAR)');
        $bag->resolve();
        $this->assertNull($bag->all()['env(NULL_VAR)']);
    }
    public function testGetThrowsOnBadDefaultValue()
    {
        $this->expectException('_PhpScoper5eddef0da618a\\Symfony\\Component\\DependencyInjection\\Exception\\RuntimeException');
        $this->expectExceptionMessage('The default value of an env() parameter must be scalar or null, but "array" given to "env(ARRAY_VAR)".');
        $bag = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ParameterBag\EnvPlaceholderParameterBag();
        $bag->set('env(ARRAY_VAR)', []);
        $bag->get('env(ARRAY_VAR)');
        $bag->resolve();
    }
}
