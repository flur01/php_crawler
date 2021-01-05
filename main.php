<?php

require_once('bootstrap.php');

use Core\App;
use Core\Database\QueryBilder;

$queryBilder = new QueryBilder();
$table = App::get('config')['queue-table'];
$baseURI = "https://www.kreuzwort-raetsel.net/";
$errorTable = App::get('config')['error-table'];
$logFile = fopen('log.txt', "w");
$pidFile = fopen(App::get('config')['file-name'] . ".txt", "w");

if (App::get('config')['select-error-table']) {
    $errorData = $queryBilder->selectWhere($errorTable);
    foreach ($errorData as $row) {
        $queryBilder->insert($table, $row);
        $queryBilder->deleteWhere($errorTable, 'id', $row['id']);
    }
}

$result = $queryBilder->selectOneWhere($table, 'id');
if (! $result) {
    $startQueueData = [
        'url' => 'https://www.kreuzwort-raetsel.net/uebersicht.html',
        'class' => 'TypeCrawlers\StartPageCrawler'
    ];
    
    $crawler = new $startQueueData['class']($startQueueData, $baseURI);
    
    $crawler->getDomXPath();
    $crawler->crawl();
    $crawler->action();
}

$childs = [];
$limit = App::get('config')['forks-amount'];
pcntl_async_signals(true);
fwrite($pidFile, getmypid());
$logFile = fopen('log.txt', "w");
$pid = 0;
$startMessage = "==========\nCrawler started at " .   date('h:i:s') . "\n==========\n";
fwrite($logFile, $startMessage);
echo $startMessage;
while (true) {
    $childs = clearChildren($childs);
    if (count($childs) < $limit) {
        $queueData = queuePop();
    }

    if (! $queueData && count($childs)) {
        $closedMessage = "\n==========\nCrawler closed\n==========\n";
        fwrite($logFile, $closedMessage);
        echo $closedMessage;
        die();
    }

    pcntl_signal(SIGINT, function ()
    {
        global $childs;
        global $pid;
        if ($pid) {
            close(clearChildren($childs));
        }
    });


    if (count($childs) < $limit && $queueData) {
        $pid = pcntl_fork();
        if ($pid == -1) {
            close($childs);
        } elseif ($pid) {
            $childs[$pid] = $queueData;
            $pidStartMessage = "\nProcess $pid start in " . date('h:i:s') . "\nwith url: " . $queueData['url'] . "\n";
            fwrite($logFile, $pidStartMessage);
            echo $pidStartMessage;
        } else {
            $crawler = new $queueData['class']($queueData, $baseURI);
            $crawler->getDomXPath();
            $crawler->crawl();
            $crawler->action();
            $pidEndMessage = "\n----Process has been DIED in " . date('h:i:s') . "----\n\n";
            fwrite($logFile, $pidEndMessage);
            echo $pidEndMessage;
            die();
        }
    }    

}

function close($childs)
{
    global $logFile;
    foreach ($childs as $pid => $queueData) {
        exec("kill -9 $pid");
        $killMessage = "KILLED --$pid--\n";
        fwrite($logFile, $killMessage);
        echo $killMessage;
    }
    rerurnToQueue($childs);
    $stopMessage = "\n==========\nCrawler stopped\n==========\n";
    fwrite($logFile, $stopMessage);
    echo $stopMessage;
    die();
}

function clearChildren($childs)
{
    global $logFile;
    if ($childs) {
        foreach($childs as $pid => $link) {
            $res = pcntl_waitpid($pid, $status, WNOHANG);
             if($res == $pid) {
                $remodePidMessage = "Removed--($pid)\n";
                fwrite($logFile, $remodePidMessage);
                echo $remodePidMessage;
                unset($childs[$pid]);
            }
        }
    }
    return $childs;
}

function queuePop()
{
    $table = App::get('config')['queue-table'];
    $queryBilder = new QueryBilder();
    $result = $queryBilder->selectOneWhere($table);
    if ($result) {
        $queryBilder->deleteWhere($table, 'id', $result['id']);
    }
    unset($result['id']);
    return $result;
}


function rerurnToQueue($childs)
{
    foreach ($childs as $pid => $queueData) {
        $table = App::get('config')['queue-table'];
        $queryBilder = new QueryBilder();
        $queryBilder->insert($table, $queueData);
    }
}
