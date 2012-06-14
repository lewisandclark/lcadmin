<?php

class BatchWidgetMaker {
private $tw;
private $log,$quiet;
private $LW;

    function __construct($config='',$LW){
        $this->table = array_key_exists('table',$config) ? $config['table'] : 'livehwale_widgets';
        $this->LW = $LW;
        $this->log = '';
        $this->quiet = true;
    }

    private function log($string){
        $this->log .= $string."\n";
        if(!$this->quiet)
            echo $string."\n";
    }
    
    //encapsulate the LW object a little
    private function query($query){
        return $this->LW->query($query);
    }
    
    public function print_log(){
        echo $this->log;
    }
    
    public function get_log(){
        return $this->log;
    }
    
    public function gen_widgets($tw_id='',$prefix='',$sufix='',$groups,$simulate=true,$update_existing=false){
        $table = 'livewhale_widgets';
        //get template widget
        if(is_numeric($tw_id))
            $where = "id = $tw_id";
        else
            $where = "name = '$tw_id'";
        
        $query = "select * from $table where $where";
        
        $result = $this->query($query);
        
        if($result->num_rows)
            $tw = $result->fetch_assoc();
        
        $tw['args'] = unserialize($tw['args']);
        
        //handle groups
        
        $matches = array();
        if(preg_match('/[,;:]/',$groups,$matches))
            $delim = $matches[0];
        else $delim = ',';
        
        $grouplist = array();
        $group_str = '';
        foreach(explode($delim,$groups) as $idx=>$group){
            $group = trim($group);
            if(is_numeric($group)){
                $group_str .= "id = $group or ";
                $grouplist[] = $group;
            }
        }
        
        if(count($grouplist))
           $where = 'where ' . $group_str . ' 0';
        else $where = '';
        $query = "select id,fullname from livewhale_groups $where;";
        $result = $this->query($query);
        
        while($row = $result->fetch_assoc()){ // loop over each group
            $sufix = strlen($sufix) ? "$sufix " : '';
            $prefix = strlen($prefix) ? "$prefix " : '';
            
            $name = trim($prefix.$row['fullname']." $sufix".ucfirst($tw['type']));

            $widget_name = str_replace(' ','_',strtolower(str_replace(array(',','-',':',';',"'"),'',$name))); //non human readable widget_name_style
            $widget_h_name = str_replace("'","\'",$name); //string based widget name... used for the title of the widget
            $tw['args']['group'] = $row['fullname']; // the serialized widget config
            $widget_config = serialize($tw['args']);
            $qry = "select id,name,type from $table where name = '$widget_name';";
            $existing = $this->query($qry);
            
            $record = $existing->fetch_assoc();
            if(is_array($record)){
                if($simulate){
                    if(!$update_existing)
                        $this->log("Collision for: $record[id], $record[name], $record[type]");
                    else
                        $this->log("Updating entry: $record[id], $record[name], $record[type]");
                }
                elseif($update_existing){
                    $this->log("Updating entry: $record[id], $record[name], $record[type]");
                    $query =  "update $table set gid = $row[id],title = '$widget_h_name',type = '$tw[type]',args = '$widget_config',last_modified = '$tw[last_modified]',last_user = $tw[last_user] where id = $record[id]";
                    $this->query($query);
                }
                else
                    $this->log("Existing entry for: $record[id], $record[name], $record[type]");
            }
            else{
                $query = "INSERT INTO `$table` (gid,title,name,type,args,date_created,last_modified,last_user) VALUES ($row[id],'$widget_h_name','$widget_name','$tw[type]','$widget_config','$tw[date_created]','$tw[last_modified]',$tw[last_user]);\n";
                if($simulate)
                    $this->log($query);
                else{
                    $this->log($query);
                    $this->query($query);
                }
            }
        }
    if($simulate)
        return 'simulate';
    else return 'run';
    }
}
?>