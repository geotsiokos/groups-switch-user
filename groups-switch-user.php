<?php
/**
 * Plugin Name: Groups Switch User
 * Plugin URI: http://www.netpad.gr
 * Description: Handles three groups. When the user becomes member to one group, removes the user from the two other groups.
 * Version: 1.0.0
 * Author: George Tsiokos
 * Author URI: http://www.netpad.gr
 * License: GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Copyright (c) 2015-2016 "gtsiokos" George Tsiokos www.netpad.gr
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'plugins_loaded', 'gsu_plugins_loaded' );
function gsu_plugins_loaded() {
	$active_plugins = get_option( 'active_plugins', array() );
	if ( in_array( 'groups/groups.php', $active_plugins ) ) {
		add_action( 'groups_created_user_group', 'gsu_groups_created_user_group', 10, 2 );
	} else {
		echo '<div class="error"><strong>Groups Switch User</strong> requires <a href="https://wordpress.org/plugins/groups/" target="_blank">Groups</a> plugin to be installed and activated.</div>';
	}
}

/**
 * Handles the user-group switch
 *
 * @param int $user_id
 * @param int $group_id
 */
function gsu_groups_created_user_group( $user_id, $group_id ) {
	
	// first group
	$first_group_name  = 'Gold';
	
	// second group
	$second_group_name = 'Silver';
	
	// third group
	$third_group_name  = 'Bronze';	

	if ( get_user_by( 'ID', $user_id ) ) {
		require_once( ABSPATH . 'wp-includes/pluggable.php' );
		if( $new_group = Groups_Group::read( $group_id ) ) {
			$new_group_name = $new_group->name;
			
			// check on which group was the user added and remove the user from the other two
			switch ( $new_group_name ) { 
				
				case $first_group_name : // remove user from second and third group
					if ( 
						gsu_check_member( $user_id, $second_group_name ) &&
						gsu_check_member( $user_id, $third_group_name ) 
					) {
						gsu_remove_member( $user_id, $second_group_name );
						gsu_remove_member( $user_id, $third_group_name );
					}
					break;

				case $second_group_name : // remove user from first and third group
					if (
						gsu_check_member( $user_id, $first_group_name ) &&
						gsu_check_member( $user_id, $third_group_name )
					) {
						gsu_remove_member( $user_id, $first_group_name );
						gsu_remove_member( $user_id, $third_group_name );
					}
					break;

				case $third_group_name : // remove user from first and second group
					if (
						gsu_check_member( $user_id, $first_group_name ) &&
						gsu_check_member( $user_id, $first_group_name )
					) {
						gsu_remove_member( $user_id, $first_group_name );
						gsu_remove_member( $user_id, $second_group_name );
					}
					break;
				
				default:
					break;
			}
		}
	}
}

/**
 * Check if user_id belongs to group_name
 *
 * @param int $user_id
 * @param string $group_name
 * @return boolean
 */
function gsu_check_member( $user_id, $group_name ) {
	if ( $group = Groups_Group::read_by_name( $group_name ) ) {
		$result = Groups_User_Group::read( $user_id , $group->group_id );
	}
	return $result ? true : false;
}

/**
 * Remove user_id from group_name
 *
 * @param int $user_id
 * @param string $group_name
 * @return boolean
 */
function gsu_remove_member( $user_id, $group_name ) {
	if ( $group = Groups_Group::read_by_name( $group_name ) ) {
		$result = Groups_User_Group::delete( $user_id, $group->group_id );
	}
	return $result;
}
