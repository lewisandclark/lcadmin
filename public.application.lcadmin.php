<?php
$_LW->REGISTERED_APPS['lcadmin']=array(
	'title'=>'LC Admin App',
  'application'=>array('loader'=>array('www.lclark.edu'=>array('/green/'))),
	'handlers'=>array('onLoad','onLaunch','onSession','onManager'),
  'custom' => array('register_methods'=>array('is_super','is_debug','is_admin'))
  );

require_once($_LW->INCLUDES_DIR_PATH.'/client/modules/lcadmin/includes/class.application.lcadmin.php');
class LiveWhaleApplicationLcadmin extends AppLCAdmin{

   protected $NAME = 'lcadmin';
   
    public function onLoad(){
		if (isset($_GET['check_lcadmin'])) echo 'LCADMIN';
        parent::onLoad();
    }
    
    public function onLaunch(){
        //echo 'test2'; die;
    }
}

?>