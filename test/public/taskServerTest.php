<?php
/**
 * Created by zxzTool.
 * User: zxz
 * Datetime: 2019/1/23 下午10:13
 */

$server = new swoole_server("127.0.0.1", 9503);

$server->set(array('task_worker_num' => 4));

$server->on('receive', function ($server, $fd, $reactor_id, $data) {
    $task_id = $server->task("data to on task");
    echo "Dispath AsyncTask: [id=$task_id]\n";
});

$server->on('task', function ($server, $task_id, $reactor_id, $data) {
    echo "New AsyncTask[id=$task_id]\n";
    $server->finish("$data -> OK");
});

$server->on('finish', function ($server, $task_id, $data) {
    echo "AsyncTask[$task_id] finished: {$data}\n";
});

$server->start();