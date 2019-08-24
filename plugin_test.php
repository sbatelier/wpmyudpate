<?php
// Class Update_My_Plugin

if ( ! class_exists( "Update_My_Plugin", false ) ) {
  class Update_My_Plugin {
    protected $name = 'Update_My_Plugin';
    protected $update_server = array(
                    "plugin_update_url"   =>"http://www.xxxxxx.com/server_update.php"
                  );
    protected $error_message = '';
    protected $update_response = null;
    private $response_transient_key = '';
    private $request_failed_transient_key = '';
    public $cached = true;
    public $plugin = null;

      // Constructor
    public function __construct($plugin_basename="") {
      $this->plugin =  new StdClass;
      if (file_exists(WP_PLUGIN_DIR."/".$plugin_basename)) {
          $info = get_file_data( WP_PLUGIN_DIR."/".$plugin_basename  ,array(
                                                                            'Name'        => 'Plugin Name',
                                                                            'PluginURI'   => 'Plugin URI',
                                                                            'Description' => 'Description',
                                                                            'Author'      => 'Author',
                                                                            'AuthorURI'   => 'Author URI',
                                                                            'Version'     => 'Version',
                                                                            'DomainPath'  => 'Domain Path',
                                                                            'Network'     => 'Network',
                                                                          ));
        if (!empty($info)) {
          $this->plugin->info = $info;
        }else{
          $this->show_update_error("Erreur lors de l'attribution de la variable info du plugin (".__FILE__.")");
          return;
        }

      }else{
        $this->show_update_error("Erreur lors de la recuperation du fichier d'info du plugin (".__FILE__.")");
        return;
      }

      if (empty($this->plugin->info)) {
        $this->show_update_error("Attention aucune <b>Information</b> pour ce plugin dans le constructeur (".__FILE__.")");
        return;
      }

      if (empty($plugin_basename)) {
        $this->show_update_error("Attention aucun <b>Plugin Basename</b> pour ce plugin dans le constructeur (".__FILE__.")");
        return;
      }

      if (empty($this->plugin->info["Version"])) {
        $this->show_update_error("Attention aucun <b>Numero de version</b> pour ce plugin dans le constructeur (".__FILE__.")");
        return;
      }

      if (empty($plugin_prefix)) {
        $this->plugin->prefix = substr(md5($plugin_basename), 0, 12)."_";
      }
      $this->plugin->basename = $plugin_basename;

      list ($this->plugin->directory, $this->plugin->filename) = explode( '/', $this->plugin->basename );
      $this->plugin->slug = str_replace( '.php', '', $this->plugin->filename );

      // generate transient names
      $this->response_transient_key       = $this->plugin->prefix  . '_update_response';
      $this->request_failed_transient_key = $this->plugin->prefix  . '_update_request_failed';

      // clear_transients
      $this->clear_transients();
      add_filter('pre_set_site_transient_update_plugins', array( $this, 'set_updates_available_data' ));
    }

      // clear_transients
    private function clear_transients() {
      global $pagenow;
      if ( $pagenow === 'update-core.php' && isset( $_GET['force-check'] ) ) {
        delete_transient( $this->response_transient_key );
        delete_transient( $this->request_failed_transient_key );
      }
    }
   
      // set_updates_available_data
      public function set_updates_available_data( $data ) {

        if ( empty( $data ) ) {
          return $data;
        }

        $remote_data = $this->get_remote_data();  // send of API request to check for updates
        if( $remote_data === false ) { // did we get a response?
          return $data;
        }
       
        if ( version_compare( $this->plugin->info["Version"], $remote_data->new_version, '<' ) ) {// compare local version with remote version
          $data->response[ $this->plugin->basename ] = $remote_data; // remote version is newer, add to data
        }
        return $data;
      }

      // show_update_error
    public function show_update_error($msg) {
      if ( $msg === '' ) {
        return;
      }
      echo '<div class="error"><p><b>'.$this->name . '</b> : '.$msg.'</p></div>';
    }

      // call_remote_api
    private function call_remote_api() {
      global $wp_version;

      if ( get_transient( $this->request_failed_transient_key ) !== false ) {
        return false;
      }

      set_transient( $this->request_failed_transient_key, 'failed', 10800 );

      $request = wp_remote_get( $this->update_server["plugin_update_url"]."?slug=".$this->plugin->slug, array( 'timeout' => 5));
      if ( !is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) === 200 ) {// Check if response is valid
        $response = json_decode($request['body']);
      }else{// show error message
        $this->error_message = $request->get_error_message();
        add_action( 'admin_notices', array( $this, 'show_update_error' ) );
        return false;  
      }

      // request succeeded, delete transient indicating a request failed
      delete_transient( $this->request_failed_transient_key );
      // Generate reponse transient object
      $transient_obj = new stdClass();
      $transient_obj->slug = $this->plugin->slug;
      $transient_obj->new_version = $response->new_version;
      $transient_obj->url = $response->url;
      $transient_obj->plugin = $this->plugin->basename;
      $transient_obj->package = $response->package;
      //$transient_obj->tested = $response->tested;
      // // store response
      set_transient( $this->response_transient_key, $transient_obj, 10800 );

      return $transient_obj;
    }

   
      // get_remote_data
    protected function get_remote_data() {
      if ( null !== $this->update_response ) { // always use property if it's set
        return $this->update_response;
      }

      if ($this->cached == true) { // get cached remote data
        $data = $this->get_cached_remote_data();
      }

      if (empty($data)) { // if cache is empty or expired, call remote api
        $data = $this->call_remote_api();
      }

      $this->update_response = $data;
      return $data;
    }

      // get_cached_remote_data
    private function get_cached_remote_data() {
      $data = get_transient( $this->response_transient_key );
      if ( $data ) {
        return $data;
      }
      return false;
    }
  }
}
