<?php
chdir(dirname(__FILE__) . '/../');
include_once("./config.php");
include_once("./lib/loader.php");
include_once("./lib/threads.php");
set_time_limit(0);
// connecting to database
$db = new mysql(DB_HOST, '', DB_USER, DB_PASSWORD, DB_NAME);
include_once("./load_settings.php");
include_once(DIR_MODULES . "control_modules/control_modules.class.php");
$ctl = new control_modules();

include_once(ROOT . "3rdparty/phpmqtt/phpMQTT.php");
include_once(DIR_MODULES . 'openhasp/openhasp.class.php');
$openhasp_module = new openhasp();
$openhasp_module->getConfig();

echo date("H:i:s") . " running " . basename(__FILE__) . PHP_EOL;

$client_name = "MajorDoMo OpenHasp";
$client_name = $client_name . ' (#' . uniqid() . ')';

if ($openhasp_module->config['MQTT_AUTH']) {
    $username = $openhasp_module->config['MQTT_USERNAME'];
    $password = $openhasp_module->config['MQTT_PASSWORD'];
}

$host = 'localhost';

if ($openhasp_module->config['MQTT_HOST']) {
    $host = $openhasp_module->config['MQTT_HOST'];
}

if ($openhasp_module->config['MQTT_PORT']) {
    $port = $openhasp_module->config['MQTT_PORT'];
} else {
    $port = 1883;
}

if ($openhasp_module->config['MQTT_QUERY']) {
    $query = $openhasp_module->config['MQTT_QUERY'];
} else {
    $query = '/var/now/#';
}

$mqtt_client = new Bluerhinos\phpMQTT($host, $port, $client_name);

if ($openhasp_module->config['MQTT_AUTH']) {
    $connect = $mqtt_client->connect(true, NULL, $username, $password);
    if (!$connect) {
        exit(1);
    }
} else {
    $connect = $mqtt_client->connect();
    if (!$connect) {
        exit(1);
    }
}


$query_list = explode(',', $query);
$total = count($query_list);
echo date('H:i:s') . " Topics to watch: $query (Total: $total)\n";
for ($i = 0; $i < $total; $i++) {
    $path = trim($query_list[$i]);
    echo date('H:i:s') . " Path: $path\n";
    $topics[$path] = array("qos" => 0, "function" => "procmsg");
}
foreach ($topics as $k => $v) {
    echo date('H:i:s') . " Subscribing to: $k  \n";
    $rec = array($k => $v);
    $mqtt_client->subscribe($rec, 0);
}

$openhasp_module->reloadPanels();

$previousMillis = 0;

$oldMinute = '';

while ($mqtt_client->proc()) {

    $currentMillis = round(microtime(true) * 10000);

    if ($currentMillis - $previousMillis > 10000) {
        $previousMillis = $currentMillis;
        setGlobal((str_replace('.php', '', basename(__FILE__))) . 'Run', time(), 1);
        if (file_exists('./reboot') || isset($_GET['onetime'])) {
            $mqtt_client->close();
            $db->Disconnect();
            exit;
        }
    }
}

$mqtt_client->close();

function procmsg($topic, $msg)
{

    $from_hub = 0;
    $did = $topic;

    global $openhasp_module;

    if (!isset($topic) || !isset($msg)) return false;
    if (preg_match('/command/', $topic)) return;
    echo date("Y-m-d H:i:s") . " Received from {$topic} ($did, $from_hub): $msg\n";
    if (function_exists('callAPI')) {
        callAPI('/api/module/openhasp', 'POST', array('topic' => $topic, 'msg' => $msg));
    } else {
        $openhasp_module->processMessage($topic, $msg);
    }
}

DebMes("Unexpected close of cycle: " . basename(__FILE__));
