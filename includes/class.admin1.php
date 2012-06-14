<?php

class Admin1 {
    
    //list of super admins
    private $super = array(
        'mckelvey',
        'grether',
        'nickshobe',
        'lsiulagi',
        'whitewhale'
        );
    
    private $user,$group,$LW,$admin;
    
    // primary methods
    public function __construct($user,$group,$is_admin,$LW){
        $this->user = $user;
        $this->group = $group;
        $this->admin = $is_admin == '1' ? true : false;
        $this->LW = $LW;
        }

    private $recent = '3 months ago';

    public function get_recent(){
        return $this->recent;
    }
    
    public function get_supers(){
        return $this->super;
    }
    
    public function is_admin($user=''){
        if($this->admin)
            return true;
        return false;
    }

    public function is_super($user=''){
        if(empty($user))
            $user = $this->user;
        if(in_array($user, $this->super))
           return true;
    return false;
  }

    public function set_user($user){
        $this->user = $user;
    }
  
    public function set_group($group){
        $this->group = $group;
    }
  public function users () {
          $users = array();
          $recent = date("Y-m-d H:i:s", strftime($this->recent, time()));
        $query = "SELECT * FROM livewhale_users WHERE livewhale_users.username NOT LIKE '%pseudo%' AND last_login > '{$recent}' GROUP BY email ORDER BY username;";
        if ( $result = $this->LW->query($query) ) {
                if ( $result->num_rows ) {
                        while ( $row = $result->fetch_assoc() ) $users[] = $row;
                }
    }
    return $users;
  }

  public function users_by_group_lead ( $lead = 'Inst: ' ) {
          $users = array();
        $query = "SELECT `livewhale_users`.* FROM `livewhale_users` INNER JOIN `livewhale_groups` ON `livewhale_groups`.`id` = `livewhale_users`.`gid` WHERE `livewhale_groups`.`fullname` LIKE '{$lead}%' GROUP BY email ORDER BY username;";
        if ( $result = $this->LW->query($query) ) {
                if ( $result->num_rows ) {
                        while ( $row = $result->fetch_assoc() ) $users[] = $row;
                }
    }
    return $users;
  }

}

?>