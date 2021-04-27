<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Psr\CacheItemPool;

use Cache\IntegrationTests\CachePoolTest;
use Laminas\Cache\Psr\CacheItemPool\CacheItemPoolDecorator;
use Laminas\Cache\Psr\SimpleCache\SimpleCacheDecorator;
use Laminas\Cache\Storage\Adapter\Memcache;
use Laminas\Cache\Storage\Plugin\Serializer;
use Laminas\Cache\StorageFactory;

use function date_default_timezone_get;
use function date_default_timezone_set;
use function get_class;
use function getenv;
use function sprintf;

/**
 * @require extension memcache
 */
class MemcacheIntegrationTest extends CachePoolTest
{
    /**
     * Backup default timezone
     *
     * @var string
     */
    private $tz;

    /** @var Memcache */
    private $storage;

    protected function setUp(): void
    {
        // set non-UTC timezone
        $this->tz = date_default_timezone_get();
        date_default_timezone_set('America/Vancouver');

        parent::setUp();
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->tz);

        if ($this->storage) {
            $this->storage->flush();
        }

        parent::tearDown();
    }

    public function createCachePool(): SimpleCacheDecorator
    {
        $host = getenv('TESTS_LAMINAS_CACHE_MEMCACHE_HOST');
        $port = getenv('TESTS_LAMINAS_CACHE_MEMCACHE_PORT');

        $options = [
            'resource_id' => self::class,
        ];
        if ($host && $port) {
            $options['servers'] = [[$host, $port]];
        } elseif ($host) {
            $options['servers'] = [[$host]];
        }

        $storage = StorageFactory::adapterFactory('memcache', $options);
        $storage->addPlugin(new Serializer());

        $deferredSkippedMessage                                                 = sprintf(
            '%s storage doesn\'t support driver deferred',
            get_class($storage)
        );
        $this->skippedTests['testHasItemReturnsFalseWhenDeferredItemIsExpired'] = $deferredSkippedMessage;

        return new CacheItemPoolDecorator($storage);
    }
}
