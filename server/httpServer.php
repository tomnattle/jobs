<?php
namespace server;
//废弃
class httpServer extends \swoole_http_server
{

    public $processes = null;

    public $baseRoot = '';

    public function onOpen($server, $req)
    {}

    public function onRequest($request, $response)
    {
        if ($request->server['request_uri'] == "/favicon.ico") {
            $response->end('');
            return;
        }
        
        $file = ROOT . "/" . $this->baseRoot . $request->server['path_info'];
        
        if (is_file($file)) {
            $response->end(file_get_contents($file));
        } else {
            $file = ROOT . "/" . $this->baseRoot . '/index.php';
            ob_start();
            require $file;
            $result = ob_get_clean();
            $response->end($result);
        }
    }

    public function onClose($server, $fd)
    {}

    public function init()
    {}

    public function __construct($config)
    {
        parent::__construct($config['ip'], $config['port']);
        $this->baseRoot = $config['base-root'];
        
        $this->on("request", [
            $this,
            'onRequest'
        ]);
        $this->on("close", [
            $this,
            'onClose'
        ]);
    }
}

?>