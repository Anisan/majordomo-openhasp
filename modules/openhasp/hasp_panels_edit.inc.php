<?php
/*
* @version 0.1 (wizard)
*/
  if ($this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  }
  $table_name='hasp_panels';
  $rec=SQLSelectOne("SELECT * FROM $table_name WHERE ID='$id'");
  if ($this->mode=='update') {
   $ok=1;
  

    if ($this->tab == '') {
        $rec['TITLE'] = gr('title');
        if ($rec['TITLE'] == '') {
            $out['ERR_TITLE'] = 1;
            $ok = 0;
        }
        $rec['MQTT_PATH'] = gr('mqtt_path');
        $rec['MQTT_PATH'] = preg_replace('/\/$/', '', $rec['MQTT_PATH']);
        if ($rec['MQTT_PATH'] == '') {
            $out['ERR_TITLE'] = 1;
            $ok = 0;
        }
    }

    if ($this->tab == 'config') {
        $rec['PANEL_CONFIG'] = gr('panel_config');
        if (!is_array(json_decode($rec['PANEL_CONFIG'], true))) {
            $ok = 0;
            $out['ERR_PANEL_CONFIG'] = 1;
        }
        /*
        if (!$rec['PANEL_CONFIG']) {
            $out['ERR_PANEL_CONFIG'] = 1;
            $ok = 0;
        }
        */
    }
    
  //UPDATING RECORD
   if ($ok) {
    if ($rec['ID']) {
     SQLUpdate($table_name, $rec); // update
    } else {
     $new_rec=1;
     $rec['ID']=SQLInsert($table_name, $rec); // adding new record
    }
    $out['OK']=1;
   } else {
    $out['ERR']=1;
   }
  }
  if (is_array($rec)) {
   foreach($rec as $k=>$v) {
    if (!is_array($v)) {
     $rec[$k]=htmlspecialchars($v);
    }
   }
  }
  outHash($rec, $out);
