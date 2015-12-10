<?php 
class biogenaData{
  private static $results=array();
  private static function all_data($post_type){
    $data=get_transient( 'biogena_data_'.$post_type);
    if (empty($data)) {
        return self::create($post_type);        
     }else{
        return self::$results=$data;      
     }
  }
  private static function create($post_type){
      self::$results=array();
      $args = array(
        'posts_per_page'   => -1,
        'orderby'          => 'title',
        'order'            => 'ASC',
        'post_type'        => $post_type,
      );
      $posts_array = get_posts( $args );
      foreach ( $posts_array as $key=>$obj ){
        $resp= new stdClass();
        $conn_arr=array();
        if($post_type!=='prodotti'){$connection='patologie_to_linee';}
        else if($post_type!=='aree-terapeutiche'){$connection='linee_to_prodotti';}
        $connected = new WP_Query( array(
          'connected_type' => $connection,
          'connected_items' => $obj,
          'nopaging' => true
        ));
        if ( $connected->have_posts() ){
          $right_obj=$connected->posts[0];
          $titolo=$right_obj->post_title;          
          $content=$right_obj->post_content;
          $perma=get_permalink ( $right_obj->ID );
          $thumb=get_the_post_thumbnail ( $right_obj->ID );
          if($post_type!=='linee'){
            if($post_type!=='prodotti'){$connection2='linee_to_prodotti';}
            else if($post_type!=='aree-terapeutiche'){$connection2='patologie_to_linee';}
            $connected2 = get_posts( array(
              'connected_type' => $connection2,
              'connected_items' => $right_obj,
              'nopaging' => true
            ));
            if ( count($connected2)>0 ){
              foreach ( $connected2 as $key2=>$right_obj2 ){
                $conn=new stdClass();
                $conn->title=$right_obj2->post_title;
                $conn->permalink = get_post_permalink($right_obj2->ID);
                $conn->thumb=get_the_post_thumbnail($right_obj2->ID,'full');
                $conn_arr[]=$conn;
              }
            }
          }else{
            $connected2 = new WP_Query( array(
              'connected_type' => 'linee_to_prodotti',
              'connected_items' => $obj,
              'nopaging' => true
            ));
            if ( count($connected2->posts)>0 ){
              foreach ( $connected2->posts as $key2=>$right_obj2 ){
                // echo var_dump($right_obj2);
                $conn=new stdClass();
                $conn->title=$right_obj2->post_title;
                $conn->permalink = get_post_permalink($right_obj2->ID);
                $conn->thumb=get_the_post_thumbnail($right_obj2->ID,'full');
                $conn_arr[]=$conn;
              }
            }
          }
        }        
      $resp->title = $obj->post_title;
      $resp->permalink = get_post_permalink($obj->ID);
      $resp->thumb=get_the_post_thumbnail(  $obj->ID,'full');
      $resp->right_obj_title=$titolo;
      $resp->right_obj_content=$content;
      $resp->right_obj_thumb=$thumb;
      $resp->right_obj_plink=$perma;
      /*
      $prevenzione=get_post_meta(  $obj->ID,'prevenzione',true);
      $bits = explode("\n", $prevenzione);
      $newstring = "<ul>";
      foreach($bits as $bit)
      {
        $newstring .= "<li><span>" . $bit . "</span></li>";
      }
      $newstring .= "</ul>";
      $resp->prevenzione= $newstring;
      */
      if($post_type==='aree-terapeutiche'){ $resp->prevenzione= get_post_meta(  $obj->ID,'prevenzione',true);}
      $resp->conn_arr=$conn_arr;
      $resp->content=$obj->post_content;
      self::$results[]=$resp;
    };
    wp_reset_postdata();
    set_transient( 'biogena_data_'.$post_type, self::$results, 60 * 60 * 24 );
  }
  public static function data($index=null,$post_type='aree-terapeutiche'){
    self::all_data($post_type);
    $count=count(self::$results);
    if(!is_null($index)){
      if(!is_numeric($index)){
        if(!is_string ($index)){
          $connected2 = get_posts( array(
                'connected_type' => 'linee_to_prodotti',
                'connected_items' => $index,
                'nopaging' => true
          ));
          if(!empty($connected2)){$linea=$connected2[0];$title=$linea->post_title;}
          foreach ( self::$results as $key=>$obj ){          
          
              if (isset($obj->linea_title) && $obj->linea_title===$title){
                  return $linea;
              }
          }
      }else{
         foreach ( self::$results as $key=>$obj ){    

              if (isset($obj->permalink) && $obj->permalink===$index) {
                $index=$key;
                break;
              }
          }
        }          
        
      }

    $next=($index+1)<$count?$index+1:0;
    $prev=($index-1)>-1?($index-1):$count-1;
    $results=new stdClass();
    $results->first=self::$results[$index];
    $results->next=self::$results[$next];
    $results->prev=self::$results[$prev];
    }else{
      $results=self::$results;
    }
    return $results;
  }
}
