<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/Pinba/Request.php';
require_once __DIR__ . '/GPBMetadata/Pinba.php';

use Workerman\Worker;
use Workerman\Lib\Timer;
use Pinba\Request;

if (!ini_get('date.timezone')) {
    ini_set('date.timezone', date_default_timezone_get()); //fix for workerman trouble with timezone
}

if (false === ($content = file_get_contents(__DIR__ . '/config.json'))) {
    die('Fail load config');
} else {
    $config = json_decode($content, true);
}

$pinbaWorkers = [];

foreach ($config['workers'] as $i => $workerConfig) {
    $pinbaWorkers[$i] = new PinbaWorker(
        $workerConfig['host'],
        $workerConfig['port'],
        $workerConfig['clickhouseUrl'],
        $workerConfig['clickhouseTable'],
        $workerConfig['timer']
    );
}

Worker::runAll();

class PinbaWorker
{
    public string $clickhouseUrl = '';
    public string $clickhouseTable = '';
    public $timer;
    public $worker;
    public $request;
    public string $rows = '';

    public function __construct($host, $port, $clickhouseUrl, $clickhouseTable, $timer)
    {
        $this->worker = new Worker("udp://$host:$port");
        $this->worker->onWorkerStart = [$this, 'onWorkerStart'];
        $this->worker->onMessage = [$this, 'onMessage'];

        $this->clickhouseUrl = $clickhouseUrl;
        $this->clickhouseTable = $clickhouseTable;
        $this->timer = $timer;
    }

    public function onWorkerStart()
    {
        $this->request = new Request();

        Timer::add($this->timer, function() {
            if ($this->rows) {
                file_get_contents(
                    "{$this->clickhouseUrl}&query=INSERT+INTO+{$this->clickhouseTable}+FORMAT+JSONEachRow",
                    false,
                    stream_context_create([
                        'http' => [
                            'method'        => 'POST',
                            'header'        => 'Content-Type: text/plain',
                            'content'       => $this->rows,
                            'ignore_errors' => true,
                        ]
                    ])
                );

                $this->rows = '';
            }
        });
    }

    public function onMessage($connection, $data)
    {
        $this->request->clear();
        $this->request->mergeFromString($data);

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
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        $dictionary = $this->request->getDictionary();
        $tagNames = $this->request->getTagName();
        $tagValue = $this->request->getTagValue();

        if ($tagNames) {
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
                $row['timers.value'][] = $timerValue[$timerId];
                $row['timers.hit_count'][] = $timerHitCount;

                for ($i = 0; $i < $timerTagCount[$timerId]; $i++) {
                    $row['timers.tag_name'][$timerId][] = $dictionary[$timerTagName[$timerTagId]];
                    $row['timers.tag_value'][$timerId][] = $dictionary[$timerTagValue[$timerTagId]];

                    $timerTagId++;
                }
            }
        }

        try {
            $this->rows .= json_encode($row, JSON_THROW_ON_ERROR) . "\n";
        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }
    }
}
