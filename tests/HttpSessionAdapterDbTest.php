<?php
namespace Tests;

use ManaPHP\Db\Adapter\Mysql;
use ManaPHP\DbInterface;
use ManaPHP\Di;
use ManaPHP\Mvc\Factory;
use PHPUnit\Framework\TestCase;

class HttpSessionAdapterDbTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $di = new Factory();
        $di->setShared('db', function () {
            $config = require __DIR__ . '/config.database.php';
            $db = new Mysql($config['mysql']);
            //   $db = new ManaPHP\Db\Adapter\Sqlite($config['sqlite']);

            $db->attachEvent('db:beforeQuery', function (DbInterface $source) {
                var_dump($source->getSQL());
                var_dump($source->getEmulatedSQL());
            });

            echo get_class($db), PHP_EOL;
            return $db;
        });
    }

    public function test_read()
    {
        $session_id = md5(microtime(true) . mt_rand());
        $adapter = Di::getDefault()->getInstance('ManaPHP\Http\Session\Adapter\Db');

        $this->assertEquals('', $adapter->do_read($session_id));

        $adapter->do_write($session_id, 'manaphp', 100);
        $this->assertEquals('manaphp', $adapter->do_read($session_id));
    }

    public function test_write()
    {
        $session_id = md5(microtime(true) . mt_rand());
        $adapter = Di::getDefault()->getInstance('ManaPHP\Http\Session\Adapter\Db');

        $adapter->do_write($session_id, '', 100);
        $this->assertEquals('', $adapter->do_read($session_id));

        $adapter->do_write($session_id, 'manaphp', 100);
        $this->assertEquals('manaphp', $adapter->do_read($session_id));
    }

    public function test_destory()
    {
        $session_id = md5(microtime(true) . mt_rand());
        $adapter = Di::getDefault()->getInstance('ManaPHP\Http\Session\Adapter\Db');
        $this->assertTrue($adapter->do_destroy($session_id));

        $adapter->do_write($session_id, 'manaphp', 100);
        $this->assertEquals('manaphp', $adapter->do_read($session_id));
        $this->assertTrue($adapter->do_destroy($session_id));

        $this->assertEquals('', $adapter->do_read($session_id));
    }

    public function test_gc()
    {
        md5(microtime(true) . mt_rand());
        $adapter = Di::getDefault()->getInstance('ManaPHP\Http\Session\Adapter\Db');
        $this->assertTrue($adapter->do_gc(100));
    }
}