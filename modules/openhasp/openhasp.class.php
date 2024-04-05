<?php
/**
* OpenHasp 
* @package project
* @author Wizard <sergejey@gmail.com>
* @copyright http://majordomo.smartliving.ru/ (c)
* @version 0.1 (wizard, 14:02:15 [Feb 12, 2024])
*/
//
//
class openhasp extends module {
    /**
* openhasp
*
* Module class constructor
*
* @access private
*/
    function __construct() {
        $this->name="openhasp";
        $this->title="OpenHasp";
        $this->module_category="<#LANG_SECTION_DEVICES#>";
        $this->checkInstalled();
    }
    /**
* saveParams
*
* Saving module parameters
*
* @access public
*/
    function saveParams($data=1) {
        $p=array();
        if (IsSet($this->id)) {
            $p["id"]=$this->id;
        }
        if (IsSet($this->view_mode)) {
            $p["view_mode"]=$this->view_mode;
        }
        if (IsSet($this->edit_mode)) {
            $p["edit_mode"]=$this->edit_mode;
        }
        if (IsSet($this->tab)) {
            $p["tab"]=$this->tab;
        }
        return parent::saveParams($p);
    }
    /**
* getParams
*
* Getting module parameters from query string
*
* @access public
*/
    function getParams() {
        global $id;
        global $mode;
        global $view_mode;
        global $edit_mode;
        global $tab;
        if (isset($id)) {
            $this->id=$id;
        }
        if (isset($mode)) {
            $this->mode=$mode;
        }
        if (isset($view_mode)) {
            $this->view_mode=$view_mode;
        }
        if (isset($edit_mode)) {
            $this->edit_mode=$edit_mode;
        }
        if (isset($tab)) {
            $this->tab=$tab;
        }
    }
    /**
* Run
*
* Description
*
* @access public
*/
    function run() {
        global $session;
        $out=array();
        if ($this->action=='admin') {
            $this->admin($out);
        } else {
            $this->usual($out);
        }
        if (IsSet($this->owner->action)) {
            $out['PARENT_ACTION']=$this->owner->action;
        }
        if (IsSet($this->owner->name)) {
            $out['PARENT_NAME']=$this->owner->name;
        }
        $out['VIEW_MODE']=$this->view_mode;
        $out['EDIT_MODE']=$this->edit_mode;
        $out['MODE']=$this->mode;
        $out['ACTION']=$this->action;
        $out['TAB']=$this->tab;
        $this->data=$out;
        $p=new parser(DIR_TEMPLATES.$this->name."/".$this->name.".html", $this->data, $this);
        $this->result=$p->result;
    }
    /**
* BackEnd
*
* Module backend
*
* @access public
*/
    function admin(&$out) {
        $this->getConfig();

        $out['MQTT_HOST'] = $this->config['MQTT_HOST'];
        $out['MQTT_PORT'] = $this->config['MQTT_PORT'];
        $out['MQTT_QUERY'] = $this->config['MQTT_QUERY'];
        if (!$out['MQTT_HOST']) {
            $out['MQTT_HOST'] = 'localhost';
        }
        if (!$out['MQTT_PORT']) {
            $out['MQTT_PORT'] = '1883';
        }
        $out['MQTT_USERNAME'] = $this->config['MQTT_USERNAME'];
        $out['MQTT_PASSWORD'] = $this->config['MQTT_PASSWORD'];
        $out['MQTT_AUTH'] = $this->config['MQTT_AUTH'];
        $out['MQTT_WRITE_METHOD'] = isset($this->config['MQTT_WRITE_METHOD']) ? (int)$this->config['MQTT_WRITE_METHOD'] : 0;
        $out['MQTT_QOS'] = isset($this->config['MQTT_QOS']) ? (int)$this->config['MQTT_QOS'] : 0;
        $out['DEBUG'] = $this->config['DEBUG'];
        
        if ($this->view_mode == 'update_settings') {
            $this->config['MQTT_HOST'] = gr('mqtt_host', 'trim');
            $this->config['MQTT_USERNAME'] = gr('mqtt_username', 'trim');
            $this->config['MQTT_PASSWORD'] = gr('mqtt_password', 'trim');
            $this->config['MQTT_AUTH'] = gr('mqtt_auth', 'int');
            $this->config['MQTT_PORT'] = gr('mqtt_port', 'int');
            $this->config['MQTT_QUERY'] = gr('mqtt_query', 'trim');
            $this->config['MQTT_WRITE_METHOD'] = gr('mqtt_write_method', 'int');
            $this->config['MQTT_QOS'] = gr('mqtt_qos', 'int');
            $this->config['DEBUG'] = gr('debug', 'int');
            $this->saveConfig();
            setGlobal('cycle_openhaspControl', 'restart');
            $this->redirect("?");
        }
        if ($this->view_mode == 'reloadpage_hasp_panels') {
            $panel=SQLSelectOne("SELECT * FROM hasp_panels WHERE ID='$this->id'");
            $this->reloadPages($panel);
            $this->redirect("?");
        }        
        if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
            $out['SET_DATASOURCE']=1;
        }
        if ($this->data_source=='hasp_panels' || $this->data_source=='') {
            if ($this->view_mode=='' || $this->view_mode=='search_hasp_panels') {
                $this->search_hasp_panels($out);
            }
            if ($this->view_mode=='edit_hasp_panels') {
                $this->edit_hasp_panels($out, $this->id);
            }
            if ($this->view_mode=='delete_hasp_panels') {
                $this->delete_hasp_panels($this->id);
                $this->redirect("?");
            }
        }
    }
    /**
* FrontEnd
*
* Module frontend
*
* @access public
*/
    function usual(&$out) {
        $this->admin($out);
    }
    /**
* hasp_panels search
*
* @access public
*/
    function search_hasp_panels(&$out) {
        require(dirname(__FILE__).'/hasp_panels_search.inc.php');
    }
    /**
* hasp_panels edit/add
*
* @access public
*/
    function edit_hasp_panels(&$out, $id) {
        require(dirname(__FILE__).'/hasp_panels_edit.inc.php');
    }
    /**
* hasp_panels delete record
*
* @access public
*/
    function delete_hasp_panels($id) {
        $rec=SQLSelectOne("SELECT * FROM hasp_panels WHERE ID='$id'");
        // some action for related tables
        SQLExec("DELETE FROM hasp_panels WHERE ID='".$rec['ID']."'");
    }

    function unsetLinked($config){
        $this->findLinked($config,false);
    }

    function setLinked($config){
        $this->findLinked($config,true);
        clearCacheData("hasp");
    }
    
    function findLinked($config, $add){
        $pattern = '/%([^%\'"]+)\.([^%\'"]+)%/';
        preg_match_all($pattern, $config, $matches);

        for ($i = 0; $i < count($matches[0]); $i++) {
            if ($add)
                addLinkedProperty($matches[1][$i], $matches[2][$i], $this->name);
            else
                removeLinkedProperty($matches[1][$i], $matches[2][$i], $this->name);
        }
        // link for templates
        $config = json_decode($config,true);
        foreach ($config['pages'] as $page){
            foreach ($page['objects'] as $object){
                if ($object["obj"] == 'template' && isset($object["linkedObject"]))
                {
                    $template = $config['templates'][$object['template']];
                    $template = json_encode($template);
                    $pattern = '/%\.([^%\'"]+)%/';
                    preg_match_all($pattern, $template, $matches);
                    for ($i = 0; $i < count($matches[0]); $i++) {
                        if ($add)
                            addLinkedProperty($object["linkedObject"], $matches[1][$i], $this->name);
                        else
                            removeLinkedProperty($object["linkedObject"], $matches[1][$i], $this->name);
                    }
                }
            }
        }
    }

    
    function sendMQTTCommand($topic, $command)
    {
        $this->getConfig();
        if ($this->config['MQTT_WRITE_METHOD'] == 2) {
            $this->log("Queue command to $topic: " . $command);
            addToOperationsQueue('openhasp_queue', $topic, json_encode($command), true);
            return 1;
        }

        $this->log("Sending command to $topic: " . $command);
        include_once(ROOT . "3rdparty/phpmqtt/phpMQTT.php");
        $client_name = "NSPanel module";
        if ($this->config['MQTT_AUTH']) {
            $username = $this->config['MQTT_USERNAME'];
            $password = $this->config['MQTT_PASSWORD'];
        }
        if ($this->config['MQTT_HOST']) {
            $host = $this->config['MQTT_HOST'];
        } else {
            $host = 'localhost';
        }
        if ($this->config['MQTT_PORT']) {
            $port = $this->config['MQTT_PORT'];
        } else {
            $port = 1883;
        }
        $mqtt_client = new Bluerhinos\phpMQTT($host, $port, $client_name);
        if (!$mqtt_client->connect(true, NULL, $username, $password)) {
            return 0;
        }
        $qos = $this->config['MQTT_QOS'] ?? 0;
        $mqtt_client->publish($topic, $command, $qos, 0);
        $mqtt_client->close();

    }

    function sendCommand($root_path, $command)
    {
        $topic = $root_path . '/command';
        $this->sendMQTTCommand($topic, $command);
    }
    
    function sendValue($root_path, $key, $value)
    {
        $topic = $root_path . '/command/'.$key;
        $this->sendMQTTCommand($topic, $value);
    }
    
    function sendBatch($root_path, $batch)
    {
        $keys = array_keys($batch);
        if (count($keys) == 1){
            $key = $keys[0];
            $this->sendValue($root_path, $key, $batch[$key]);
            return;
        }
        $data = array();
        foreach ($keys as $key) {
            $data[]=$key." ".$batch[$key];
        }
        $command = "json ".json_encode($data);
        $this->sendCommand($root_path, $command);
    }

    function api($params)
    {
        $table_name = "hasp_panels";
            
        if ($_REQUEST['topic']) {
            $this->processMessage($_REQUEST['topic'], $_REQUEST['msg']);
        }
        if ($params['request'][0]=='panels') {
            $panels=SQLSelect("SELECT * FROM `hasp_panels`");
            return $panels;
        }
        if ($params['request'][0]=='config') {
            $id = $params['request'][1];
            $rec = SQLSelectOne("SELECT * FROM `$table_name` WHERE ID='$id'");
            if ($rec){
                
                if ($_SERVER['REQUEST_METHOD']=='POST'){
                    $old_config = $rec['PANEL_CONFIG'];
                    $config = file_get_contents('php://input');
                    $rec['PANEL_CONFIG'] = $config;
                    SQLUpdate($table_name, $rec);
                    $this->unsetLinked($old_config);
                    $this->setLinked($rec['PANEL_CONFIG']);
                    return "ok";
                }
                else{
                    return $rec['PANEL_CONFIG'];
                }
            }
            else
                return "Not found";
        }
        if ($params['request'][0]=='reload') {
            $id = $params['request'][1];
            $panel=SQLSelectOne("SELECT * FROM hasp_panels WHERE ID='$id'");
            if ($panel){
                if (count($params['request'])==3)
                    $this->reloadPage($panel,$params['request'][2]);
                else
                    $this->reloadPages($panel);
                return "ok";
                }
            return "Not found";
        }
        if ($params['request'][0]=='page') {
            $id = $params['request'][1];
            $page = $params['request'][2];
            $rec = SQLSelectOne("SELECT * FROM `$table_name` WHERE ID='$id'");
            if ($rec){
                $batch = array();
                $batch["page"] = $page;
                $this->sendBatch($rec['MQTT_PATH'], $batch);
                return "ok";
            }
            return "Not found";
        }
        if ($params['request'][0]=='jsonl') {
            $id = $params['request'][1];
            $jsonl = file_get_contents('php://input');
            $rec = SQLSelectOne("SELECT * FROM `$table_name` WHERE ID='$id'");
            if ($rec){
                $batch = array();
                $batch["jsonl"] = $jsonl;
                $this->sendBatch($rec['MQTT_PATH'], $batch);
                return "ok";
            }
            return "Not found";
        }
    }
    function cleanObject(&$object){
        $events = array("up","down","release","long","hold","changed");
        foreach ($events as $event)
        {
            unset($object[$event."_linkedMethod"]);
            unset($object[$event."_linkedScript"]);
            unset($object[$event."_linkedTemplate"]);
            unset($object[$event."_command"]);
        }
        unset($object["linkedObject"]);
    }
    function reloadPage($panel,$index){
        $this->sendCommand($panel['MQTT_PATH'],"clearpage ".$index);
        $config = json_decode($panel['PANEL_CONFIG'], true);
        $page = $config["pages"][$index];
        $page_atr = array("page"=>$index);
            if (isset($page["comment"])) $page_atr["comment"] = $page["comment"];
            if (isset($page["back"])) $page_atr["back"] = $page["back"];
            if (isset($page["next"])) $page_atr["next"] = $page["next"];
            if (isset($page["prev"])) $page_atr["prev"] = $page["prev"];
            $jsonl = 'jsonl '.json_encode($page_atr);
            $this->sendCommand($panel['MQTT_PATH'],$jsonl);
            // Перебираем обьекты на панели
            foreach ($page['objects'] as $object) {
                if ($object["obj"] != "template"){
                    // перебираем все значения обьекта
                    foreach ($object as $key => $val) {
                        if (!is_string($val)) continue;
                        $object[$key] = $this->processValue($val, "", "");
                    }
                    // clean object
                    $this->cleanObject($object);
                    // send object
                    $jsonl = "jsonl ".json_encode($object);
                    $this->sendCommand($panel['MQTT_PATH'],$jsonl);
                }
                else if (isset($object["template"]))
                {
                    $this->addTemplate($panel,$object);
                }
                
            }
    }
    function reloadPages($panel){
        $config = json_decode($panel['PANEL_CONFIG'], true);
        // перебираем все страницы
        $pages = count($config["pages"]);
        for ($pi = 0; $pi < $pages; $pi++) {
            $this->reloadPage($panel,$pi);
        }
    }
    
    function reloadPanels(){
        $this->log("Reload panel's config");
        $panels = SQLSelect("SELECT * FROM hasp_panels");
        $total = count($panels);
        for ($i = 0; $i < $total; $i++) {
            $this->reloadPages($panels[$i]);
        }
    }
    
    function addTemplate($panel,$parent){
        //$this->log(json_encode($parent));
        $name = $parent["template"];
        $config = json_decode($panel['PANEL_CONFIG'], true);
        if (!isset($config['templates'])) return;
        if (!isset($config['templates'][$name])) return;
        $template = $config['templates'][$name];
        $this->mergeObjects($template[0],$parent);
        foreach ($template as $index=>$object) {
            if (!isset($object['tag']))
            {
                $tag = array();
                $tag["object"] = $parent["linkedObject"];
                $tag["template"] = $name;
                $tag["id"] = $object["id"];
                $tag["parent"] = $parent["id"];
                $object['tag'] = $tag;
            }
            $object["id"] = $parent["id"] + $object["id"];
            if ($index > 0 && isset($object["parentid"]))
                $object["parentid"] = $object["parentid"]+$parent["id"];
            // перебираем все значения обьекта
            foreach ($object as $key => $val) {
                if (!is_string($val)) continue;
                if ($val == '%.description%'){
                    $o = getObject($parent["linkedObject"]);
                    $object[$key] =  $o->description;
                }
                else if ($val == '%.name%'){
                    $object[$key] =  $parent["linkedObject"];
                }
                else
                {
                    $op = str_replace('%.', '%'.$parent["linkedObject"].'.', $val);
                    $object[$key] = $this->processValue($op, "", "");
                }
            }
            $this->cleanObject($object);
            // send object
            $jsonl = "jsonl ".json_encode($object);
            $this->sendCommand($panel['MQTT_PATH'],$jsonl);
        }
    }
    
    function mergeObjects(&$child, $parent){
        $ignore = array("id","obj","template");
            
        foreach ($parent as $key => $val) {
            if (!in_array($key, $ignore))
                $child[$key] = $parent[$key];
        }
    }
    
    function openTemplate($panel, $name, $ob){
        $config = json_decode($panel['PANEL_CONFIG'], true);
        if (!isset($config['templates'])) return;
        if (!isset($config['templates'][$name])) return;
        $template = $config['templates'][$name];
        foreach ($template as $object) {
            if (!isset($object['tag']))
            {
                $tag = array();
                $tag["object"] = $ob;
                $tag["template"] = $name;
                $tag["id"]=$object['id'];
                $object['tag'] = $tag;
            }
            $object['page'] = $panel['CURRENT_PAGE'];
            // перебираем все значения обьекта
            foreach ($object as $key => $val) {
                if (!is_string($val)) continue;
                if ($val == '%.description%'){
                    $o = getObject($ob);
                    $object[$key] =  $o->description;
                }
                else if ($val == '%.name%'){
                    $object[$key] =  $tag;
                }
                else
                {
                    $op = str_replace('%.', '%'.$ob.'.', $val);
                    $object[$key] = $this->processValue($op, "", "");
                }
            }
            $this->cleanObject($object);
            // send objects
            $jsonl = "jsonl ".json_encode($object);
            $this->sendCommand($panel['MQTT_PATH'],$jsonl);
        }
    }
    
    function closeTemplate($panel, $name){
        $config = json_decode($panel['PANEL_CONFIG'], true);
        if (!isset($config['templates'])) return;
        if (!isset($config['templates'][$name])) return;
        $template = $config['templates'][$name];
        foreach ($template as $object) {
            $cmd = "p".$panel['CURRENT_PAGE']."b".$object['id'].".delete";
            $this->sendCommand($panel['MQTT_PATH'],$cmd);
        }
    }

    function processPanelMessage($panel, $topic, $msg)
    {
        $key = basename($topic);
        
        $this->log("Processing (" . $panel['TITLE'] . ")  $topic - $key: $msg");
        
        
        if ($key == "page"){
            $panel['CURRENT_PAGE'] = $msg;
            SQLUpdate("hasp_panels", $panel);
            $this->setLinkedProperty($panel,"page", $msg);
            $config = json_decode($panel['PANEL_CONFIG'], true);
            if (isset($config["page_linkedProperty"])){
                $this->updateValues($panel["ID"],"page", $config["page_linkedProperty"],$msg);
            }
        }
        else if ($key == "LWT"){
            if ($panel['ONLINE'] != $msg)
            {
                $panel['ONLINE'] = $msg;
                SQLUpdate("hasp_panels", $panel);
                $this->setLinkedProperty($panel,"LWT", $msg);
            }
        }
        else if ($key == "statusupdate"){
            $value= json_decode($msg,true);
            if ($value["uptime"] < 30)
                $this->reloadPages($panel);
            $panel['IP'] = $value["ip"];
            SQLUpdate("hasp_panels", $panel);
            $this->setLinkedProperty($panel,"ip", $value["ip"]);
        }
        else if ($key == "idle"){
            $res = $this->setLinkedProperty($panel,"idle", $msg);
            if(!$res){ // default action by idle
                if ($msg == "long")
                {
                    // turnoff display
                    $this->sendValue($panel['MQTT_PATH'], "backlight" , 0);
                    // save display obj
                    $object = array("page"=>0,"id"=>99,"obj"=>"obj","x"=>0,"y"=>0,"w"=>480,"h"=>480,"radius"=>0,"hidden"=>0,"bg_grad_dir"=>0,"bg_color"=>"black","border_width"=>0);
                    $jsonl = "jsonl ".json_encode($object);
                    $this->sendCommand($panel['MQTT_PATH'],$jsonl);
                }
                if ($msg == "off"){
                    // delete save dispaly obj
                    $this->sendCommand($panel['MQTT_PATH'],"p0b99.delete");
                    // turn display
                    $this->sendValue($panel['MQTT_PATH'], "backlight" , 255);
                }
            }
        }
        else if ($key == "backlight"){
            $backlight = json_decode($msg,true);
            $this->setLinkedProperty($panel,"brightness", $backlight['brightness']);
            $this->setLinkedProperty($panel,"backlight", $backlight['state']);
        }
        else if ($this->str_contains($key, "output")){
            $value= json_decode($msg,true);
            $state = $value["state"];
            if ($state == "on") $state=1; else $state=0;
            $this->setLinkedProperty($panel, $key, $state);
        }
        else if (preg_match('/^p(\d+)b(\d+)$/', $key, $matches)) {
            $page_index = $matches[1];
            $object_id = $matches[2];
            $event = json_decode($msg,true);
            $config = json_decode($panel['PANEL_CONFIG'], true);
            if ( $page_index > count($config["pages"])-1) return;
            $page = $config["pages"][$page_index];
            $object = null;
            if (isset($event["tag"]))
            {
                foreach ($config['templates'][$event['tag']['template']] as $index => $ob) {
                    if ($ob['id'] == $event['tag']['id']){
                        $object = $ob;
                        if ($index==0)
                            foreach ($page["objects"] as $pob){
                                if ($pob["id"] == $event['tag']['parent']){
                                    $this->mergeObjects($object, $pob);
                                    break;
                                }
                            }
                        
                        foreach ($object as $key => $val) {
                            $object[$key] = str_replace('%.', '%'.$event["tag"]["object"].'.', $val);
                            if ($val[0] == '.')
                               $object[$key] = $event["tag"]["object"].$val;
                        }
                        break;
                    }
                }
            }
            else
            {
                foreach ($page['objects'] as $ob) {
                    if ($ob["id"] == $object_id){
                        $object = $ob;
                        break;
                    }
                }
            }   
            
            $this->log(json_encode($object));
                
            if ($object){
                    
                $event["object"]=$key;
                $event["page"]=$page_index;
                $event["id"]=$object["id"];
                // templates
                if (isset($object[$event['event']."_linkedTemplate"]) && isset($object['linkedObject'])){
                    $this->openTemplate($panel, $object[$event['event']."_linkedTemplate"], $object["linkedObject"]);
                    return;
                }
                // commands
                if (isset($object[$event['event']."_command"])){
                    $cmd = $object[$event['event']."_command"];
                    if ($cmd == 'delete')
                        $this->sendCommand($panel["MQTT_PATH"],"p`$page_index`b`$object_id`.delete");
                    if ($cmd == 'close')
                        $this->closeTemplate($panel,$event['tag']['template']);
                }
                if (isset($object[$event["event"]."_linkedMethod"])){
                    callMethodSafe($object[$event["event"]."_linkedMethod"],array('event' => $event));
                    return; // выполняется только метод
                }
                if (isset($object[$event["event"]."_linkedScript"]))
                {
                    runScriptSafe($object[$event["event"]."_linkedScript"],array('event' => $event));
                    return; // выполняется только скрипт
                }
                $default_event = "up"; // event по умолчанию, на который осуществляется установка значения в привязанное свойство
                if (isset($config["event_value"]))
                    $default_event = $config["value_event"];
                if ($object["obj"] == 'dropdown' || $object["obj"] == 'roller')
                    $default_event = "changed";
                if ($event["event"] == $default_event){
                    if (isset($event["val"]) && isset($object["val"])){
                        $this->setValue($object["val"],$event["val"]);
                        $this->updateValues($panel["ID"],$key.".val",$object["val"],$event["val"]);
                    }
                    if (isset($event["text"]) && isset($object["text"])){
                        $this->setValue($object["text"],$event["text"]);
                        $this->updateValues($panel["ID"],$key.".text",$object["val"],$event["val"]);
                    }
                    if (isset($event["color"]) && isset($object["color"]))
                    {
                        $this->setValue($object["color"],$event["color"]);
                        $this->updateValues($panel["ID"],$key.".color",$object["val"],$event["val"]);
                    }
                 }
             }
            
        }
    }
    
    function setValue($op,$val){
        $pattern = '/%([^%]+)\.([^%]+)%/';
        if (preg_match($pattern, $op, $matches))
        {
            if (getGlobal($matches[1].".".$matches[2]) != $val)
                setGlobal($matches[1].".".$matches[2], $val, array($this->name => '0'), $this->name);
            return true;
        }
        return false;
    }
    
    function setLinkedProperty($panel,$name,$value){
        $config = json_decode($panel['PANEL_CONFIG'], true);
        if (isset($config[$name."_linkedProperty"]))
            return $this->setValue($config[$name."_linkedProperty"],$value);
        return false;
    }

    function processMessage($topic, $msg)
    {
        $this->getConfig();
        if (preg_match('/discovery/', $topic)) {
            $discovery= json_decode($msg,true);
            if (!isset($discovery["node_t"])) return;
            $mqtt_path = substr($discovery["node_t"], 0, -1);
            $rec = SQLSelectOne("SELECT * FROM hasp_panels WHERE MQTT_PATH='$mqtt_path'");
            if (!$rec['ID']){
                $rec['TITLE'] = $discovery["node"];
                $rec['MQTT_PATH'] = $mqtt_path;
                SQLInsert("hasp_panels", $rec);
            }
            return;
        }
        if (preg_match('/command/', $topic)) return;
        $panels = SQLSelect("SELECT * FROM hasp_panels");
        $total = count($panels);
        for ($i = 0; $i < $total; $i++) {
            if (is_integer(strpos($topic, $panels[$i]['MQTT_PATH']))) {
                $this->processPanelMessage($panels[$i], $topic, $msg);
                return;
            }
        }
    }

    function processSubscription($event, $details='') {
        $this->getConfig();
        if ($event=='SAY') {
            $level=$details['level'];
            $message=$details['message'];
            //...
        }
    }
    
    
    
    function propertySetHandle($object, $property, $value)
    {   
        //$this->getConfig();
        $this->log("PropertySetHandle: ". $object .".". $property ."=". $value);
        $op = "%".$object.".".$property."%";
        $found = $this->updateValues(0,"",$op, $value);
        if (!$found) {
            removeLinkedProperty($object, $property, $this->name);
        }
    }
    
    function updateValues($panel_id, $name_value, $op, $value)
    {
        $found = 0;
        $cache = checkFromCache("hasp:".$op);
        if ($cache)
        {
            $cache = json_decode($cache,true);
            
            foreach ($cache as $key => $device){
                $batch = $device["batch"];
                foreach ($batch as $obj => $val){
                    $data = $this->processValue($val, $op, $value);
                    $batch[$obj] = $data;
                }
                if ($key == $panel_id)
                {
                    if ($name_value != "")
                        unset($batch[$name_value]);
                }
                if (!empty($batch))
                {
                    $found = true;
                    $this->sendBatch($device['MQTT'], $batch);
                }
            }
            if ($found)
                return $found;
        }
        
        $cache = array();
        $panels = SQLSelect("SELECT * FROM hasp_panels");
        $total = count($panels);
        for ($i = 0; $i < $total; $i++) {
            $batch = array();
            $config = json_decode($panels[$i]['PANEL_CONFIG'], true);
            // _linkedProperty
            foreach ($config as $key => $val){
                if ($panel_id != 0) break;
                if ($val==$op){
                    $found = 1;
                    $pattern = '/([^_]+)_linkedProperty/';
                    if (preg_match($pattern, $key, $matches))
                    {
                        $found = 1;
                        $name = $matches[1];
                        if ($name == 'backlight')
                            $batch["backlight"] = "{\"state\":\"$value\"}";
                        if ($name == 'brightness')
                            $batch["backlight"] = "{\"brightness\":\"$value\"}";
                        if ($name == 'page')
                            $batch["page"] = $value;
                        if ($name == 'idle')
                            $batch["idle"] = $value;
                        if ($this->str_contains($name, "output")){
                            $state = "{\"state\":$value}";
                            $batch[$name] = $state;
                        }
                    }
                }
            }
            
            // перебираем все страницы
            $pages = count($config["pages"]);
            for ($pi = 0; $pi < $pages; $pi++) {
                $page = $config["pages"][$pi];
                // Перебираем обьекты на панели
                foreach ($page['objects'] as $object) {
                    
                    //for template
                    if ($object["obj"] == "template" && isset($object["template"]))
                    {
                        $template = $config["templates"][$object["template"]];
                        $this->mergeObjects($template[0],$object);
                        foreach ($template as $index => $child)
                        {
                            $id = $child["id"] + $object["id"];
                            foreach ($child as $key => $val) {
                                $str = str_replace('%.', '%'.$object["linkedObject"].'.', $val);
                                if (is_string($str) && $this->str_contains($str, $op)){
                                    $found = 1;
                                    $name = "p".$pi."b".$id.".".$key;
                                    $data = $str;
                                    $batch[$name] = $data;
                                }
                            }
                            
                        }
                    }
                    else{ // другие обьекты
                        // перебираем все значения обьекта
                        foreach ($object as $key => $val) {
                            if (is_string($val) && $this->str_contains($val, $op)){
                                $found = 1;
                                $name = "p".$pi."b".$object["id"].".".$key;
                                $data = $val;
                                $batch[$name] = $data;
                            }
                        }    
                    }
                }
            }
            $cache[$panels[$i]['ID']] = array("MQTT"=>$panels[$i]['MQTT_PATH'],"batch"=>$batch);
            if (!empty($batch))
            {
                foreach ($batch as $key=> $val){
                    $data = $this->processValue($val, $op, $value);
                    $batch[$key] = $data;
                }
                if ($panels[$i] == $panel_id)
                {
                    unset($batch[$name_value]);
                }
                
                $this->sendBatch($panels[$i]['MQTT_PATH'], $batch);
            }
            
        }
        if ($found)
            saveToCache("hasp:".$op, json_encode($cache));
        
        return $found;
    }
    
    function processValue($template, $op, $value){
        if ($op)
            $data = str_replace($op, $value, $template);
        else
            $data = $template;
        if ($this->str_contains($data, '%'))
            $data = processTitle($data);
        if (preg_match('/{{\s*([^}]+)\s*}}/', $data, $matches)){
            $code = $matches[1];
            try {
                // Execute the code and get its result
                $data = eval('return ' . $code . ';');
                $this->log("Process template: ". $template. " => ".$code." Result:".$data);
            } catch (DivisionByZeroError $e) {
                $this->log("Error: Division by zero is not allowed (".$template.")");
            } catch (ParseError $e) {
                $this->log("Error: Invalid PHP code (".$template.")");
            } catch (Exception $e) {
                $this->log("Error: ". $e->getMessage() ."(".$template.")");
            }
        }
        return $data;
    }

    function processCycle() {
        $this->getConfig();
        //to-do
    }
    
    
    function log($message) {
        //echo $message . "\n";
        // DEBUG MESSAGE LOG
        if($this->config['DEBUG'] == 1)
            DebMes($message, $this->name);
    }
    
    function str_contains($haystack, $needle) {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }
    
    /**
* Install
*
* Module installation routine
*
* @access private
*/
    function install($data='') {
        subscribeToEvent($this->name, 'SAY');
        parent::install();
    }
    /**
* Uninstall
*
* Module uninstall routine
*
* @access public
*/
    function uninstall() {
        SQLExec('DROP TABLE IF EXISTS hasp_panels');
        parent::uninstall();
    }
    /**
* dbInstall
*
* Database installation routine
*
* @access private
*/
    function dbInstall($data) {
        /*
hasp_panels - 
*/
        $data = <<<EOD
        hasp_panels: ID int(10) unsigned NOT NULL auto_increment
        hasp_panels: TITLE varchar(100) NOT NULL DEFAULT ''
        hasp_panels: MQTT_PATH varchar(100) NOT NULL DEFAULT '' 
        hasp_panels: CURRENT_PAGE varchar(100) NOT NULL DEFAULT ''
        hasp_panels: PANEL_CONFIG text NOT NULL DEFAULT ''
        hasp_panels: ONLINE varchar(100) NOT NULL DEFAULT ''
        hasp_panels: IP varchar(100) NOT NULL DEFAULT ''
        
        EOD;
        parent::dbInstall($data);
    }
    // --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgRmViIDEyLCAyMDI0IHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
