<?php
/**
 * Plugin Name:       Single role delete
 * Description:       Single role delete
 * Requires at least: 5.8
 * Requires PHP:      7.0
 * Version:           0.1.0
 * Author:            The WordPress Contributors
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 */

declare( strict_types=1 );

namespace SingleRoleDelete;

/**
 * Register hooks.
 *
 * @return void
 */
function init(): void {
	add_action( 'cli_init', __NAMESPACE__ . '\\register_command' );
}

/**
 * Registers the custom command.
 *
 * @return void
 */
function register_command(): void {
	\WP_CLI::add_command( 'single-role-delete', __NAMESPACE__ . '\\single_role_delete', [
		'shortdesc' => 'Delete users with the specified role only.',
		'synopsis'  => [
			[
				'type'        => 'flag',
				'name'        => 'dry-run',
				'optional'    => true,
				'description' => 'Lists users that should be deleted without deleting them.',
			],
			[
				'type'        => 'assoc',
				'name'        => 'role',
				'optional'    => true,
				'description' => 'Role to search for.',
			],
		],
		'longdesc'  => '## EXAMPLES' . "\n\n" . 'wp single-role-delete --role=administrator --dry-run' . "\n\n" . 'wp single-role-delete',
	] );
}

/**
 * Find all users with specified role, and delete if requested.
 *
 * @param array $positional_args
 * @param array $assoc_args
 *
 * @return void
 */
function single_role_delete( array $positional_args, array $assoc_args ): void {
	$args    = \wp_parse_args(
		$assoc_args,
		[
			'dry-run' => false,
			'role'    => 'subscriber',
		],
	);
	$dry_run = ! empty( $args['dry-run'] );
	\WP_CLI::line( $dry_run === true ? '===Dry Run===' : 'Doing it live!' );
	\WP_CLI::line( 'Starting process' );
	$users_query = new \WP_User_Query( [
		'role' => $args['role'],
	] );
	$users       = $users_query->get_results();
	$count       = 0;
	if ( empty( $users ) ) {
		\WP_CLI::line( 'No users found matching those criteria.' );
	} else {
		foreach ( $users as $user ) {
			// Ignore users with multiple roles.
			if ( count( $user->roles ) > 1 ) {
				continue;
			}
			if ( $dry_run ) {
				\WP_CLI::line( 'User  ' . $user->display_name . ' with role ' . print_r( $user->roles[0], true ) . ' will be deleted.' );
			} else {
				wp_delete_user( $user->id );
				\WP_CLI::line( 'User  ' . $user->display_name . ' with role ' . print_r( $user->roles[0], true ) . ' was deleted.' );
			}
			$count ++;
		}
	}
	\WP_CLI::line( 'Finished process - ' . $count . ' user(s) processed.' );
}

init();
