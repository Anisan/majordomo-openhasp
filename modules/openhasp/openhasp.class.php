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
 function processSubscription($event, $details='') {
 $this->getConfig();
  if ($event=='SAY') {
   $level=$details['level'];
   $message=$details['message'];
   //...
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
