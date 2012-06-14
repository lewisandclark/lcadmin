<?php

/* Usage
 * $this->module_path should be set to the base path of your LW module. It defaults to lcadmin
 * Child classes must implement protected configure()
 * Child classes must register the onLoad() and onSession LW handlers in addition to their own.
 */

require_once($_LW->INCLUDES_DIR_PATH.'/client/modules/lcadmin/includes/class.admin1.php');
class LCAdmin { //implements the base LiveWhaleDataModule api.

    protected $CONF; //should be a ref to the primary $_LW object;
    protected $NAME = 'lcadmin';
    
    //shared properties
    protected static $LW;
    protected static $admin;
    
    //debug stuff
    protected $trace_init = true;
    protected $type = 'generic';
    
    /**
     * Handler methods
     */
    public function onLoad() {
        //bootstrap into livewhale and encapsulate
        global $_LW;
        $this->LW =& $_LW;
        // $this->CONF is setup in childs configure
        //admin helper class
        if (isset($_SESSION['livewhale']) && isset($_SESSION['livewhale']['manage']) && isset($_SESSION['livewhale']['manage']['username']) && !empty($_SESSION['livewhale']['manage']['username'])){
            $username = $_SESSION['livewhale']['manage']['username'];
            $group = $_SESSION['livewhale']['manage']['gid'];
        }
        else {
            $username = '';
            $group = '';
        }
        
        if(isset($_SESSION['livewhale']['manage']['is_admin']) && !empty($_SESSION['livewhale']['manage']['is_admin']))
            $is_admin = $_SESSION['livewhale']['manage']['is_admin'];
        else $is_admin = false;
        
        if(!isset($this->admin)){
            $this->admin = new Admin1($username,$group,$is_admin,$this->LW);
        }
        
        $this->module_path = dirname(__FILE__.'../');
        $this->configure();
        $this->debug();
    }
    
    public function __destruct(){
        if($this->trace_init && $this->is_debug())
            if(isset($this->debug_string)){
                echo $this->debug_string."\n";
            }
    }

    public function onSession($user='',$group=''){
        $this->debug();
        if($user != '')
            $this->admin->set_user($user);
        if($group != '')
            $this->admin->set_group($group);
    }
    
    /** Public provided methods
     */
    public function is_super(){
        return $this->admin->is_super();
    }
    
    public function is_admin(){
        return $this->admin->is_admin();
    }
    
    public function is_debug(){
        if($this->is_super() && !empty($this->LW->_GET['testing']))
            return true;
        return false;
    }
    
    /**
     * LC framework methods
     */
    protected function redirect_to($path){
        die(header("Location: $path"));
    }
    
    protected function ensure_authorized(){
        if(!$this->is_super()){
            die('<h1>You are not authorized to view this page</h1>');
        }
    }
    
    /**
     * OO way to register a tab for this and child classes
     */
    protected function registerTab($link,$pos1,$pos2,$pos3){
        $this->tabs[$this->NAME] = array($link,$pos1,$pos2,$pos3);
    }
    
    protected function registerJs($script){
        if(file_exists($this->module_path.'/scripts'.$script)){
            $this->LW->REGISTERED_JS[] = "/live/resource/js$script";
            return true;
        }
        else return false;
    }
    
    //run debug stuff + tracing
    protected function debug(){
        if($this->trace_init && $this->is_debug()){

            if(!isset($this->debug_string))
                $this->debug_string = '';
            $trace=debug_backtrace();
          
            $caller=$trace[1]['function'];
            
            $this->debug_string .= $caller.'>>';
        }
    }
    
}

?>