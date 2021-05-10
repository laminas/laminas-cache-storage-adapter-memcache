<?php

namespace LaminasTest\Cache\Storage\Adapter;

use Laminas\Cache;

use function getenv;

/**
 * @group      Laminas_Cache
 * @covers Laminas\Cache\Storage\Adapter\Memcache<extended>
 */
class MemcacheTest extends AbstractCommonAdapterTest
{
    public function setUp(): void
    {
        $this->options = new Cache\Storage\Adapter\MemcacheOptions([
            'resource_id' => self::class,
        ]);

        if (getenv('TESTS_LAMINAS_CACHE_MEMCACHE_HOST') && getenv('TESTS_LAMINAS_CACHE_MEMCACHE_PORT')) {
            $this->options->getResourceManager()->addServers(self::class, [
                [getenv('TESTS_LAMINAS_CACHE_MEMCACHE_HOST'), getenv('TESTS_LAMINAS_CACHE_MEMCACHE_PORT')],
            ]);
        } elseif (getenv('TESTS_LAMINAS_CACHE_MEMCACHE_HOST')) {
            $this->options->getResourceManager()->addServers(self::class, [
                [getenv('TESTS_LAMINAS_CACHE_MEMCACHE_HOST')],
            ]);
        }

        $this->storage = new Cache\Storage\Adapter\Memcache();
        $this->storage->setOptions($this->options);
        $this->storage->flush();

        parent::setUp();
    }

    public function getCommonAdapterNamesProvider(): array
    {
        return [
            ['memcache'],
            ['Memcache'],
        ];
    }

    /**
     * Data provider to test valid server info
     *
     * Returns an array of the following structure:
     * array(array(
     *     <array|string server options>,
     *     <array expected normalized servers>,
     * )[, ...])
     *
     * @return array
     */
    public function getServersDefinitions()
    {
        $expectedServers = [
            ['host' => '127.0.0.1', 'port' => 12345, 'weight' => 1, 'status' => true],
            ['host' => 'localhost', 'port' => 54321, 'weight' => 2, 'status' => true],
            ['host' => 'examp.com', 'port' => 11211, 'status' => true],
        ];

        return [
            // servers as array list
            [
                [
                    ['127.0.0.1', 12345, 1],
                    ['localhost', '54321', '2'],
                    ['examp.com'],
                ],
                $expectedServers,
            ],

            // servers as array assoc
            [
                [
                    ['127.0.0.1', 12345, 1],
                    ['localhost', '54321', '2'],
                    ['examp.com'],
                ],
                $expectedServers,
            ],

            // servers as string list
            [
                [
                    '127.0.0.1:12345?weight=1',
                    'localhost:54321?weight=2',
                    'examp.com',
                ],
                $expectedServers,
            ],

            // servers as string
            [
                '127.0.0.1:12345?weight=1, localhost:54321?weight=2,tcp://examp.com',
                $expectedServers,
            ],
        ];
    }

    /**
     * @dataProvider getServersDefinitions
     * @param mixed $servers
     * @param array $expectedServers
     */
    public function testOptionSetServers($servers, $expectedServers)
    {
        $options = new Cache\Storage\Adapter\MemcacheOptions();
        $options->setServers($servers);
        $this->assertEquals($expectedServers, $options->getServers());
    }

    public function testCompressThresholdOptions()
    {
        $threshold  = 100;
        $minSavings = 0.2;

        $options = new Cache\Storage\Adapter\MemcacheOptions();
        $options->setAutoCompressThreshold($threshold);
        $options->setAutoCompressMinSavings($minSavings);
        $this->assertEquals(
            $threshold,
            $options->getResourceManager()->getAutoCompressThreshold($options->getResourceId())
        );
        $this->assertEquals(
            $minSavings,
            $options->getResourceManager()->getAutoCompressMinSavings($options->getResourceId())
        );

        $memcache = new Cache\Storage\Adapter\Memcache($options);
        $this->assertEquals($memcache->getOptions()->getAutoCompressThreshold(), $threshold);
        $this->assertEquals($memcache->getOptions()->getAutoCompressMinSavings(), $minSavings);
    }

    public function tearDown(): void
    {
        if ($this->storage) {
            $this->storage->flush();
        }

        parent::tearDown();
    }
}
