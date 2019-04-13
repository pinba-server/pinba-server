<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/Pinba/Request.php';
require_once __DIR__ . '/GPBMetadata/Pinba.php';

use Pinba\Request;
use Swoole\Server;


$config = [
    'host' => '0.0.0.0',
    'port' => 30002,
    'clickhouseUrl' => 'http://127.0.01:8123?user=default',
    'db.table' => 'pinba.requests',
    'timer' => 60,
];

$server = new Server($config['host'], $config['port'], SWOOLE_BASE, SWOOLE_SOCK_UDP);

$request = null;
$jsonRows = '';
$request = new Request();

$server->on('WorkerStart', function (Server $server) use ($request, $config, &$jsonRows)
{
    $server->tick(1000, function () use ($request, $config, &$jsonRows) {
        if ($jsonRows) {
            //echo "$jsonRows\n\n";
            $r = file_get_contents("{$config['clickhouseUrl']}&query=INSERT%20INTO%20{$config['db.table']}%20FORMAT%20JSONEachRow", null,
                stream_context_create(['http' => ['method' => 'POST', 'header' => 'Content-type: text/plain', 'content' => $jsonRows, 'ignore_errors' => true]]));
            //echo "$r\n\n";
            $jsonRows = '';
        }
    });
});

$server->on('Packet', function (Server $server, $data, $addr) use (&$request, &$jsonRows)
{
    $request->clear();
    $request->mergeFromString($data);

    //echo $data . "\n";
    //$json = $request->serializeToJsonString();
    //echo "$json\n\n";
    //$data = json_decode($json);

    $row = [
        'hostname' => $request->getHostname(),
        'server_name' => $request->getServerName(),
        'script_name' => $request->getScriptName(),
        'doc_size' => $request->getDocumentSize(),
        'mem_peak_usage' => $request->getMemoryPeak(),
        'req_time' => $request->getRequestTime(),
        'ru_utime' => $request->getRuUtime(),
        'ru_stime' => $request->getRuStime(),
        'status' => $request->getStatus(),
        'memory_footprint' => $request->getMemoryFootprint(),
        'schema' => $request->getSchema(),
        'tags.name' => [],
        'tags.value' => [],
        'timers.value' => [],
        'timers.hit_count' => [],
        'timers.tag_name' => [],
        'timers.tag_value' => [],
        'req_count' => $request->getRequestCount() ?: 1,
        'timestamp' => date("Y-m-d H:i:s"),
    ];

    $dictionary = $request->getDictionary();
    $tagNames = $request->getTagName();
    $tagValue = $request->getTagValue();

    if (!empty($tagNames)) {
        foreach ($tagNames as $tagId => $tagName) {
            $row['tags.name'][] = $dictionary[$tagName];
            $row['tags.value'][] = $dictionary[$tagValue[$tagId]];
        }
    }

    $timerHitCounts = $request->getTimerHitCount();
    $timerValue = $request->getTimerValue();
    $timerTagCount = $request->getTimerTagCount();
    $timerTagName = $request->getTimerTagName();
    $timerTagValue = $request->getTimerTagValue();

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
    echo $jsonRows .= json_encode($row) . "\n";
    //var_export($r);
});

$server->start();
