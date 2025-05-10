<?php
/**
 * Plugin Name: Wilcosky Frontend Username Changer
 * Description: Allows users to change their username from the frontend, with a limit of two changes per user. Admins can change usernames unlimited times via user profiles.
 * Version: 1.0.4
 * Author: Billy Wilcosky
 * Text Domain: wilcosky-username-changer
 */

if ( ! class_exists( 'Wilcosky_Username_Changer' ) ) {
	class Wilcosky_Username_Changer {
		private const MAX_CHANGES           = 2;
		private const META_KEY             = '_wilcosky_username_changes';
		private const REQUEST_META_KEY     = '_wilcosky_username_change_requested';
		private const REQUEST_NEW_USERNAME = '_wilcosky_username_requested_name';

		public function __construct() {
			add_shortcode( 'username_changer', [ $this, 'render_username_change_form' ] );
			add_action( 'init', [ $this, 'handle_form_submission' ] );
			add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
			add_action( 'show_user_profile', [ $this, 'add_admin_username_field' ] );
			add_action( 'edit_user_profile', [ $this, 'add_admin_username_field' ] );
			add_action( 'personal_options_update', [ $this, 'save_admin_username_field' ] );
			add_action( 'edit_user_profile_update', [ $this, 'save_admin_username_field' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
			add_action( 'admin_post_wilcosky_approve_username_request', [ $this, 'handle_admin_approval' ] );
			add_action( 'admin_post_wilcosky_dismiss_username_request', [ $this, 'handle_admin_dismissal' ] );
		}

		public function render_username_change_form(): string {
			if ( ! is_user_logged_in() ) {
				return '<p>' . esc_html__( 'You must be logged in to change your username.', 'wilcosky-username-changer' ) . '</p>';
			}

			$user_id      = get_current_user_id();
			$user         = get_userdata( $user_id );
			$changes      = (int) get_user_meta( $user_id, self::META_KEY, true );
			$request_sent = get_user_meta( $user_id, self::REQUEST_META_KEY, true );
			$output       = '';

			if ( $changes < self::MAX_CHANGES ) {
				$output .= '<form method="post">
					<p>' . esc_html__( 'Your current username: ', 'wilcosky-username-changer' ) . esc_html( $user->user_login ) . '</p>
					<p>' . esc_html__( 'Username changes left: ', 'wilcosky-username-changer' ) . ( self::MAX_CHANGES - $changes ) . '</p>
					<p><input type="text" class="wp-username-change" name="new_username" required></p>
					<p><input type="submit" class="wp-element-button" name="wilcosky_username_change_submit" value="' . esc_attr__( 'Change Username', 'wilcosky-username-changer' ) . '"></p>
				</form>';
			} elseif ( ! $request_sent ) {
				$output .= '<form method="post">
					<p>' . esc_html__( 'You have used all your username changes.', 'wilcosky-username-changer' ) . '</p>
					<p><input type="text" class="wp-username-change" name="requested_username" required></p>
					<p><input type="submit" class="wp-element-button" name="wilcosky_username_change_request" value="' . esc_attr__( 'Request Another Change', 'wilcosky-username-changer' ) . '"></p>
				</form>';
			} else {
				$output .= '<p>' . esc_html__( 'Your request for another username change has been sent.', 'wilcosky-username-changer' ) . '</p>';
			}

			return $output;
		}

		public function handle_form_submission(): void {
			if ( ! is_user_logged_in() ) {
				return;
			}

			$user_id = get_current_user_id();

			// Handle direct change
			if ( isset( $_POST['wilcosky_username_change_submit'], $_POST['new_username'] ) ) {
				$new_username = sanitize_user( wp_unslash( $_POST['new_username'] ) );

				if ( username_exists( $new_username ) ) {
					add_filter( 'the_content', fn( $content ) => '<p>' . esc_html__( 'Username already exists.', 'wilcosky-username-changer' ) . '</p>' . $content );
					return;
				}

				$changes = (int) get_user_meta( $user_id, self::META_KEY, true );

				if ( $changes < self::MAX_CHANGES ) {
					global $wpdb;
					$wpdb->update( $wpdb->users, [ 'user_login' => $new_username ], [ 'ID' => $user_id ] );
					clean_user_cache( $user_id );
					update_user_meta( $user_id, self::META_KEY, $changes + 1 );
					wp_redirect( add_query_arg( 'username_changed', '1', wp_get_referer() ) );
					exit;
				}
			}

			// Handle admin-requested change
			if ( isset( $_POST['wilcosky_username_change_request'], $_POST['requested_username'] ) ) {
				$request = sanitize_user( wp_unslash( $_POST['requested_username'] ) );
				update_user_meta( $user_id, self::REQUEST_META_KEY, true );
				update_user_meta( $user_id, self::REQUEST_NEW_USERNAME, $request );
			}
		}

		public function add_admin_menu(): void {
			add_submenu_page(
				'users.php',
				esc_html__( 'Username Change Requests', 'wilcosky-username-changer' ),
				esc_html__( 'Username Change Requests', 'wilcosky-username-changer' ),
				'edit_users',
				'username-change-requests',
				[ $this, 'render_admin_requests_page' ]
			);
		}

		public function render_admin_requests_page(): void {
			$users = get_users([
				'meta_key'   => self::REQUEST_META_KEY,
				'meta_value' => true,
			]);

			echo '<div class="wrap"><h1>' . esc_html__( 'Username Change Requests', 'wilcosky-username-changer' ) . '</h1>';
			echo '<table class="widefat fixed"><thead><tr>
					<th>' . esc_html__( 'User ID', 'wilcosky-username-changer' ) . '</th>
					<th>' . esc_html__( 'Current Username', 'wilcosky-username-changer' ) . '</th>
					<th>' . esc_html__( 'Requested Username', 'wilcosky-username-changer' ) . '</th>
					<th>' . esc_html__( 'Email', 'wilcosky-username-changer' ) . '</th>
					<th>' . esc_html__( 'Actions', 'wilcosky-username-changer' ) . '</th>
				</tr></thead><tbody>';

			foreach ( $users as $user ) {
				$requested = get_user_meta( $user->ID, self::REQUEST_NEW_USERNAME, true );
				echo '<tr>
					<td>' . esc_html( $user->ID ) . '</td>
					<td>' . esc_html( $user->user_login ) . '</td>
					<td>' . esc_html( $requested ) . '</td>
					<td>' . esc_html( $user->user_email ) . '</td>
					<td>
						<a href="' . esc_url( get_edit_user_link( $user->ID ) ) . '">' . esc_html__( 'Edit Profile', 'wilcosky-username-changer' ) . '</a> |
						<a href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=wilcosky_approve_username_request&user_id=' . $user->ID ), 'approve_username_' . $user->ID ) ) . '">' . esc_html__( 'Approve Request', 'wilcosky-username-changer' ) . '</a> |
						<a href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=wilcosky_dismiss_username_request&user_id=' . $user->ID ), 'dismiss_username_' . $user->ID ) ) . '">' . esc_html__( 'Dismiss Request', 'wilcosky-username-changer' ) . '</a>
					</td>
				</tr>';
			}

			echo '</tbody></table></div>';
		}

		public function handle_admin_approval(): void {
			if ( ! current_user_can( 'edit_users' ) || empty( $_GET['user_id'] ) ) {
				wp_die( __( 'You do not have permission.', 'wilcosky-username-changer' ) );
			}

			$user_id = absint( $_GET['user_id'] );
			check_admin_referer( 'approve_username_' . $user_id );

			$requested = get_user_meta( $user_id, self::REQUEST_NEW_USERNAME, true );
			if ( $requested && ! username_exists( $requested ) ) {
				global $wpdb;
				$wpdb->update( $wpdb->users, [ 'user_login' => $requested ], [ 'ID' => $user_id ] );
				clean_user_cache( $user_id );
			}

			delete_user_meta( $user_id, self::REQUEST_META_KEY );
			delete_user_meta( $user_id, self::REQUEST_NEW_USERNAME );

			wp_redirect( admin_url( 'users.php?page=username-change-requests&approved=1' ) );
			exit;
		}

		public function handle_admin_dismissal(): void {
			if ( ! current_user_can( 'edit_users' ) || empty( $_GET['user_id'] ) ) {
				wp_die( __( 'You do not have permission.', 'wilcosky-username-changer' ) );
			}

			$user_id = absint( $_GET['user_id'] );
			check_admin_referer( 'dismiss_username_' . $user_id );

			delete_user_meta( $user_id, self::REQUEST_META_KEY );
			delete_user_meta( $user_id, self::REQUEST_NEW_USERNAME );

			wp_redirect( admin_url( 'users.php?page=username-change-requests&dismissed=1' ) );
			exit;
		}

		public function add_admin_username_field( $user ): void {
			if ( current_user_can( 'edit_users' ) ) {
				echo '<h2>' . esc_html__( 'Change Username', 'wilcosky-username-changer' ) . '</h2>';
				echo '<table class="form-table"><tr>
						<th><label for="admin_new_username">' . esc_html__( 'New Username', 'wilcosky-username-changer' ) . '</label></th>
						<td><input type="text" name="admin_new_username" id="admin_new_username" value="' . esc_attr( $user->user_login ) . '" class="regular-text" /></td>
					</tr></table>';
				// Hide the core username field
				echo '<script>
					document.addEventListener("DOMContentLoaded", function() {
						const row = document.querySelector("#username").closest("tr");
						if ( row ) row.style.display = "none";
					});
				</script>';
			}
		}

		public function save_admin_username_field( $user_id ): void {
			if ( ! current_user_can( 'edit_users' ) || empty( $_POST['admin_new_username'] ) ) {
				return;
			}

			$new_username = sanitize_user( wp_unslash( $_POST['admin_new_username'] ) );
			if ( ! username_exists( $new_username ) ) {
				global $wpdb;
				$wpdb->update( $wpdb->users, [ 'user_login' => $new_username ], [ 'ID' => $user_id ] );
				clean_user_cache( $user_id );
			}
		}

		/**
		 * (Optional) Enqueue any admin-only scripts or styles.
		 */
		public function enqueue_admin_scripts(): void {
			// e.g. wp_enqueue_style( 'wilcosky-username-changer-admin', plugin_dir_url(__FILE__) . 'assets/admin.css' );
		}
	}

	new Wilcosky_Username_Changer();
}

/**
 * Clean up all plugin data on uninstall.
 */
function wilcosky_username_changer_uninstall(): void {
	if ( ! function_exists( 'get_users' ) ) {
		return;
	}

	$meta_keys = [
		'_wilcosky_username_changes',
		'_wilcosky_username_change_requested',
		'_wilcosky_username_requested_name',
	];

	$user_ids = get_users( [ 'fields' => 'ID' ] );
	foreach ( $user_ids as $user_id ) {
		foreach ( $meta_keys as $key ) {
			delete_user_meta( $user_id, $key );
		}
	}
}
register_uninstall_hook( __FILE__, 'wilcosky_username_changer_uninstall' );