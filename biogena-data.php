<?php 
/*
* Plugin Name: biogena-data
*/

class biogenaData{
  private static $post_types=array('area-skin-care','linee','prodotti');
  private static $results=array();
  private static $results_cache=array();
private static  function get_obj_info($obj){
  
  $cache=(isset(self::$results_cache[$obj->ID]) || array_key_exists($obj->ID,self::$results_cache))?self::$results_cache[$obj->ID]:false;
  
  if (!$cache){
    $result=array();
    $result["title"]=$obj->post_title;
    $result["permalink"]=get_permalink ( $obj->ID );
    $result["content"]=$obj->post_content;
    $result["thumbnail"]=get_the_post_thumbnail ( $obj->ID );
    $result["fields"]=get_fields($obj->ID);
    self::$results_cache[$obj->ID]=$result;
    return $result;
  }else
  {
   return $cache; 
  }
}

private static function get_obj_connected($obj,$conn){
  
          $cache=(isset(self::$results_cache['c_'.$obj->ID.'_c_'.$conn]) || array_key_exists('c_'.$obj->ID.'_c_'.$conn,self::$results_cache))?self::$results_cache['c_'.$obj->ID.'_c_'.$conn]:false;
  if (!$cache){
        $connected = get_posts( array(
          'connected_type' => $conn,
          'connected_items' => $obj
        ));
        $result=count($connected)===1?$connected[0]:$connected;
        self::$results_cache['c_'.$obj->ID.'_c_'.$conn]=$result;
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

        $result=self::get_obj_info($subject);
        if($key_pt===0){
          $linea=self::get_obj_connected($subject,'area-skin-care_to_linee');
          if($linea){
            $result['linea']=self::get_obj_info($linea);
            $prodotti=self::get_obj_connected($linea,'linee_to_prodotti');
            if($prodotti){
              $result['prodotti']=array();
              foreach ($prodotti as $key_prod => $prodotto) {
                $result['prodotti'][]=self::get_obj_info($prodotto);
              }
            }
          }
        }
        elseif($key_pt===2){
          $linea=self::get_obj_connected($subject,'linee_to_prodotti');
          if($linea){
            $result['linea']=self::get_obj_info($linea);
            $prodotti=self::get_obj_connected($linea,'linee_to_prodotti');
            if($prodotti){
              $result['prodotti']=array();
              foreach ($prodotti as $key_prod => $prodotto) {
                if($prodotto->post_title!==$result['title']){ 
                  $result['prodotti'][]=self::get_obj_info($prodotto);
                }
              }
            }
            $area_terapeutica=self::get_obj_connected($linea,'area-skin-care_to_linee');
            if($area_terapeutica){
              $result['area-skin-care']=self::get_obj_info($area_terapeutica);
            }
          }
        }
        elseif($key_pt===1){
          $prodotti=self::get_obj_connected($subject,'linee_to_prodotti');
          $area_terapeutica=self::get_obj_connected($subject,'area-skin-care_to_linee');
          if($area_terapeutica){
            $result['area-skin-care']=self::get_obj_info($area_terapeutica);
          }
          if($prodotti){
            $result['prodotti']=array();
            foreach ($prodotti as $key_prod => $prodotto) {
              $result['prodotti'][]=self::get_obj_info($prodotto);
            }
          }
        }
        self::$results[$post_type][$result['title']]=$result;
        
      }
      set_transient( 'biogena_data_'.$post_type, self::$results[$post_type], 60 * 60 * 24 );

  }
  public static function data($post_type=null,$index=null,$tree=false,$by_index=false){
    $data=get_transient( 'biogena_data_'.$post_type);
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
