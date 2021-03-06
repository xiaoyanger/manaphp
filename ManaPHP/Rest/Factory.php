<?php
namespace ManaPHP\Rest;

use ManaPHP\Di\FactoryDefault;

class Factory extends FactoryDefault
{
    public function __construct()
    {
        parent::__construct();

        $this->_definitions = array_merge($this->_definitions, [
            'router' => 'ManaPHP\Router',
            'dispatcher' => 'ManaPHP\Dispatcher',
            'errorHandler' => 'ManaPHP\Rest\ErrorHandler',
            'url' => 'ManaPHP\Url',
            'response' => 'ManaPHP\Http\Response',
            'request' => 'ManaPHP\Http\Request',
            'session' => 'ManaPHP\Http\Session\Adapter\File',
            'cookies' => 'ManaPHP\Http\Cookies',
            'captcha' => 'ManaPHP\Security\Captcha',
            'authorization' => 'ManaPHP\Authorization',
            'swooleHttpServer' => 'ManaPHP\Swoole\Http\Server'
        ]);
    }
}