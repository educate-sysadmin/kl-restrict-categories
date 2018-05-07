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

/* Ref: https://wordpress.stackexchange.com/questions/191658/allowing-user-to-edit-only-certain-pages */
/* Ref: http://mawaha.com/allow-user-to-edit-specific-post-using-map_meta_cap/ ? */

class KL_Restrict_Categories {

	/*
	settings:
	include_parent_categories
	*/

	/* 
	Ref: https://codex.wordpress.org/Function_Reference/get_role
	WP_Role Object
	(
    [name] => administrator
    [capabilities] => Array
        (
            [switch_themes] => 1
             ...
    */
    
    /* 
    KLRC => Array
    (
	    [category] => '..',
	    [capability'] => '..' // [ 'edit_post', 'delete_post', 'edit_page', 'delete_page' ]
		// currently only 0 values are implemented i.e. can restrict but not add capabilities	    
		[role] => 0|1, 
    	[group] => 0|1,
    	[user] => 0|1 // user-level not currently implemented
    	)
    )
    */
    
    private static $klrc = array();
        
    public static function klrc_init() {
    	$klrc = array();
    	$klrc[] = array(			
			'category' => 'Test',
			'capability' => '*',
			'role' => 'administrator',
			'value' => 1,
		);
    	$klrc[] = array(					
			'category' => 'Uncategorized',
			'capability' => '*',
			'role' => 'administrator',
			'value' => 1,
		);		
		$klrc[] = array(		
			'category' => 'Test',
			'capability' => '*',
			'role' => 'editor',
			'value' => 1,
		);
		KL_Restrict_Categories::klrc_configure($klrc);
    
    }
    
    public static function klrc_configure($klrc) {   
     
    	KL_Restrict_Categories::$klrc = $klrc;
//		var_dump(KL_Restrict_Categories::$klrc);	
    }    
    
	/* helper to get user roles as array */
	public static function get_user_roles() {
		$roles = array();
		if (!is_user_logged_in()) {
			$roles[] = 'visitor';
		} else {
			$user_object = wp_get_current_user();
			$user = $user_object->user_login;
			foreach ($user_object->roles as $role) {
				$roles[] = $role;
			}
		}
		return $roles;
	}	

	/* update a wp $caps variable */
	public static function update_caps($caps, $capability, $value) {				
		$index = array_search($capability, $caps);		
		if ($value === 0) {
			if ($index) {
				unset($caps[$index]);
			}
		} else if ($value === 1) {
			if (!$index) {
				$caps[] = $capability;
			}
		}
		return $caps;
	}

 	public static function klrc_control($caps, $cap, $user_id, $args) { 		
        //echo '<span style="color:red;">klrc_control</span>';
		if (is_admin()) {
		
			//echo $cap;
			//var_dump($caps);	
			//var_dump($args);
			//echo $args[0]; -> page_id		
			// sample: if ($user_id == 2 && $args[0]==81) { return false;}			
			
			$post_id = $args[0];
			$user_roles = array(); // to populate
			$user_groups = array(); // to populate
			// if not relevant post, return
			//if (!$post_id) { return $caps; }
		
			$capabilities = [ 'edit_post', 'delete_post', 'edit_page', 'delete_page' ];
			//echo "=".$cap;
			// If the capability being filtered isn't of our interest, just return current value			
			if ( ! in_array( $cap, $capabilities, true ) ) {
				return $caps;
			}
					
			// late load $klrc configuration // todo

			$return = true; // default to return current permissions, unless overrides for category 
			/* lookup page or post categories including parents, to plain text */
			$categories = get_the_category_list( ",", 'multiple', $post_id );
			$categories = explode(",",$categories);
			foreach ($categories as $index => $category) {
				$categories[$index] = strip_tags($category);
			}
			if (empty($categories)) {
				$categories = array('Uncategorized');
			}
			foreach ($categories as $category) {
				/* check klrc permissions for each category*/
				$allow_rule_found = false;				
				foreach (KL_Restrict_Categories::$klrc as $index => $klrc) {
					if ($klrc['category'] == '*' || $klrc['category'] == $category) {
						if (!$allow_rule_found) {
							$return = false; // if category limits set, now defaulting to false
						}
						// check roles for each klrc
						if (isset($klrc['role'])) {
							if (empty($user_roles)) {
								$user_roles = KL_Restrict_Categories::get_user_roles($user_id);
							}
							foreach ($user_roles as $user_role) {
								if ($user_role == $klrc['role']) {
									// parse capabilities and values, merge into wp $caps
									$new_capabilities = array();
									if ($klrc['capability'] == '*') { $new_capabilities = $capabilities; }
									foreach ($new_capabilities as $new_capability) {
										if ($cap == $new_capability) { 
											if ($klrc['value'] === 1) {
												// allow as specified
												$allow_rule_found = true;
												$return = true;
												break; break; break; break; // stop processing // not working ???
											} /* else if ($klrc['value'] === 0) {
												// allow as specified
												$return = true; 
											}*/
										}
									}
								}		
							}
						}	
					}			
				}
			}
			
			//else
			if ($return) {
				return $caps;
			}	 else {
				return false;
			}
		}

    }

    public static function klrc_check_1() {
        echo 'klrc_check';
        var_dump(get_current_screen());       
    }
}
//add_action( 'current_screen', 'KL_Restrict_Categories::klrc_check_1' );
// thanks https://wordpress.stackexchange.com/questions/191658/allowing-user-to-edit-only-certain-pages
KL_Restrict_Categories::klrc_init();
add_filter( 'map_meta_cap', 'KL_Restrict_Categories::klrc_control', 10, 4 );

