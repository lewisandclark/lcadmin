<?php

/* See notes for parant class
 *
 */

require_once($_LW->INCLUDES_DIR_PATH.'/client/modules/lcadmin/includes/class.lcadmin.php');
class DataLCAdmin extends LCAdmin{
    protected $tabs;
    
    protected function configure(){
        $this->CONF = $this->LW->REGISTERED_MODULES[$this->NAME]; //copy of config
        $this->type = 'data';
    }
    
    public function onTabs($tabs) {
        $this->debug();
        if($this->is_super() && !empty($this->tabs))
            foreach($this->tabs as $tab => $array){
                $tabs[$tab] = $array;
            }
    return $tabs;
    }
    
    public function onActivate(){
        $this->debug();
        //if($this->is_super())
        //    print_r($this->LW->REGISTERED_MODULES);die;
        return;
    }
    
    /**
     * Manager Handlers
     */
    public function onManager(){
        $this->debug();
        $redirect = false;
        $method = $this->LW->page;

        $this->ensure_authorized();
        
        if(method_exists($this,$method)){
            if(isset($this->LW->AUTHORIZED_MODULES[$this->NAME]) && in_array($method,$this->CONF['data']['managers']))
                call_user_func(array($this,$method));
            else $redirect = true;
        }
        
        if($redirect)
            $this->redirect_to('/livewhale/'.$this->CONF['data']['link']);
    }
    
    public function onManagerSubmit(){
        $this->debug();
       $redirect = false;
       $manager = $this->LW->page;
       $method = $manager.'_submit';
       
       $this->ensure_authorized();
       
       if(method_exists($this,$method)){
            if(isset($this->LW->AUTHORIZED_MODULES[$this->NAME]) && in_array($manager,$this->CONF['data']['managers']))
                call_user_func(array($this,$method));
            else $redirect = true;
        }
        
        if($redirect)
            $this->redirect_to('/livewhale/'.$this->CONF['data']['link']);
    }
    
    /**
     * Editor Handlers
     */
    
    public function onEditor(){
        $this->debug();
        $redirect = false;
        $method = $this->LW->page;

        $this->ensure_authorized();
        
        if(method_exists($this,$method)){
            if(isset($this->LW->AUTHORIZED_MODULES[$this->NAME]) && in_array($method,$this->CONF['data']['editors']))
                call_user_func(array($this,$method));
            else $redirect = true;
        }
        
        if($redirect)
            $this->redirect_to('/livewhale/'.$this->CONF['data']['link']);
    }
    
    public function onEditorSubmit(){
        $this->debug();
        $redirect = false;
        $method = $this->LW->page.'Submit';
       
        $this->ensure_authorized();
        if($this->is_super())
            echo 'You are not authorized to view this page';die;
        
        if(isset($this->LW->AUTHORIZED_MODULES[$this->NAME]) && in_array($method,$this->CONF['data']['editors']))
            try{
                $this->callback($method,array());
            }
            catch (Exception $e){
                $redirect = true;
            }
        
        if($redirect)
            $this->redirect_to('/livewhale/'.$this->CONF['data']['link']);
    }
    
    
    /**
     * Register a subnav link to the parent tab should be called from child classes in the onload
     */
    protected function registerSubNav($parent,$link){
        
        return;
    }
    
    //Util methods
    
       /*
    * validate fields accepts an array of validations and an array of fields
    * use type => num, bool, string for simple validations sting matches anything and could be combined with regex or regexsub
    * regex should match whole string
    * regexsub should match a substring... can be used to look for an @ or something
    * trim => true/false, default is true... should the field be trimmed of whitespace
    * minlen => int default 1 we assume if it's set it should have a value
    * maxlen => int default none
    * 
    */

   protected function validate_fields($ruleset,$fields){
        $sets = array();
        $errors = array();
        
        //validate fields and add to sets
        foreach($ruleset as $field => $rules){
            
            //skip sets type => 'set'
            if($this->viable('type',$rules) && $rules['type'] == 'set'){
                continue;
            }
            
            //add field to a set
            if($this->viable('set',$rules)){
                $sets[$rules['set']][]=$field;
            }
            
            //required and viable checks
            if($rules['require'] && !$this->viable($field,$fields)){
                $errors[$field] = 'Field is required';
                continue;
            }
            elseif(!$this->viable($field))//not set skip
                continue;
            
            //trim whitespace
            if($this->viable('trim',$rules) && $rules['trim'] == false)
                $fields[$field] = trim($fields[$field]);
            
            //minlen
            if(!$this->viable('minlen',$rules) && $rules['require'])
                $rules['minlen'] = 1;
            else $rules['minlen'] = -1;
            
            if(strlen($fields[$field]) < $rules['minlen']){
                $errors[$field] = "Field must be longer then $rules[minlen] charecters.";
                continue;
            }
            
            //maxlen
            if($this->viable('maxlen',$rules)){
                if(strlen($fields[$field]) > $rules['maxlen']){
                    $errors[$field] = "Field must be less then $rules[maxlen] charecters.";
                    continue;
                    }
            }
            
            //type checks
            if($this->viable('type',$rules)){
               if($rules['type'] == 'num' && !is_numeric($fields[$field])){
                $errors[$field] = 'Field should be a number';
                continue;
                }
                elseif($rules['type'] == 'bool'){
                    if(!preg_match('/(on|false|true|1|0)/',$fields[$field])){
                        $errors[$field] = "Field is not a valid boolean value";
                        continue;
                    }
                }
            }
            
            if($this->viable('callback',$rules)){
                try{
                    if($error = $this->callback($rules['callback'],array($field=>$fields[$field])))
                        $errors[$field] = $error;
                }
                catch(Exception $e){
                    $errors[$field] = $e->getMessage();
                }
            }
        }

        //validate set rules
        foreach($sets as $setname=>$set){
            if($this->viable($setname,$ruleset))
                $setrules = $ruleset[$setname];
            else continue;
            
            $errorcount = 0;
            foreach($set as $field){
                if(isset($errors[$field]))
                    $errorcount++;
                elseif($this->viable('reqtype',$setrules) && $setrules['reqtype'] == 'or' && array_key_exists($field,$fields) && trim($fields[$field]) == '')
                    $errorcount++;
            }
            if($setrules['require']){
                    if($this->viable('reqtype',$setrules) && $setrules['reqtype'] == 'or' && $errorcount == count($set))
                        $errors[$setname] = 'You must fill in atleast one of these fields';
                    elseif($this->viable('reqtype',$setrules) && $setrules['reqtype'] == 'and' && $errorcount)
                        $errors[$setname] = 'You must fill in all of these fields';
                }
                
            if($this->viable('callback',$setrules)){
                $params = array($setname=>'set');
                foreach($set as $field)
                    $params[$field] = $fields[$field];
                
                try{
                    if($error = $this->callback($rules['callback'],$params))
                        $errors[$field] = $error;
                }
                catch(Exception $e){
                    $errors[$field] = $e->getMessage();
                }
            }
        }
        
    return array('errors'=>$errors,'params'=>$fields);
    }
    
    protected function scrub_sql($params){
        $char = '\\';
        foreach($params as $key => $val){
            $params[$key] = mysql_real_escape_string($val);
            $params[$key] = ereg_replace('[%]', $char . '\0', $val);
        }
        return $params;
    }
    
    /*
     * checks an array for an existing element defaults to $_REQUEST array
     */
    protected function viable($var,$request=''){
        if($request == '')
            $request = $_REQUEST;
        if(isset($request[$var]) && $request[$var])
            return true;
        else return false;
    }
    
    /* calls an existing callback method or throws a missing method exception
     * returns the results of the callback
     */
    protected function callback($method,$params=array()){
        //should check a valid callback list to secure this better
        if(method_exists($this, $method)){
            // send callback the field => value array
            if(count($params))
                return call_user_func(array($this,$method),$params);
            else return call_user_func(array($this,$method));
        }
        else throw new Exception('Callback: Missing Method');
    }
}