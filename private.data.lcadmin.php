<?php

$_LW->REGISTERED_MODULES['lcadmin']=array(
   'title'=>'LC Admin',
  'data'=>array('link'=>'?lcadmin_page','order'=>100,
		'managers'=>array('lcadmin_page','lcadmin_copywidget','lcadmin_envdump','lcadmin_normalize'),
    'editors'=>array(),
		'subnav'=>array(array('title'=>'Short Cuts','url'=>'/livewhale/?lcadmin_page','id'=>'page_lcadmin_page'),array('title'=>'Copy Widgets','url'=>'/livewhale/?lcadmin_copywidget','id'=>'page_lcadmin_copywidget'),array('title'=>'Normalize Data','url'=>'/livewhale/?lcadmin_normalize','id'=>'page_lcadmin_normalize'),array('title'=>'Env Dump','url'=>'/livewhale/?lcadmin_envdump','id'=>'page_lcadmin_envdump'))
		), //array('title'=>'Submenu Item','url'=>'/livewhale/?foo_sub','id'=>'page_foo_sub')
  'flags'=>array('has_own_tab','is_admin_only'),
   'handlers'=>array('onLoad','onActivate','onSession','onManager','onManagerSubmit','onEditor','onEditorSubmit')
  );

require_once($_LW->INCLUDES_DIR_PATH.'/client/modules/lcadmin/includes/class.data.lcadmin.php');
class LiveWhaleDataLcadmin extends DataLCAdmin{

    protected $NAME = 'lcadmin';
    protected $validation = array();
 
    protected function configure(){
        $this->module_path = dirname(__FILE__); //set module path
        $GLOBALS['template'] = new Template; //setup object for xphp templating methods
        $this->validation['lc_admin_copywidget'] = array(
                        'approved' => array('require'=>false,'type'=>'bool'),
                        'dest_groups'=>array('require'=>true,),
                        'source_widget_id'=>array('type'=>'num','set'=>'source_widget','require'=>false,'callback'=>'validate_widget_exists'),
                        'source_widget_name'=>array('set'=>'source_widget','require'=>false,'callback'=>'validate_widget_exists'),
                        'dest_widget_prefix'=>array('require'=>false,'maxlen'=>10),
                        'dest_widget_sufix'=>array('require'=>false,'maxlen'=>10),
                        'update_existing'=>array('type'=>'bool','require'=>false),
                        'source_widget'=>array('type'=>'set','require'=>true,'highlight'=>'source_widget','reqtype'=>'or','callback'=>'validate_widget_exists')
                       );
        parent::configure(); //call parents configure method
        //$this->registerTab($this->CONF['title'],$this->CONF['data']['link'],1,0,0);
     }
 
     /**
      * Page Template Callbacks
      * callbacks are registered and allowed only if they are listed in managers array
      */
    protected function lcadmin_page(){
        $GLOBALS['title'] = 'LC Admin Utils';
     }
 
    protected function lcadmin_copywidget(){
        $GLOBALS['title'] = 'Copy Widgets';
    }
    
    protected function lcadmin_envdump(){
        $GLOBALS['title'] = 'Env Dump';
        print_r($_SESSION['livewhale']);
    }
    
    protected function lcadmin_normalize(){
        $GLOBALS['title'] = 'Env Dump';
        
    }
    
    protected function lcadmin_normalize_submit(){
        $GLOBALS['title'] = 'Copy Widgets';
        $GLOBALS['job_results'] = print_r($_POST, true);
        
        //external plugins loaded here;
        //if(isset($_POST['jobs']))
        //    if(in_array('clean_slashes',$_POST['jobs'])){
        //        include_once($_LW->INCLUDES_DIR_PATH.'/client/modules/lcadmin/includes/class.batchCleanSlashes.php');
        //    }
    }
    /*
     * Copywidget sumbit is the callback for the manager submit on the copywidget page
     */
    protected function lcadmin_copywidget_submit(){
        $GLOBALS['title'] = 'Copy Widgets';
        $cleaned = $this->validate_fields($this->validation['lc_admin_copywidget'],$_REQUEST);
        if(count($cleaned['errors']))
            $GLOBALS['errors'] = serialize($cleaned['errors']);
        else{ // run the widget copy
            include_once($this->LW->INCLUDES_DIR_PATH.'/client/modules/lcadmin/includes/class.BatchWidgetMaker.php');
            $config['table'] = 'livewhale_widgets';
            
            $widget_maker = new BatchWidgetMaker($config,$this->LW);
            
            //escape for sql injection
            $fields = $this->scrub_sql($cleaned['params']);
            //need to handle id or name here
            if(strlen($fields['source_widget_name']))
                $widget = $fields['source_widget_name'];
            else $widget = $fields['source_widget_id'];
            
            $result = $widget_maker->gen_widgets($widget,$fields['dest_widget_prefix'],$fields['dest_widget_sufix'],$fields['dest_groups'],($fields['approved'] == 'true' ? false : true),($fields['update_existing'] == 'on' ? true : false));
            if($result == 'simulate')
                $GLOBALS['simulate'] = true;
            else $GLOBALS['simulate'] = false;
            
            $GLOBALS['success'] = ($fields['approved'] == 'true' ? "Job Report:<br />\n" : "Simulated Report:<br />\n").$widget_maker->get_log();
        }
    }
    
    protected function validate_widget_exists($fields){
        $table='livewhale_widgets';
        $error_str = '';
        $results = array();
        
        if(array_key_exists('source_widget',$fields)){
            if(strlen($fields['source_widget_name']) && strlen($fields['source_widget_id'])){
                $query = "select name, id from $table where name = '$fields[source_widget_name]' and id = $fields[source_widget_id];";
                $error = "id and name do not match the same widget";
            }
        }
        elseif(array_key_exists('source_widget_id',$fields)){
            $query = "select id from $table where id = $fields[source_widget_id];";
            $error = "No widget with id $fields[source_widget_id]";
        }
        elseif(array_key_exists('source_widget_name',$fields)){
            $query = "select name from $table where name = '$fields[source_widget_name]';";
            $error = "No widget with name $fields[source_widget_name]";
        }
        else return "No matching fields to check";
        
        if(isset($query)){
            $result = $this->LW->query($query);
            if(!$result->num_rows)
                return $error;
        }
    }

}

//place dummy templating functions here
class Template{

   //templating functions
   public function message_box($xphp,$args){
    $message_str = '';
    if(isset($args['errors'])){
        $errors = unserialize((string)$args['errors']);

        foreach($errors as $field => $message)
            $message_str .= '<p>'.$message.'</p>';

        return "<div id=\"lw_messages\">
            <div id=\"messages\">
                <div class=\"msg_failure\">
                $message_str
                </div>
            </div>
        </div>";
    }
    elseif(isset($args['message'])){
        $message_str = (string)$args['message'];
        return "<div id=\"lw_messages\">
            <div id=\"messages\">
                <div class=\"msg_success\">
                $message_str
                </div>
            </div>
        </div>";
    }
   }

    public function json_errors($xphp,$args){
        $errors = unserialize((string)$args['errors']);
        $js = '<script type="text/javascript">'."\n";
        $js .= 'var field_errors = \''.json_encode($errors)."'";
        $js .= '</script>';
        return $js;
   }

    public function value_fill($xphp,$args){
        $field = (string)$args['field'];
        if(isset($args['default']))
            $default = (string)$args['default'];
        if(isset($_POST[$field])){
            $value = $_POST[$field];
            return " value=\"$value\" ";
        }
        else return " value=\"$default\" ";
   }

    public function value_checked($xphp,$args){
        $field = (string)$args['field'];
        if(isset($args['default']))
            $default = (string)$args['default'];
    
        if(isset($_POST[$field]))
            return " checked ";
        else return $default;
   }
   
    public function submit_button($xphp,$args){
        return '<input type="hidden" id="approved" name="approved" value="true">
<button id="submit_button" >Create Copies</button>';
   }
   
    public function approve_button($xphp,$args){
        return '<input type="hidden" id="approved" name="approved" value="false">
<button id="submit_button" >Test Run</button>';
   }

}