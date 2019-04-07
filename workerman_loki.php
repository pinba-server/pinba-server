<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/Pinba/Request.php';
require_once __DIR__ . '/GPBMetadata/Pinba.php';

use Workerman\Worker;
use Workerman\Lib\Timer;
use Pinba\Request;

if (!ini_get('date.timezone')) {
    ini_set('date.timezone', date_default_timezone_get());//exec('date +"%Z"');
}

$config = [
    'host:port' => '0.0.0.0:30002',
    'lokiUrl' => 'http://127.0.01:3100/api/prom/push',
    'db.table' => 'pinba.requests',
    'timer' => 60,
];

// pecl install event
//Worker::$eventLoopClass = '\Workerman\Events\Event';

$tcp_worker = new Worker("udp://{$config['host:port']}");

$request = null;
$streams = '';

$tcp_worker->onWorkerStart = function () use (&$request, &$streams, &$config){
    $request = new Request();
    Timer::add($config['timer'],
        function() use (&$streams, &$config)
        {
            if ($streams) {
                $post = json_encode(['streams' => $streams]);
                //{"streams": [{"labels": "{foo=\"bar\"}","entries": [{ "ts": "2018-12-18T08:28:06.801064-04:00", "line": "baz" }]}]}
                //echo $post . "\n\n";
                $r = file_get_contents($config['lokiUrl'], null,
                    stream_context_create(['http' => ['method' => 'POST', 'header' => 'Content-Type: application/json', 'content' => $post, 'ignore_errors' => true]]));
                //echo "$r\n\n";
                $streams = [];
            }
        }
    );
};

$tcp_worker->onMessage = function($connection, $data) use (&$request, &$streams)
{
    $request->clear();
    $request->mergeFromString($data);

    //echo $data . "\n";
    //$json = $request->serializeToJsonString();
    //echo "$json\n\n";
    //$data = json_decode($json);

    $row = [
        'labels' => [
            'hostname' => $request->getHostname(),
            'server_name' => $request->getServerName(),
            'script_name' => $request->getScriptName(),
            'status' => $request->getStatus(),
            'schema' => $request->getSchema(),
        ],
        'entries' => [
            'doc_size' => $request->getDocumentSize(),
            'mem_peak_usage' => $request->getMemoryPeak(),
            'req_time' => $request->getRequestTime(),
            'ru_utime' => $request->getRuUtime(),
            'ru_stime' => $request->getRuStime(),
            'memory_footprint' => $request->getMemoryFootprint(),
            'req_count' => $request->getRequestCount() ?: 1,
        ],
    ];

    $dictionary = $request->getDictionary();
    $tagName = $request->getTagName();
    $tagValue = $request->getTagValue();

    if (!empty($tagName)) {
        foreach ($tagName as $tagId => $tagName) {
            $row['labels'][$dictionary[$tagName]] = $dictionary[$tagValue[$tagId]];
        }
    }

    $timerHitCounts = $request->getTimerHitCount();
    $timerValue = $request->getTimerValue();
    $timerTagCount = $request->getTimerTagCount();
    $timerTagName = $request->getTimerTagName();
    $timerTagValue = $request->getTimerTagValue();

    if ($timerHitCounts->count()) {
        $row['entries']['timers'] = [
            'value' => [],
            'hit_count' => [],
            'tag_name' => [],
            'tag_value' => [],
        ];

        $timerTagId = 0;
        foreach ($timerHitCounts as $timerId => $timerHitCount) {
            $row['entries']['timers']['value'][]= $timerValue[$timerId];
            $row['entries']['timers']['hit_count'][]= $timerHitCount;

            for ($i = 0; $i < $timerTagCount[$timerId]; $i++) {
                $row['entries']['timers']['tag_name'][$timerId][]=$dictionary[$timerTagName[$timerTagId]];
                $row['entries']['timers']['tag_value'][$timerId][]=$dictionary[$timerTagValue[$timerTagId]];

                $timerTagId++;
            }
        }
        $row['entries']['timers'] = json_encode($row['entries']['timers']);
    }

    //var_export($row);
    $stream = [
        'labels' => [],
        'entries' => [],
    ];


    foreach ($row['labels'] as $name => $value) {
        $stream['labels'][]= "$name=\"$value\"";
    }
    $stream['labels'] = '{' . join(',', $stream['labels']) . '}';
    $stream['entries'][] = ['ts' => date("Y-m-d\TH:i:s\Z"), 'line' => json_encode($row['entries'])];
    $streams[]= $stream;
};


Worker::runAll();