<?php
/*
 * Plugin Name: biogena-data
 */

class biogenaData
{
  private static $results=array();
  public static function data($index=null,$post_type='aree-terapeutiche'){
    $data=get_transient( 'biogena_data');
     if (empty($data)) {
        self::create();        
     }else{
      self::$results=$data;      
     }
    $count=count(self::$results);
    if(!is_null($index)){
      if(!is_numeric($index)){
                      $connected2 = get_posts( array(
                'connected_type' => 'linee_to_prodotti',
                'connected_items' => $index,
                'nopaging' => true
              ));

            if(!empty($connected2)){$linea=$connected2[0];$title=$linea->post_title;}
        foreach ( self::$results as $key=>$obj ){          
          if($post_type !=='aree-terapeutiche'){              
              if (isset($obj->linea_title) && $obj->linea_title===$title){
                  return $linea;
              }
        }else{
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
  private function create()
    {
                    $args = array(
        'posts_per_page'   => -1,
        'orderby'          => 'title',
        'order'            => 'ASC',
        'post_type'        => 'aree-terapeutiche',
      );
      $posts_array = get_posts( $args );
                                            // if posts
      foreach ( $posts_array as $key=>$patologia ){
        $resp= new stdClass();
        $conn_arr=array();
        $connected = new WP_Query( array(
          'connected_type' => 'patologie_to_linee',
          'connected_items' => $patologia,
          'nopaging' => true
        ));
        if ( $connected->have_posts() ){
        // if more than one linea
          $linea=$connected->posts[0];
          $titolo_linea=$linea->post_title;          
          $titolo_content=$linea->post_content;
          $titolo_perma=get_permalink ( $linea->ID );
          $titolo_thumb=get_the_post_thumbnail ( $linea->ID );
          $connected2 = get_posts( array(
            'connected_type' => 'linee_to_prodotti',
            'connected_items' => $linea,
            'nopaging' => true
          ));
          if ( count($connected2)>0 ){
            foreach ( $connected2 as $key2=>$prodotto ){
              $conn=new stdClass();
              $conn->title=$prodotto->post_title;
              $conn->permalink = get_post_permalink($prodotto->ID);
              $conn->thumb=get_the_post_thumbnail($prodotto->ID,'full');
              $conn_arr[]=$conn;
            }
          }
        }
      $resp->title = $patologia->post_title;
      $resp->permalink = get_post_permalink($patologia->ID);
      $resp->thumb=get_the_post_thumbnail(  $patologia->ID,'full');
      $resp->linea_title=$titolo_linea;
      $resp->linea_content=$titolo_content;
      $resp->linea_thumb=$titolo_thumb;
      $resp->linea_plink=$titolo_perma;
      $prevenzione=get_post_meta(  $patologia->ID,'prevenzione',true);
      $bits = explode("\n", $prevenzione);
      $newstring = "<ul>";
      foreach($bits as $bit)
      {
        $newstring .= "<li><span>" . $bit . "</span></li>";
      }
      $newstring .= "</ul>";
      $resp->prevenzione= $newstring;
      $resp->conn_arr=$conn_arr;
      $resp->content=$patologia->post_content;
      self::$results[]=$resp;
    };
    wp_reset_postdata();
        set_transient( 'biogena_data', self::$results, 60 * 60 * 24 );

    
  }
  
  
 
 
}

