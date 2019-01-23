<?php
/**
 * Created by zxzTool.
 * User: zxz
 * Datetime: 2019/1/23 下午10:21
 */
go(function () {
    $client = new Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);
    
    if (!$client->connect('127.0.0.1', 9503, 0.5)) {
        exit("connect failed. Error: {$client->errCode}\n");
    }
    
    while (true){
        sleep(2);
        $client->send(json_encode([
            'route' => 'index/index',
            'data'  => []
        ]));
    }
    
    //echo $client->recv();
    //$client->close();
});