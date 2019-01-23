<?php
/**
 * Created by zxzTool.
 * User: zxz
 * Datetime: 2019/1/23 13:55
 */

//$client = new Swoole\Coroutine\Client("192.168.56.10", 5291);
go(function () {
    $client = new Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);
    if (!$client->connect('127.0.0.1', 9502, 0.5)) {
        exit("connect failed. Error: {$client->errCode}\n");
    }
    $client->send(json_encode([
        'route' => 'index/index',
        'data'  => []
    ]));
    echo $client->recv();
    //$client->close();
});

//$client->connect("192.168.56.10", 5291, 0.5);