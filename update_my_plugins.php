<?php
/**
 * Plugin Name: WP my Update
 * Description: Plugins to be able to update all other plugins on my private server
 * Author: SBatelier
 * Version: 0.1.0
 */


include_once( 'update_my_plugins.php' );
$update = new Update_My_Plugin(plugin_basename( __FILE__ ));

function myfonction_de_demo(){
  $myvar = "Lorem Ipsum";
  return $myvar;
}
 
