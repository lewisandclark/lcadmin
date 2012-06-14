<?php
/* See notes for parant class
 *
 */
require_once($_LW->INCLUDES_DIR_PATH.'/client/modules/lcadmin/includes/class.lcadmin.php');
class AppLCAdmin extends LCAdmin{
    
    protected function configure(){
        $this->CONF = $this->LW->REGISTERED_APPS[$this->NAME]; //copy of config
        $this->type = 'application';
        $this->registerMethods();
    }
    
    public function onLaunch(){
        $this->debug();
    }
    
    public function onLoad(){
        parent::onLoad();
    }
    
    /**
     * Method should be called in the onLoad() before onLaunch and other methods should attempt to use them.
     */
    protected function registerMethods($method_list=array()){
        if(!empty($method_list)){
            if(!isset($this->CONF['custom']['register_methods']))
                $this->CONF['custom']['register_methods'] = $method_list;
            else
                array_merge($this->CONF['custom']['register_methods'], $method_list);
        }
        
        if(isset($this->CONF['custom']['register_methods']))
            foreach($this->CONF['custom']['register_methods'] as $m)
                $this->LW->REGISTERED_METHODS[$m]=$this;
    }
}
?>