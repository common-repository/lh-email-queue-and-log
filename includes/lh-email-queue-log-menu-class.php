<?php


if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}



class LH_Email_queue_List_class extends WP_List_Table {
    
    
    
function get_columns(){
        $columns = array(
            'id' => __( 'ID', 'mylisttable' ),
            'time'    => __( 'Time', 'mylisttable' ),
            'status'      => __( 'Status', 'mylisttable' )
        );
         return $columns;
    }
    
    
    
    function get_sortable_columns() {
  $sortable_columns = array();
  return $sortable_columns;
}
    
    
    
function prepare_items() {
  $columns  = $this->get_columns();
  $hidden   = array();
  $sortable = $this->get_sortable_columns();
  $this->_column_headers = array( $columns, $hidden, $sortable );
  
  $per_page = 5;
  $current_page = $this->get_pagenum();
  $total_items = count( $this->example_data );
  
  
  
}
    
    

	/** Class constructor */
	public function __construct() {

		parent::__construct( [
			'singular' => __( 'Email', 'sp' ), //singular name of the listed records
			'plural'   => __( 'Emails', 'sp' ), //plural name of the listed records
			'ajax'     => false //should this table support ajax?

		] );

	}
	
	
	
}





class LH_Email_queue_menu_class extends LH_Email_queue_plugin {
    
    
private function print_nav_tab_wrapper($active_tab){
    
    ?>
    
           <h2 class="nav-tab-wrapper">
            <a href="?page=<?php echo $this->filename;  ?>&tab=email_queue" class="nav-tab <?php echo $active_tab == 'email_queue' ? 'nav-tab-active' : ''; ?>">Queue</a>
            <a href="?page=<?php echo $this->filename;  ?>&tab=email_log" class="nav-tab <?php echo $active_tab == 'email_log' ? 'nav-tab-active' : ''; ?>">Log</a>
        </h2>
        
        <?php
    
    
    

}
    
    
public function plugin_menu() {
add_submenu_page( 'tools.php', 'Email Queue/Log', 'Email Queue/Log', 'manage_options', $this->filename, array($this,"plugin_options") );



}

public function plugin_options() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	
	
	// Now display the tools screen
include ('partials/tools.php');
	
}
    
    
    
public function __construct() {
    
    parent::__construct();
 
 add_action( 'admin_menu', array($this,"plugin_menu"));
    
}
    
    
}

$lh_email_queue_menu_instance = new LH_Email_queue_menu_class();


?>