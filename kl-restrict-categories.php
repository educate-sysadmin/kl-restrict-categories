<?php
/*
Plugin Name: KL Restrict Categories
Plugin URI: https://github.com/educate-sysadmin/kl-restrict-categories
Description: Restricts editing categories of posts (by role, group and/or user)
Version: 0.1
Author: b.cunningham@ucl.ac.uk
Author URI: https://educate.london
License: GPL2
*/
class KL_Restrict_Categories {

    public static function klrc_check() {
        echo 'klrc_check';
        var_dump(get_current_screen());       
    }
}
add_action( 'current_screen', 'KL_Restrict_Categories::klrc_check' );


