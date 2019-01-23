<?php
/**
 * Created by zxzTool.
 * User: zxz
 * Datetime: 2018/11/15 18:03
 *
    \zxzgit\ssd\WebSocketApp::run([
        'moduleList' => [
            'test' => \zxzgit\ssd\test\modules\test\MessageModule::class,
         ],
        'messageDistributor' => \zxzgit\ssd\test\MessageDistributor::class,
        'event' => [
                'initConnector' => function () {},
                'start'         => function (&$server) {},
                'workerStart'   => function (&$server, $id) {},
                'open'          => function (&$server, &$req) {},
                'beforeMessage' => function (&$server, &$frame) {},
                'afterMessage'  => function (&$server, &$frame) {},
                'close'         => function (&$server, $fd) {},
                'request'       => function (&$request, &$response) {},
        ]
    ]);
 */

namespace zxzgit\ssd\libs;


class ConnectHandler
{
    /**
     * @var string process title
     */
    public $processTitle = 'php-zxzgit-ssd-server';
    
    /**
     * @var string 主进程process title后缀
     */
    public $processTitleMainProcessSuffix = '-- master for swoole';
    
    /**
     * @var string swoole server 主进程process title后缀
     */
    public $processTitleSwooleMasterSuffix = '-- swoole worker master';
    
    /**
     * @var string swoole server 子worker进程process title后缀
     */
    public $processTitleSwooleWorkSuffix = '-- swoole worker';

    /**
     * 路由解析函数映射
     * $parseRouteMap = [
     *     'json' => function($data){
     *         //$data 为客户端传过来的数据
     *         //do something parse
     *         return 'module/controller/action';//必须返回该格式的字符串路由数据，e.g controller/action or module/submodule/controller/action
     *      }
     *     'xml' => function($data){
     *         //do something parse
     *         return 'module/controller/action';
     *     }
     * ]
     * @var array
     */
    public $parseRouteMap = [];

    /**
     * 解析格式，默认为json,对应 $parseRouteMap 中的路由解析函数映射
     * @var string
     */
    public $parseRouteDataFormat = 'json';

    /**
     * 该参数在 $parseRouteDataFormat = 'json'时有效，json中路由信息属性名
     * @var string
     */
    public $parseRouteDataFormatRouteProperty = 'route';

    /**
     * @var bool 是否开启生成子线程处理，开启后控制器代码修改可直接生效
     */
    public $isDoFork = false;

    /**
     * 是否开启调试
     * @var bool
     */
    public $debugOn  = false;

    /**
     * debug 输出方式,默认为$debugMethodForConsoleOutput 属性
     * @var string
     */
    public $debugMethod;
    
    /**
     * 控制台输出debug方法
     * @var string
     */
    private $debugMethodForConsoleOutput  = 'print_r';

    /**
     * debug输出方法
     * $debugMethodHandler = [
     *    'print_r' => function($msg){
     *        //output msg
     *        print_r($msg);
     *    },
     *    'file' => function($msg){
     *       //do something log options
     *    }
     * ]
     * @var array
     */
    public $debugMethodHandler  = [];

    /**
     * @var array 模块设置
     * $moduleList = [
     *    'test'   => \zxzgit\ssd\test\modules\test\MessageModule::class,
     *    '模块名称' => '模块类名',
     * ]
     */
    public $moduleList = [];

    /**
     * @var null|MessageModule 当前路由模块对象
     */
    public $module;

    /**
     * @var string 默认控制器
     */
    public $defaultController = 'index';

    /**
     * @var MessageDistributor 内容分发器
     * 'messageDistributor' => \zxzgit\ssd\test\MessageDistributor::class,
     */
    public $messageDistributor;

    /**
     * @var array 钩子
     * $event = [
     *    'initConnector' => function(&$connector){},
     *     \/** server 事件 **\/
     *    'start'         => function(&$server){},
     *    'workerStart'   => function(&$server, $id){},
     *    'open'          => function(&$server, &$req){},
     *    'beforeMessage' => function(&$server, &$frame){},
     *    'afterMessage'  => function(&$server, &$frame){},
     *    'close'         => function(&$server, $fd){},
     *    'request'       => function(&$request, &$response){},
     * ]
     */
    public $event = [];
    
    /**
     * 服务器接收信息事件名称
     * @var string
     */
    private $receiveEventName = 'receive';
    
    /**
     * 服务器开始事件名称
     * @var string
     */
    private $startEventName   = 'start';
    
    /**
     * @var string swoole worder start event name
     */
    private $workerStartName  = 'workerStart';

    /**
     * websocke 服务bind
     * @var string
     */
    public $serverBind = '0.0.0.0';

    /**
     * websocke 服务监听端口
     * @var int
     */
    public $serverPort = 9502;
    
    /**
     * 运行的模式 SWOOLE_PROCESS多进程模式（默认）,SWOOLE_BASE基本模式
     * @var int
     */
    public $mode = SWOOLE_PROCESS;
    
    /**
     * Socket的类型，支持TCP、UDP、TCP6、UDP6、UnixSocket Stream/Dgram 6种
     * @var int
     */
    public $sockType = SWOOLE_SOCK_TCP;

    /**
     * @var array swoole server::set() 配置 https://wiki.swoole.com/wiki/page/13.html
     */
    public $serverSetConfig = [];

    /**
     * @var \swoole_websocket_server $server
     */
    public $server;

    /**
     * ConnectCollection constructor.
     * @param array $config
     */
    function __construct(array $config = [])
    {
        $this->init($config);
    }

    /**
     * @param array $config
     */
    protected function init(array $config = [])
    {
        foreach ($config as $index => $item) {
            property_exists($this, $index)
            &&
            (new \ReflectionProperty(__CLASS__, $index))->isPublic()
            &&
            ($this->$index = $item);
        }

        $this->checkProperty();

        $this->setErrorHandler();

        $this->addDefaultRouteParseFn();

        $this->debugAddDefaultHandler();

        $this->processTitleSet($this->processTitle . $this->processTitleMainProcessSuffix);
    }

    /**
     * 促发事件
     * @param string $event
     * @param array $params
     */
    public function triggerEvent(string $event, array $params = [])
    {
        isset($this->event[$event])
        &&
        is_callable($this->event[$event])
        &&
        call_user_func_array($this->event[$event], $params);
    }

    /**
     * 添加事件
     * @param string $event
     * @param callable $fn
     */
    public function setEvent(string $event, callable $fn)
    {
        $this->event[$event] = $fn;
    }

    /**
     * 执行服务
     */
    public function run()
    {
        $this->runConnectHandlerOutputInfo();

        $this->initServer();

        $this->triggerEvent('initConnector', [&$this]);

        $this->server->start();
    }

    /**
     * 初始化websocket服务
     */
    protected function initServer()
    {
        $this->server = new \swoole_server($this->serverBind, $this->serverPort, $this->mode, $this->sockType);

        //设置server运行时的各项参数
        $this->server->set($this->serverSetConfig);
    
        $this->initServerEvent();
    
        return $this;
    }
    
    /**
     * 初始化服务器回调事件
     */
    private function initServerEvent() {
        !isset($this->event[$this->startEventName]) && $this->event[$this->startEventName] = function () { };
        !isset($this->event[$this->workerStartName]) && $this->event[$this->workerStartName] = function () { };
        //事件设置
        foreach ($this->event as $eventName => $eventFn) {
            $this->server->on($eventName, function () use ($eventName) {
                $argsInfo = func_get_args();
                $this->triggerEvent($eventName, $argsInfo);
                
                //特殊事件处理
                switch ($eventName) {
                    case $this->receiveEventName:
                        //如果为接收信息则进行信息分发处理
                        $frame            = new \stdClass();
                        $frame->fd        = $argsInfo[1];
                        $frame->reactorId = $argsInfo[2];
                        $frame->data      = $argsInfo[3];
                        
                        //对当前信息进行分发
                        MessageHandler::msgDeal($this, $frame, $this->isDoFork);
                        break;
                    case $this->startEventName:
                        $this->processTitleSet($this->processTitle . $this->processTitleSwooleMasterSuffix);
                        //启动信息提示
                        $this->debugConsoleOutput('server start success.');
                        $this->debugConsoleOutput('server setting info:');
                        $this->debugConsoleOutput(json_encode($this->server->setting, JSON_PRETTY_PRINT));
                        break;
                    case $this->workerStartName:
                        $this->processTitleSet($this->processTitle . $this->processTitleSwooleWorkSuffix);
                        break;
                    default:
                        break;
                }
            });
        }
    }

    /**
     * 检测必要参数设置
     */
    protected function checkProperty()
    {
        if ($this->messageDistributor == null) {
            throw new \Exception('请正确设置' . __CLASS__ . '::$messageDistributor 必须设置');
        }
    }

    /**
     * 异常处理
     */
    protected function setErrorHandler()
    {
        ErrorException::initErrorHandler();
    }

    /**
     * 添加默认路由解析函数
     */
    public function addDefaultRouteParseFn()
    {
        $this->parseRouteMap['json'] = function ($data) {
            $data = json_decode($data, true);
            return isset($data[$this->parseRouteDataFormatRouteProperty]) && trim($data[$this->parseRouteDataFormatRouteProperty]) ? (string)$data[$this->parseRouteDataFormatRouteProperty] : '';
        };
    }

    /**
     * 添加默认调试处理
     */
    public function debugAddDefaultHandler(){
        $this->debugMethod = $this->debugMethod ?: $this->debugMethodForConsoleOutput;
        $this->debugMethodHandler[$this->debugMethodForConsoleOutput] = function ($msg) {
            print_r(PHP_EOL);
            print_r($msg);
            print_r(PHP_EOL);
        };
    }

    /**
     * 输出debug信息
     * @param string $msg
     */
    public function debugOutput($msg  = '-- debug empty msg --'){
        is_callable($this->debugMethodHandler[$this->debugMethod])
        &&
        call_user_func($this->debugMethodHandler[$this->debugMethod], $msg);
    }

    /**
     * 控制台提示信息输出
     * @param $msg
     */
    public function debugConsoleOutput($msg)
    {
        call_user_func($this->debugMethodHandler[$this->debugMethodForConsoleOutput], $msg);
    }

    /**
     * set process title
     * @param $title
     */
    public function processTitleSet($title){
        cli_set_process_title($title);
    }

    /**
     * connect启动信息输出提示
     */
    protected function runConnectHandlerOutputInfo()
    {
        $this->debugConsoleOutput('process-pid   : ' . posix_getpid());
        $this->debugConsoleOutput('process-title prefix : ' . $this->processTitle);
    }
}