<?php
namespace Tests;

use ManaPHP\Db\Adapter\Mysql;
use ManaPHP\DbInterface;
use ManaPHP\Di\FactoryDefault;
use ManaPHP\Model\Criteria\Merger;
use PHPUnit\Framework\TestCase;
use Tests\Models\City;

class ModelCriteriaMergerTest extends TestCase
{
    /**
     * @var \ManaPHP\DiInterface
     */
    protected $di;

    public function setUp()
    {
        $this->di = new FactoryDefault();
        $this->di->alias->set('@data', __DIR__);
        $this->di->set('db', function () {
            $config = require __DIR__ . '/config.database.php';
            $db = new Mysql($config['mysql']);

            $db->attachEvent('db:beforeQuery', function (DbInterface $source) {
                // var_dump(['sql'=>$source->getSQL(),'bind'=>$source->getBind()]);
                var_dump($source->getEmulatedSQL());
            });

            return $db;
        });
    }

    public function test_limit()
    {
        $criterias = [City::class, City::class];

        $this->assertEquals([1, 2, 3], array_column((new Merger($criterias))->limit(3)->fetch(true), 'city_id'));
        $this->assertEquals([2, 3, 4], array_column((new Merger($criterias))->limit(3, 1)->fetch(true), 'city_id'));
        $this->assertEquals([1, 2, 3], array_column((new Merger($criterias))->limit(3)->fetch(true), 'city_id'));
        $this->assertEquals([600, 1, 2], array_column((new Merger($criterias))->limit(3, 599)->fetch(true), 'city_id'));
        $this->assertEquals([11, 12, 13], array_column((new Merger($criterias))->limit(3, 610)->fetch(true), 'city_id'));
    }
}