<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/Pinba/Request.php';
require_once __DIR__ . '/GPBMetadata/Pinba.php';

use Workerman\Worker;
use Workerman\Lib\Timer;
use Pinba\Request;

if (!ini_get('date.timezone')) {
    ini_set('date.timezone', date_default_timezone_get()); //fix for workerman trouble with timezone
}

$config = json_decode(file_get_contents('config.json'), true);

$pinbaWorkers = [];

foreach ($config['workers'] as $i => $workerConfig) {
    $pinbaWorkers[$i] = new PinbaWorker($workerConfig['host'], $workerConfig['port'], $workerConfig['clickhouseUrl'], $workerConfig['clickhouseTable'], $workerConfig['timer']);
}

Worker::runAll();

class PinbaWorker {
    public $clickhouseUrl;
    public $clickhouseTable;
    public $timer;
    public $worker;
    public $request;
    public $rows = '';

    public function __construct($host, $port, $clickhouseUrl, $clickhouseTable, $timer)
    {
        $this->worker = new Worker("udp://$host:$port");
        $this->worker->onWorkerStart = [$this, 'onWorkerStart'];
        $this->worker->onMessage = [$this, 'onMessage'];

        $this->clickhouseUrl = $clickhouseUrl;
        $this->clickhouseTable = $clickhouseTable;
        $this->timer = $timer;
    }

    public function onWorkerStart() {
        $this->request = new Request();

        Timer::add($this->timer, function() {
            if ($this->rows) {
                //echo "$this->rows\n\n";
                $r = file_get_contents("{$this->clickhouseUrl}&query=INSERT+INTO+{$this->clickhouseTable}+FORMAT+JSONEachRow", null,
                    stream_context_create(['http' => ['method' => 'POST', 'header' => 'Content-Type: text/plain', 'content' => $this->rows, 'ignore_errors' => true]]));
                //echo "$r\n\n";
                $this->rows = '';
            }
        });
    }

    public function onMessage($connection, $data)
    {
        $this->request->clear();
        $this->request->mergeFromString($data);

        //echo $data . "\n";
        //$json = $this->request->serializeToJsonString();
        //echo "{$this->clickhouseTable}: $json\n\n";
        //$data = json_decode($json);

        $row = [
            'hostname' => $this->request->getHostname(),
            'server_name' => $this->request->getServerName(),
            'script_name' => $this->request->getScriptName(),
            'doc_size' => $this->request->getDocumentSize(),
            'mem_peak_usage' => $this->request->getMemoryPeak(),
            'req_time' => $this->request->getRequestTime(),
            'ru_utime' => $this->request->getRuUtime(),
            'ru_stime' => $this->request->getRuStime(),
            'status' => $this->request->getStatus(),
            'memory_footprint' => $this->request->getMemoryFootprint(),
            'schema' => $this->request->getSchema(),
            'tags.name' => [],
            'tags.value' => [],
            'timers.value' => [],
            'timers.hit_count' => [],
            'timers.tag_name' => [],
            'timers.tag_value' => [],
            'req_count' => $this->request->getRequestCount() ?: 1,
            'timestamp' => date("Y-m-d H:i:s"),
        ];

        $dictionary = $this->request->getDictionary();
        $tagNames = $this->request->getTagName();
        $tagValue = $this->request->getTagValue();

        if (!empty($tagNames)) {
            foreach ($tagNames as $tagId => $tagName) {
                $row['tags.name'][] = $dictionary[$tagName];
                $row['tags.value'][] = $dictionary[$tagValue[$tagId]];
            }
        }

        $timerHitCounts = $this->request->getTimerHitCount();
        $timerValue = $this->request->getTimerValue();
        $timerTagCount = $this->request->getTimerTagCount();
        $timerTagName = $this->request->getTimerTagName();
        $timerTagValue = $this->request->getTimerTagValue();

        if ($timerHitCounts->count()) {
            $timerTagId = 0;
            foreach ($timerHitCounts as $timerId => $timerHitCount) {
                $row['timers.value'][]= $timerValue[$timerId];
                $row['timers.hit_count'][]= $timerHitCount;

                for ($i = 0; $i < $timerTagCount[$timerId]; $i++) {
                    $row['timers.tag_name'][$timerId][]=$dictionary[$timerTagName[$timerTagId]];
                    $row['timers.tag_value'][$timerId][]=$dictionary[$timerTagValue[$timerTagId]];

                    $timerTagId++;
                }
            }
        }

        //var_export($row);
        $this->rows .= json_encode($row) . "\n";
    }
}
