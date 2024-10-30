 <!-- Create a header in the default WordPress 'wrap' container -->
<div class="wrap">
<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
<?php settings_errors(); ?>

        <?php
            if( isset( $_GET[ 'tab' ] ) ) {
                $active_tab = $_GET[ 'tab' ];
            } else {
                
                $active_tab = 'email_queue';
                
            }
            
            $this->print_nav_tab_wrapper($active_tab);
        
        ?>
<form name="lh_email_queue-backend_form" method="post" action="">
<?php wp_nonce_field( parent::return_namespace()."-nonce", parent::return_namespace()."-nonce", false ); ?>
<?php submit_button('Save'); ?>
</form>
</div>