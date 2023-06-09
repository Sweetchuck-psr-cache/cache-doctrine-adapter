<?php

declare(strict_types = 1);

/**
 * @file
 * This file is part of php-cache organization.
 *
 * (c) 2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\Adapter\Doctrine;

use Cache\Adapter\Common\AbstractCachePool;
use Cache\Adapter\Common\PhpCacheItem;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\FlushableCache;

/**
 * This is a bridge between PSR-6 and aDoctrine cache.
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class DoctrineCachePool extends AbstractCachePool
{
    protected Cache $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    protected function fetchObjectFromCache(string $key): array
    {
        if (false === $data = $this->cache->fetch($key)) {
            return [false, null, [], null];
        }

        return unserialize($data);
    }

    /**
     * {@inheritdoc}
     */
    protected function clearAllObjectsFromCache(): bool
    {
        if ($this->cache instanceof FlushableCache) {
            return $this->cache->flushAll();
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function clearOneObjectFromCache(string $key): bool
    {
        return $this->cache->delete($key);
    }

    /**
     * {@inheritdoc}
     */
    protected function storeItemInCache(PhpCacheItem $item, ?int $ttl): bool
    {
        if ($ttl === null) {
            $ttl = 0;
        }

        $data = serialize([true, $item->get(), $item->getTags(), $item->getExpirationTimestamp()]);

        return $this->cache->save($item->getKey(), $data, $ttl);
    }

    /**
     * @return Cache
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * {@inheritdoc}
     */
    protected function getList(string $name): array
    {
        if (false === $list = $this->cache->fetch($name)) {
            return [];
        }

        return $list;
    }

    /**
     * {@inheritdoc}
     */
    protected function removeList(string $name): bool
    {
        return $this->cache->delete($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function appendListItem(string $name, string $key)
    {
        $list   = $this->getList($name);
        $list[] = $key;
        $this->cache->save($name, $list);
    }

    /**
     * {@inheritdoc}
     */
    protected function removeListItem(string $name, string $key)
    {
        $list = $this->getList($name);
        foreach ($list as $i => $item) {
            if ($item === $key) {
                unset($list[$i]);
            }
        }
        $this->cache->save($name, $list);
    }
}
