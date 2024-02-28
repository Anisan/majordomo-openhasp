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
        $out['DEBUG_MODE'] = $this->config['DEBUG_MODE'];

        if ($this->view_mode == 'update_settings') {
            $this->config['MQTT_HOST'] = gr('mqtt_host', 'trim');
            $this->config['MQTT_USERNAME'] = gr('mqtt_username', 'trim');
            $this->config['MQTT_PASSWORD'] = gr('mqtt_password', 'trim');
            $this->config['MQTT_AUTH'] = gr('mqtt_auth', 'int');
            $this->config['MQTT_PORT'] = gr('mqtt_port', 'int');
            $this->config['MQTT_QUERY'] = gr('mqtt_query', 'trim');
            $this->saveConfig();
            setGlobal('cycle_openhaspControl', 'restart');
            $this->redirect("?");
        }
        if ($this->view_mode == 'reloadpage_hasp_panels') {
            $this->reloadPages($this->id);
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
        $pattern = '/%([^%]+)\.([^%]+)%/';
        preg_match_all($pattern, $config, $matches);

        $values = array();
        for ($i = 0; $i < count($matches[0]); $i++) {
            removeLinkedProperty($matches[1][$i], $matches[2][$i], $this->name);
        }
    }

    function setLinked($config){
        $pattern = '/%([^%]+)\.([^%]+)%/';
        preg_match_all($pattern, $config, $matches);

        $values = array();
        for ($i = 0; $i < count($matches[0]); $i++) {
            addLinkedProperty($matches[1][$i], $matches[2][$i], $this->name);
        }
    }

    
    function sendMQTTCommand($topic, $command)
    {
        DebMes("Sending custom command to $topic: " . $command, 'openhasp');
        $this->getConfig();
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
        $mqtt_client->publish($topic, $command, 0, 0);
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

    function api($params)
    {
        if ($_REQUEST['topic']) {
            $this->processMessage($_REQUEST['topic'], $_REQUEST['msg']);
        }
        if ($params['request'][0]=='panels') {
            $panels=SQLSelect("SELECT * FROM `hasp_panels`");
            return $panels;
        }
        if ($params['request'][0]=='config') {
            $id = $params['request'][1];
            $table_name = "hasp_panels";
            $rec = SQLSelectOne("SELECT * FROM `$table_name` WHERE ID='$id'");
            if ($rec){
                
                if ($_SERVER['REQUEST_METHOD']=='POST'){
                    $old_config = $this->getPanelConfig($rec['PANEL_CONFIG']);
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
        }
        if ($params['request'][0]=='reload') {
            $id = $params['request'][1];
            $this->reloadPages($id);
            return "ok";
        }
    }
    
    function reloadPages($id){
        $panel=SQLSelectOne("SELECT * FROM hasp_panels WHERE ID='$id'");
        // clear all pages
        $this->sendCommand($panel['MQTT_PATH'],"clearpage all");
        $config = json_decode($panel['PANEL_CONFIG'], true);
        // перебираем все страницы
        $pages = count($config["pages"]);
        for ($pi = 0; $pi < $pages; $pi++) {
            // send page
            $page = $config["pages"][$pi];
            $page_atr = array("page"=>$pi);
            if (isset($page["comment"])) $page_atr["comment"] = $page["comment"];
            if (isset($page["back"])) $page_atr["back"] = $page["back"];
            if (isset($page["next"])) $page_atr["next"] = $page["next"];
            if (isset($page["prev"])) $page_atr["prev"] = $page["prev"];
            $jsonl = 'jsonl '.json_encode($page_atr);
            $this->sendCommand($panel['MQTT_PATH'],$jsonl);
            // Перебираем обьекты на панели
            foreach ($page['objects'] as $object) {
                // перебираем все значения обьекта
                foreach ($object as $key => $val) {
                    if (str_contains($val, '%')){
                        $object[$key] = processTitle($val);
                    }
                }
                // send object
                $jsonl = "jsonl ".json_encode($object);
                $this->sendCommand($panel['MQTT_PATH'],$jsonl);
            }
        }
    }

    function processPanelMessage($panel, $topic, $msg)
    {
        $key = basename($topic);
        
        DebMes("Processing (" . $panel['TITLE'] . ")  $topic $key:\n$msg", 'openhasp');
        
        
        if ($key == "page"){
            $panel['CURRENT_PAGE'] = $msg;
            SQLUpdate("hasp_panels", $panel);
            $this->setLinkedProperty($panel,"page", $msg);
        }
        else if ($key == "LWT"){
            if ($panel['ONLINE'] != $msg)
            {
                $panel['ONLINE'] = $msg;
                SQLUpdate("hasp_panels", $panel);
                $this->setLinkedProperty($panel,"LWT", $msg);
                if ($msg=="online")
                    $this->reloadPages($panel['ID']);
            }
        }
        else if ($key == "statusupdate"){
            $value= json_decode($msg,true);
            $panel['IP'] = $value["ip"];
            SQLUpdate("hasp_panels", $panel);
            $this->setLinkedProperty($panel,"ip", $msg);
        }
        else if ($key == "idle"){
            $res = $this->setLinkedProperty($panel,"idle", $msg);
            if(!$res){
                if ($msg == "long")
                    $this->sendValue($panel['MQTT_PATH'], "backlight" , 0);
                if ($msg == "off")
                    $this->sendValue($panel['MQTT_PATH'], "backlight" , 255);
            }
        }
        else if (preg_match('/^p(\d+)b(\d+)$/', $key, $matches)) {
            $page_index = $matches[1];
            $object_id = $matches[2];
            $event = json_decode($msg,true);
            $config = json_decode($panel['PANEL_CONFIG'], true);
            if ( $page_index > count($config["pages"])-1) return;
            $page = $config["pages"][$page_index];
            foreach ($page['objects'] as $object) {
                if ($object["id"] == $object_id){
                        if (isset($object[$event["event"]."_linkedMethod"]))
                            callMethod($object[$event["event"]."_linkedMethod"],array('event' => $event));
                        $default_event = "up";
                        if (isset($config["event_value"]))
                            $default_event = $config["value_event"];
                        if ($event["event"] == $default_event){
                            if (isset($event["val"]) && isset($object["val"]))
                                $this->setValue($object["val"],$event["val"]);
                            if (isset($event["text"]) && isset($object["text"]))
                                $this->setValue($object["text"],$event["text"]);
                            if (isset($event["color"]) && isset($object["color"]))
                                $this->setValue($object["color"],$event["color"]);
                        }
                }
            }
        }
    }
    
    function setValue($op,$val){
        $pattern = '/%([^%]+)\.([^%]+)%/';
        if (preg_match($pattern, $op, $matches))
        {
            if (gg($matches[1].".".$matches[2]) != $val)
                sg($matches[1].".".$matches[2], $val, $this->name);
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
        $op = "%".$object.".".$property."%";
        $found = 0;
        $panels = SQLSelect("SELECT * FROM hasp_panels");
        $total = count($panels);
        for ($i = 0; $i < $total; $i++) {
            $config = json_decode($panels[$i]['PANEL_CONFIG'], true);
            // _linkedProperty
            foreach ($config as $key => $val){
                if ($val==$op){
                    $pattern = '/([^_]+)_linkedProperty/';
                    if (preg_match($pattern, $key, $matches))
                    {
                        $name = $matches[1];
                        $this->sendValue($panels[$i]['MQTT_PATH'], $name , $value);
                    }
                }
            }
            
            // перебираем все страницы
            $pages = count($config["pages"]);
            for ($pi = 0; $pi < $pages; $pi++) {
                $page = $config["pages"][$pi];
                // Перебираем обьекты на панели
                foreach ($page['objects'] as $object) {
                    // перебираем все значения обьекта
                    foreach ($object as $key => $val) {
                        if (is_string($val) && str_contains($val, $op)){
                            $name = "p".$pi."b".$object["id"].".".$key;
                            $data = str_replace($op, $value, $val);
                            $this->sendValue($panels[$i]['MQTT_PATH'], $name , $data);
                            $found = 1;
                        }
                    }
                }
            }
        }
        if (!$found) {
            removeLinkedProperty($object, $property, $this->name);
        }
    }

    function processCycle() {
        $this->getConfig();
        //to-do
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
