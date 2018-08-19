<?php
namespace ManaPHP\Swoole\Http;

use ManaPHP\Component;

class Server extends Component implements ServerInterface
{
    /**
     * @var string
     */
    protected $_host = '0.0.0.0';

    /**
     * @var int
     */
    protected $_port = 9501;

    /**
     * @var array
     */
    protected $_settings = [];

    /**
     * @var array
     */
    protected $_server = [];

    /**
     * @var \swoole_http_server
     */
    protected $_swoole;

    /**
     * @var \swoole_http_request
     */
    protected $_request;

    /**
     * @var \swoole_http_response
     */
    protected $_response;

    /**
     * @var callable
     */
    protected $_handler;

    /**
     * Http constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['host'])) {
            $this->_host = $options['host'];
            unset($options['host']);
        }
        if (isset($options['port'])) {
            $this->_port = (int)$options['port'];
            unset($options['port']);
        }

        $this->_settings = $options;

        $script_filename = get_included_files()[0];
        $parts = explode('-', phpversion());
        $this->_server = [
            'DOCUMENT_ROOT' => dirname($script_filename),
            'SCRIPT_FILENAME' => $script_filename,
            'PHP_SELF' => $server['SCRIPT_NAME'] = '/' . basename($script_filename),
            'QUERY_STRING' => '',
            'REQUEST_SCHEME' => 'http',
            'SERVER_SOFTWARE' => 'Swoole/' . SWOOLE_VERSION . ' ' . php_uname('s') . '/' . $parts[1] . ' PHP/' . $parts[0]
        ];
    }

    /**
     * @param \swoole_http_request $request
     */
    public function _prepareGlobals($request)
    {
        $_SERVER = array_change_key_case($request->server, CASE_UPPER);
        unset($_SERVER['SERVER_SOFTWARE']);
        $_SERVER += $this->_server;

        foreach ($request->header ?: [] as $k => $v) {
            $_SERVER['HTTP_' . strtoupper(strtr($k, '-', '_'))] = $v;
        }

        $_SERVER['WORKER_ID'] = $this->_swoole->worker_pid;

        $_GET = $request->get ?: [];
        $_POST = $request->post ?: [];

        /** @noinspection AdditionOperationOnArraysInspection */
        $_REQUEST = $_POST + $_GET;

        $_COOKIE = $request->cookie ?: [];
        $_FILES = $request->files ?: [];
    }

    /**
     * @param callable $handler
     *
     * @return static
     */
    public function start($handler)
    {
        $this->_swoole = new \swoole_http_server($this->_host, $this->_port);
        $this->_swoole->set($this->_settings);
        $this->_handler = $handler;
        $this->_swoole->on('request', [$this, 'onRequest']);
        $this->_swoole->start();

        return $this;
    }

    /**
     * @param \swoole_http_request $request
     * @param \swoole_http_response $response
     */
    public function onRequest($request, $response)
    {
        if ($request->server['request_uri'] === '/favicon.ico') {
            $response->status(404);
            $response->end();
            return;
        }
        $this->_request = $request;
        $this->_response = $response;
        $this->_prepareGlobals($request);
        $handler = $this->_handler;
        $handler();
    }

    /**
     * @param array $headers
     *
     * @return static
     */
    public function sendHeaders($headers)
    {
        $response = $this->_response;

        if (isset($headers['Status'])) {
            $parts = explode(' ', $headers['Status']);
            $response->status($parts[0]);
            unset($headers['Status']);
        }
        foreach ($headers as $k => $v) {
            $response->header($k, $v);
        }

        $response->header('X-Response-Time', round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3));
        $response->header('X-WORKER-ID', $_SERVER['WORKER_ID']);

        return $this;
    }

    /**
     * @param array $cookies
     *
     * @return static
     */
    public function sendCookies($cookies)
    {
        $response = $this->_response;

        $this->fireEvent('cookies:beforeSend');
        foreach ($cookies as $cookie) {
            $response->cookie($cookie['name'], $cookie['value'], $cookie['expire'],
                $cookie['path'], $cookie['domain'], $cookie['secure'],
                $cookie['httpOnly']);
        }
        $this->fireEvent('cookies:afterSend');

        return $this;
    }

    /**
     * @param string $content
     *
     * @return static
     */
    public function sendContent($content)
    {
        $this->_response->end($content);
        return $this;
    }
}