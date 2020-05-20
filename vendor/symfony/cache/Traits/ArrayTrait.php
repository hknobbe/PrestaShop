<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace _PhpScoper5ea00cc67502b\Symfony\Component\Cache\Traits;

use _PhpScoper5ea00cc67502b\Psr\Log\LoggerAwareTrait;
use _PhpScoper5ea00cc67502b\Symfony\Component\Cache\CacheItem;
/**
 * @author Nicolas Grekas <p@tchwork.com>
 *
 * @internal
 */
trait ArrayTrait
{
    use LoggerAwareTrait;
    private $storeSerialized;
    private $values = [];
    private $expiries = [];
    /**
     * Returns all cached values, with cache miss as null.
     *
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }
    /**
     * {@inheritdoc}
     */
    public function hasItem($key)
    {
        \_PhpScoper5ea00cc67502b\Symfony\Component\Cache\CacheItem::validateKey($key);
        return isset($this->expiries[$key]) && ($this->expiries[$key] > \time() || !$this->deleteItem($key));
    }
    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->values = $this->expiries = [];
        return \true;
    }
    /**
     * {@inheritdoc}
     */
    public function deleteItem($key)
    {
        \_PhpScoper5ea00cc67502b\Symfony\Component\Cache\CacheItem::validateKey($key);
        unset($this->values[$key], $this->expiries[$key]);
        return \true;
    }
    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        $this->clear();
    }
    private function generateItems(array $keys, $now, $f)
    {
        foreach ($keys as $i => $key) {
            try {
                if (!($isHit = isset($this->expiries[$key]) && ($this->expiries[$key] > $now || !$this->deleteItem($key)))) {
                    $this->values[$key] = $value = null;
                } elseif (!$this->storeSerialized) {
                    $value = $this->values[$key];
                } elseif ('b:0;' === ($value = $this->values[$key])) {
                    $value = \false;
                } elseif (\false === ($value = \unserialize($value))) {
                    $this->values[$key] = $value = null;
                    $isHit = \false;
                }
            } catch (\Exception $e) {
                \_PhpScoper5ea00cc67502b\Symfony\Component\Cache\CacheItem::log($this->logger, 'Failed to unserialize key "{key}"', ['key' => $key, 'exception' => $e]);
                $this->values[$key] = $value = null;
                $isHit = \false;
            }
            unset($keys[$i]);
            (yield $key => $f($key, $value, $isHit));
        }
        foreach ($keys as $key) {
            (yield $key => $f($key, null, \false));
        }
    }
}
