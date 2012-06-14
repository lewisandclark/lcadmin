<?php
$_LW->REGISTERED_APPS['lcadmin']=array(
	'title'=>'LC Admin App',
  'flags'=>array('has_own_tab','is_admin_only'),
	'handlers'=>array('onLoad','onLaunch','onSession','onManager'),
  'custom' => array('register_methods'=>array('is_super','is_debug','is_admin'))
  );

require_once($_LW->INCLUDES_DIR_PATH.'/client/modules/lcadmin/includes/class.application.lcadmin.php');
class LiveWhaleApplicationLcAdmin extends AppLCAdmin{

   protected $NAME = 'lcadmin';
   
    public function onLoad(){
        parent::onLoad();
        #Custom logging
        if(function_exists('newrelic_name_transaction'))
                newrelic_name_transaction ('Livewhale/Backend');
    }
    
}

?>