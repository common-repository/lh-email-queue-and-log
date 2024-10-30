<?php
/**
 * Plugin Name: LH Email Queue and Log
 * Plugin URI: http://lhero.org/plugins/lh-email-queue-log/
 * Description: Creates an email queue for wordpress
 * Author: Peter Shaw
 * Author URI: http://shawfactor.com
 * Version: 1.01
*/


/*
 * Inspired by and rewritten from Danny Kootens excellent work here
 * https://github.com/dannyvankooten/wp-mail-in-background
 * Codebase has now diverged substantially
 * Copyright 2017  Peter Shaw  (email : pete@localhero.biz)
 *
 * Released under the GPL license
 */

if (!class_exists('LH_Email_queue_plugin')) {

class LH_Email_queue_plugin {
    
var $filename;


static function maybe_add_recipient_name($queued){
    
		 $try = get_user_by( 'ID', $queued->user_id );
		 
		 if (isset($try->user_email) and is_email($try->user_email) and isset($try->display_name) and !empty($try->display_name)){
		     
		  return $try->display_name." <".$try->user_email.">"; 
		     
		     
		 } else {
		     
		     
		 return $queued->wp_mail_to;   
		     
		 }
		      
    
    
    
}
    
public static function return_namespace(){
    
return 'lh_email_queue';
 
}

    
static function get_queue_tablename(){    

global $wpdb;

return $wpdb->prefix."lh_email_queue_log";    
    
}


static function extract_emails_from_string($string){
    
    
    $pattern = '/[a-z0-9_\-\+]+@[a-z0-9\-]+\.([a-z]{2,3})(?:\.[a-z]{2})?/i';
    preg_match_all($pattern, $string, $matches);
    
    $matches = array_unique(array_values($matches));
    
    $return = array();
    
    foreach( $matches as $match ) {
        
        if (is_email(trim($match[0]))){
            
        $return[] = trim($match[0]);    
        }
        
    }
    
 return $return;
 
}
    
    
static function maybe_create_table(){
    
global $wpdb;

$table_name = self::get_queue_tablename(); 
$charset_collate = $wpdb->get_charset_collate();
   
   
$sql = "CREATE TABLE IF NOT EXISTS ".$table_name." (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  user_id bigint(20) unsigned NOT NULL DEFAULT '0',
  time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
  status varchar(20) NOT NULL DEFAULT 'queued',
  wp_mail_to tinytext NOT NULL,
  wp_mail_subject tinytext NOT NULL,
  wp_mail_message longtext NOT NULL,
  wp_mail_headers longtext NULL,
  wp_mail_attachments longtext NULL,
  results longtext NULL,
  PRIMARY KEY (`id`)
) ".$charset_collate;


$result = $wpdb->get_results($sql);

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
dbDelta( $sql );


    
}
    


/**
 * @return array
 */
public static function get_email_queue() {
    
    global $wpdb;
    
    $table_name = self::get_queue_tablename(); 
    
    $sql = "select * from ".$table_name." where status = 'queued' ORDER BY id LIMIT 1";
    
    $results = $wpdb->get_results($sql);
    
if (isset($results[0]->id)){
    
    
	return $results;
	
} else {
    
    return false;
    
}
}



/**
 * @param $id
 *
 * @return bool
 */
static function handle_sent_from_email_queue( $id ) {
  
    global $wpdb;
    
    $table_name = self::get_queue_tablename();  
    
    $sql = "UPDATE ".$table_name." SET status = 'sent' WHERE id = '".$id."'";
    
    $results = $wpdb->get_results($sql);


}



/**
 * @param $args
 *
 * @return bool
 */
static function add_to_email_queue( $args ) {
    
   global $wpdb;
    
$table_name = self::get_queue_tablename(); 


$emails = self::extract_emails_from_string($args["to"]);

if (count($emails) === 1) {
    
$userby = get_user_by('email', $emails[0]);

if (isset($userby)){
    
$user_id = $userby->ID;
    
} else {
    
$user_id = "0"; 
    
    
}

} else {
    
$user_id = "0";    
    
    
}

$wpdb->query( $wpdb->prepare( 
	"INSERT INTO ".$table_name." (user_id, time, wp_mail_to, wp_mail_subject, wp_mail_message, wp_mail_headers, wp_mail_attachments) VALUES (%s, now(), %s, %s, %s, %s, %s )", 
        $user_id, $args["to"], $args["subject"], $args["message"],maybe_serialize($args["headers"]), maybe_serialize($args["attachments"]) ) );
    
}






/**
 * @param $args
 *
 * @return bool
 */
 
static function queue_wp_mail( $args ) {
	self::add_to_email_queue( $args );
	// schedule event to process all queued emails
if( ! wp_next_scheduled( 'lh_email_queue_single' ) ) {
//schedule event to be fired right away
wp_schedule_single_event( time(), 'lh_email_queue_single' );
//send off a request to wp-cron on shutdown
add_action( 'shutdown', 'spawn_cron' );
}
	/**
	 * Return empty `to` and `message` values as this stops the email from being sent
	 *
	 * Once `wp_mail` can be short-circuited using falsey values, we can return false here.
	 *
	 * @see https://core.trac.wordpress.org/ticket/35069
	 */
$args["message"] = "";
$args["to"] = "";

return $args;

}

/**
 * Processes the email queue
 */
static function process_email_queue() {
	// remove filter as we don't want to short circuit ourselves
	remove_filter( "wp_mail", array("LH_Email_queue_plugin","queue_wp_mail") );
	$queue = self::get_email_queue();
	if(isset($queue) and !empty($queue) ) {
		// send each queued email
		foreach( $queue as $queued ) {
		    
		    $mailResult = false;
		    
            $send_to = self::maybe_add_recipient_name($queued);
		    
		$mailResult = wp_mail( $send_to, $queued->wp_mail_subject, $queued->wp_mail_message, maybe_unserialize($queued->wp_mail_headers), maybe_unserialize($queued->wp_mail_attachments) );
		
		if ($mailResult){
			self::handle_sent_from_email_queue($queued->id);
		}
		
		unset($send_to);
		}
		
		//wp_mail( 'shawfactor@gmail.com', 'check this ran', 'test body');
		
		
		//see if anything is still in the queue
		$test = self::get_email_queue();
		
		if (($test) and !wp_next_scheduled( 'lh_email_queue_single' ) ) {

//schedule a new event to be fired asap
wp_schedule_single_event( time(), 'lh_email_queue_single' );

}		    
		    
		    
		} else {
		    
wp_clear_scheduled_hook( 'lh_email_queue_single' );		    
		    
		    
		}
		

	
}


   
    
    


public function on_activate($network_wide) {

    if ( is_multisite() && $network_wide ) { 

        global $wpdb;

foreach ($wpdb->get_col("SELECT blog_id FROM $wpdb->blogs") as $blog_id) {

switch_to_blog($blog_id);
wp_clear_scheduled_hook( 'lh_email_queue_initial_run' );
wp_schedule_single_event(time(), 'lh_email_queue_initial_run');
wp_clear_scheduled_hook( 'lh_email_queue_generate' );
wp_schedule_event( time(), 'lh_email_queue_interval', 'lh_email_queue_generate' );
restore_current_blog();

    
} 

    } else {

wp_clear_scheduled_hook( 'lh_email_queue_initial_run' );
wp_schedule_single_event(time(), 'lh_email_queue_initial_run');
wp_clear_scheduled_hook( 'lh_email_queue_generate' );
wp_schedule_event( time(), 'lh_email_queue_interval', 'lh_email_queue_generate' );

}

}

public function add_interval( $schedules ) {
	// add a 'weekly' schedule to the existing set
	$schedules['lh_email_queue_interval'] = array(
		'interval' => 360,
		'display' => __('Once every 6 minutes')
	);
	return $schedules;
}


public function run_initial_processes(){
    
    self::maybe_create_table();
    
    wp_clear_scheduled_hook( 'lh_email_queue_initial_run' );
    
    
}




public function run_processes(){
    
     self::process_email_queue();
    
}

public function __construct() {
    
$this->filename = plugin_basename( __FILE__ );

//force wp_mail through the queue
add_filter( "wp_mail", array("LH_Email_queue_plugin","queue_wp_mail"));

//add processing to the cron job
add_action( 'lh_email_queue_single', array("LH_Email_queue_plugin", "process_email_queue"));


//Hook to attach new schedules to cron
add_filter( 'cron_schedules', array($this,"add_interval"), 10, 1);


//Hook to attach processes to initial cron job
add_action('lh_email_queue_initial_run', array($this,"run_initial_processes"));

//to attach processes to the ongoing cron job
add_action( 'lh_email_queue_generate', array($this,"run_processes"));



}

}

$lh_email_queue_instance = new LH_Email_queue_plugin();
register_activation_hook(__FILE__, array($lh_email_queue_instance, 'on_activate') , 10, 1);


}

if (!class_exists('LH_Email_queue_menu_class')) {
    
require_once('includes/lh-email-queue-log-menu-class.php');
    
    
    
}



?>