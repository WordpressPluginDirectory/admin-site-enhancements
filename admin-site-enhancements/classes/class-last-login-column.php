<?php

namespace ASENHA\Classes;

/**
 * Class for Last Login Column module
 *
 * @since 6.9.5
 */
class Last_Login_Column {

	/**
	 * Log date time when a user last logged in successfully
	 *
	 * @since 3.6.0
	 */
	public function log_login_datetime( $user_login ) {

		$user = get_user_by( 'login', $user_login ); // by username
		if ( is_object( $user ) ) {
			if ( property_exists( $user, 'ID' ) ) {
				update_user_meta( $user->ID, 'asenha_last_login_on', time() );		
			}
		}

	}

	/**
	 * Add Last Login column to users list table
	 *
	 * @since 3.6.0
	 */
	public function add_last_login_column( $columns ) {

		$columns['asenha_last_login'] = 'Last Login';
		return $columns;

	}

	/**
	 * Show last login info in the last login column
	 *
	 * @since 3.6.0
	 */
	public function show_last_login_info( $output, $column_name, $user_id ) {

		if ( 'asenha_last_login' === $column_name ) {

			if ( ! empty( get_user_meta( $user_id, 'asenha_last_login_on', true ) ) ) {

				$last_login_unixtime = (int) get_user_meta( $user_id, 'asenha_last_login_on', true );

				if ( function_exists( 'wp_date' ) ) {
					$output 	= wp_date( 'M j, Y H:i', $last_login_unixtime );
				} else {
					$output 	= date_i18n( 'M j, Y H:i', $last_login_unixtime );
				}

			} else {

				$output = 'No data yet';

			}

		}

		return $output;

	}

	/**
	 * Add custom CSS for the Last Login column
	 *
	 * @since 3.6.0
	 */
	public function add_column_style() {
		?>
			<style>
				.column-asenha_last_login {
					width: 90px;
				}
			</style>
		<?php
	}
	
}