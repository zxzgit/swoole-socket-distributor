<?php
/**
 * Created by zxzTool.
 * User: zxz
 * Datetime: 2019/1/23 13:55
 */

$client = new swoole_client(SWOOLE_TCP | SWOOLE_ASYNC); //异步非阻塞

$client->on("connect", function($cli) {
    echo "connect success";
    go(function () use ($cli) {
        $redis = new Swoole\Coroutine\Redis();
        $redis->connect('127.0.0.1', 6379);
        $val = $redis->get('key');
        echo PHP_EOL . "--ldjfls--" . PHP_EOL;
    });
    
    echo PHP_EOL ."hello world". PHP_EOL;
    
    $cli->send(json_encode([
        'route' => 'index/index',
        'data'  => []
    ]));
});

$client->on("receive", function($cli, $data) {
    echo "received: $data\n";
    sleep(1);
    $cli->send(json_encode([
        'route' => 'index/index',
        'data'  => []
    ]));
});

$client->on("close", function($cli){
    echo "closed\n";
});

$client->on("error", function($cli){
    exit("error\n");
});

$client->connect('127.0.0.1', 9502, 0.5);