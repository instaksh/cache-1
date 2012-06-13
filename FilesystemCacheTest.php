<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\FilesystemCache;

/**
 * @group DCOM-101
 */
class FilesystemCacheTest extends CacheTest
{
    /**
     * @var \Doctrine\Common\Cache\FilesystemCache
     */
    private $driver;

    protected function _getCacheDriver()
    {
        $dir = sys_get_temp_dir() . "/doctrine_cache_". uniqid();
        $this->assertFalse(is_dir($dir));

        
        $this->driver = new FilesystemCache($dir);
        $this->assertTrue(is_dir($dir));

        return $this->driver;
    }

    public function testLifetime()
    {
        $cache = $this->_getCacheDriver();

        // Test save
        $cache->save('test_key', 'testing this out', 10);

        // Test contains to test that save() worked
        $this->assertTrue($cache->contains('test_key'));

        // Test fetch
        $this->assertEquals('testing this out', $cache->fetch('test_key'));

        // access private methods
        $getFilename        = new \ReflectionMethod($cache, 'getFilename');
        $getNamespacedId    = new \ReflectionMethod($cache, 'getNamespacedId');

        $getFilename->setAccessible(true);
        $getNamespacedId->setAccessible(true);

        $id     = $getNamespacedId->invoke($cache, 'test_key');
        $path   = $getFilename->invoke($cache, $id);
        $value  = include $path;

        // update lifetime
        $value['lifetime'] = $value['lifetime'] - 20;
        file_put_contents($path, '<?php return unserialize(' . var_export(serialize($value), true) . ');');

        // test expired data
        $this->assertFalse($cache->contains('test_key'));
        $this->assertFalse($cache->fetch('test_key'));
    }

    public function testGetStats()
    {
        $cache = $this->_getCacheDriver();
        $stats = $cache->getStats();
        $this->assertNull($stats);
    }

    public function tearDown()
    {
        $dir    = $this->driver->getDirectory();
        $ext    = $this->driver->getExtension();
        $list   = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));

        foreach($list as $file){
            if($file->isDir()) {
                rmdir($file->getRealPath());
                continue;
            }
            unlink($file->getRealPath());
        }
    }

}