<?php
/*
* @version 0.1 (wizard)
*/
if ($this->owner->name=='panel') {
    $out['CONTROLPANEL']=1;
}
$table_name='hasp_panels';
$rec=SQLSelectOne("SELECT * FROM $table_name WHERE ID='$id'");

if (gr('load_demo')) {
    $file_path = DIR_MODULES . 'openhasp/sample_config.json';
    $file = file_get_contents($file_path);
    $rec['PANEL_CONFIG'] = $file;
    
    SQLUpdate($table_name, $rec);
    $this->redirect("?id=" . $rec['ID'] . "&view_mode=" . $this->view_mode . "&tab=" . $this->tab);
}

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
        $old_config = $rec['PANEL_CONFIG'];
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
        $this->unsetLinked($old_config);
        $this->setLinked($rec['PANEL_CONFIG']);
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
