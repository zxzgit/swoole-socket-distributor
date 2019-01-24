<?php

echo PHP_EOL . "This is an immature example, please see pity." . PHP_EOL;

//load autoload file
$autoloadFile = '../../vendor/autoload.php';
$projectComposerAutoloadFile = '../../../../../vendor/autoload.php';

if(!is_file($autoloadFile) && !is_file($projectComposerAutoloadFile)){
    exit('Please set the autoload file path correctly。' . PHP_EOL);
}

include is_file($autoloadFile) ? $autoloadFile : $projectComposerAutoloadFile;

use zxzgit\ssd\libs\ConnectHandler;


$directive = isset($argv[1]) ? $argv[1] : ConnectHandler::DIRECTIVE_START;


//run app
\zxzgit\ssd\WebSocketApp::run($directive,[
    'debugOn' => true,
    'isDoFork' => true,
    'moduleList' => [
        'test' => \zxzgit\ssd\test\modules\test\MessageModule::class,
    ],
    'messageDistributor' => \zxzgit\ssd\test\MessageDistributor::class,

    'event' => [
        'connect' =>function($server, $fd){
            echo "Client:Connect." . $fd . PHP_EOL;
            //$this->triggerEvent('connect', func_get_args());
        },
        'receive' => function($server, $fd, $reactor_id, $data){
            echo "server: receive." .$data . '-fd:'  . $fd  . PHP_EOL;
        },
        'close' => function($server, $fd){
            echo "Client: Close."  . $fd  . PHP_EOL;
        }
    ],
    'serverSetConfig'=>[
        'daemonize' => 1
    ],

    /*
    'serverSetConfig' => [//https://wiki.swoole.com/wiki/page/13.html
        'worker_num' => 4,    //worker process num
        'reactor_num' => 2, //reactor thread num
        'worker_num' => 4,    //worker process num
        'backlog' => 128,   //listen backlog
        'max_request' => 50,
        'dispatch_mode' => 1,
        'max_conn' => 1000,
    ],
    */
]);