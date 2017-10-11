<?php
/**
 * Plugin Name: Groups Switch User
 * Plugin URI: http://www.netpad.gr
 * Description: User promoter. Once a user gets added to the last out of a list of groups, the user gets removed from the rest of the groups.
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
 * @package groups-switch-user
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'plugins_loaded', 'gsu_plugins_loaded' );

/**
 * Checks plugin dependencies
 */
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
 * If group_id belongs to the defined list
 * and the user_id also belongs to the rest
 * of them, remove the user_id from the rest
 * of the groups.
 *
 * @param int $user_id
 * @param int $group_id
 */
function gsu_groups_created_user_group( $user_id, $group_id ) {
	
	// array of groups to check
	//$groups_list = array( 'Gold', 'Silver', 'Bronze', 'Blue' );
	$groups_list = apply_filters( 'groups_switch_user_groups_list', array() );
	
	if ( is_array( $groups_list ) && count( $groups_list ) > 0 ) {
		if ( get_user_by( 'ID', $user_id ) ) {
			
			require_once ABSPATH . 'wp-includes/pluggable.php';
			if ( $new_group = Groups_Group::read( $group_id ) ) {
				$new_group_name = $new_group->name;
				
				/**
				 * We should first remove the group where
				 * the user_id was just added and check the
				 * rest of the groups in the given list
				 */
				if ( $key = array_search( $new_group_name, $groups_list ) ) {
					unset( $groups_list[$key] );
					$groups_list = array_values( $groups_list );
					
					$groups_count = count( $groups_list );
					for ( $i = 0; $i < $groups_count; $i++ ) {
						
						// if user_id doesn't belong to each one of the
						// groups in the list, then the process should break
						if ( !gsu_check_member( $user_id, $groups_list[$i] ) ) {
							$groups_list[$i] = 0;
						}
					}
					
					// If the user doesn't belong in at least one
					// of the groups, then stop the process
					if ( !in_array( 0, $groups_list, true ) ) {
						foreach ( $groups_list as $group_name ) {
							gsu_remove_member( $user_id, $group_name );
						}
					}
				}
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
