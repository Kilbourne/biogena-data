<?php 
/*
* Plugin Name: biogena-data
*/

class biogenaData{
  private static $post_types=array('linee','area-skin-care','prodotti');
  private static $results=array();
  private static $results_cache=array();
private static  function get_obj_info($obj,$kp){

    $cache=(isset(self::$results_cache[$obj->ID]) || array_key_exists($obj->ID,self::$results_cache))?self::$results_cache[$obj->ID]:false;
  
  
  if (!$cache){
    //if($kp!==0 ){echo "Non cachato".d($obj).d($kp).d(self::$results_cache[$obj->ID]).d(self::$results_cache).'!!!';}
    $result=array();
    $result["title"]=$obj->post_title;
    $result["permalink"]=get_permalink ( $obj->ID );
    $result["content"]=wpautop($obj->post_content,true);
    $result["thumbnail"]=get_the_post_thumbnail ( $obj->ID );
    $result["fields"]=get_fields($obj->ID);
    self::$results_cache[$obj->ID]=$result;
    if($kp===0 ){
      if(!isset(self::$results_cache['linee'])){self::$results_cache['inverse']=array();}
      self::$results_cache['linee'][$obj->ID]=$obj;
    }
    return $result;
  }else
  {
   return $cache; 
  }
}

private static function get_obj_connected($obj,$conn,$kp,$one=true,$inverse=false){
  $cache=false;
  if(!$inverse && isset(self::$results_cache[$conn])){
          $cache=(isset(self::$results_cache[$conn][$obj->ID]) || array_key_exists($obj->ID,self::$results_cache[$conn]))?self::$results_cache[$conn][$obj->ID]:false;
  }else{
    $temp=array();
    if(isset(self::$results_cache['inverse'][$conn])){
    foreach (self::$results_cache['inverse'][$conn] as $key => $value) {

      $keys = array_search($obj->ID, $value); // $key = 2;
      
      if($keys!==false){
      $temp[]=$key;  
      }
      
    }
  }
    if(count($temp)===1){
        
        $cache=(isset(self::$results_cache['linee'][$temp[0]]) || array_key_exists($temp[0],self::$results_cache['linee']))?self::$results_cache['linee'][$temp[0]]:false;      # code...
        }
    
  }          
  if (!$cache){

        $connected = get_posts( array(
          'connected_type' => $conn,
          'connected_items' => $obj,
          'posts_per_page'=>-1
        ));

        //if($kp!==0){echo "Non cachato".d($obj).d($conn).d($one).d($inverse).d($kp).d($connected).d($results_cache[$conn][$obj->ID]).d($temp).d($keys).d(self::$results_cache[$conn][$temp[0]]).d(self::$results_cache).'!!!';}        
        if($one && count($connected)===1){
          $result=$connected[0];
           self::$results_cache[$conn][$obj->ID]=$result;
           self::$results_cache[$conn][$result->ID]=$obj;
        }else{
          $result=$connected;
          self::$results_cache[$conn][$obj->ID]=$result;
          $id_array=array();
          foreach ($result as $key => $value) {
            $id_array[]=$value->ID;
          }
          if(!isset(self::$results_cache['inverse'])){self::$results_cache['inverse']=array();}
          if(!isset(self::$results_cache['inverse'][$conn])){self::$results_cache['inverse'][$conn]=array();}
          self::$results_cache['inverse'][$conn][$obj->ID]=$id_array;
        }        

        return $result;
      }else{
   return $cache; 
  }
}

  private static function create($post_type){
      $key_pt=array_search($post_type, self::$post_types);      
      self::$results[$post_type]=array();
      $args = array(
        'posts_per_page'   => -1,
        'orderby'          => 'title',
        'order'            => 'ASC',
        'post_type'        => $post_type,
      );
      $posts_array = get_posts( $args );
      foreach ( $posts_array as $key_s =>$subject ){

        $result=self::get_obj_info($subject,$key_pt);
        if($key_pt===1){
          $linea=self::get_obj_connected($subject,'area-skin-care_to_linee',$key_pt);          
          if($linea){
            $result['linea']=self::get_obj_info($linea,$key_pt);
            $prodotti=self::get_obj_connected($linea,'linee_to_prodotti',$key_pt,false);          

            if($prodotti){              
              $result['prodotti']=array();
               foreach ($prodotti as $key_prod => $prodotto) {
                  $result['prodotti'][]=self::get_obj_info($prodotto,$key_pt);
                }

            }
          }
             // if($key_s===count($posts_array)-1)    d(self::$results_cache);
        }
        elseif($key_pt===2){
          $linea=self::get_obj_connected($subject,'linee_to_prodotti',$key_pt,true,true);          
          if($linea){            
            $result['linea']=self::get_obj_info($linea,$key_pt);
            $prodotti=self::get_obj_connected($linea,'linee_to_prodotti',$key_pt,false,false);          

            if($prodotti){
              $result['prodotti']=array();

                            foreach ($prodotti as $key_prod => $prodotto) {
                              
                if($prodotto->post_title!==$result['title']){ 
                  $result['prodotti'][]=self::get_obj_info($prodotto,$key_pt);
                  
                }
              }

              
            }
            
                          if(!isset($result['linea']['fields']['no_area_skin_care']) && $result['linea']['fields']['no_area_skin_care']!==TRUE ){
            $area_terapeutica=self::get_obj_connected($linea,'area-skin-care_to_linee',$key_pt);
            if($area_terapeutica){
              $result['area-skin-care']=self::get_obj_info($area_terapeutica,$key_pt);
            }
            }
          }
          //if($key_s===count($posts_array)-1)      d(self::$results_cache);
        }
        elseif($key_pt===0){
//          if(  $result['title']==='Specialità Medicinali' ) continue;
          $prodotti=self::get_obj_connected($subject,'linee_to_prodotti',$key_pt,false);

            if(!isset($result['fields']['no_area_skin_care']) || $result['fields']['no_area_skin_care']!==TRUE ){

          $area_terapeutica=self::get_obj_connected($subject,'area-skin-care_to_linee',$key_pt);
          if($area_terapeutica){
            $result['area-skin-care']=self::get_obj_info($area_terapeutica,$key_pt);
          }
          }
          if($prodotti){
            $result['prodotti']=array();
              foreach ($prodotti as $key_prod => $prodotto) {
                if($prodotto->post_title!==$result['title']){ 
                $result['prodotti'][]=self::get_obj_info($prodotto,$key_pt);
              }
              }

          }
          //if($key_s===count($posts_array)-1)      d(self::$results_cache);  
        }

        if($result['title']==='Linea Osmin'){
       
          self::$results['area-baby']=array();
          self::$results['area-baby'][$result['title']]=$result;
          set_transient( 'biogena_data_area-baby', self::$results['area-baby'], 60 * 60 * 24 );
       
          
        }else{      
          self::$results[$post_type][$result['title']]=$result;
        }
      }
          set_transient( 'biogena_data_'.$post_type, self::$results[$post_type], 60 * 60 * 24 );    
       
      

  }
  public static function data($post_type=null,$index=null,$tree=false,$by_index=false){
    if($index ==='Linea Osmin'){$data=get_transient( 'biogena_data_area-baby');}else
    {$data=get_transient( 'biogena_data_'.$post_type);}
    
    if (empty($data)) {
         self::create($post_type);                
         $data=self::$results[$post_type];

     }
    if($index===null) {

      return $data;

    }else{
      if( $tree || (!$tree && $by_index  ) )$keys=array_keys($data);      
      if(!$tree){
          if($by_index) {            
            return $data[$keys[$index]];
          }else{
            return $data[$index];
          }
      }else{
        $count=count($data);
        $n_index=$by_index?$index:array_search($index, $keys);        
        $next=($n_index+1)<$count?$n_index+1:0;
        $prev=($n_index-1)>-1?($n_index-1):$count-1;
        $obj=new stdClass();
        $obj->first=$data[$keys[$n_index]];
        $obj->prev=$data[$keys[$prev]];
        $obj->next=$data[$keys[$next]];
        return $obj;
      }
     }
  
    }
}
