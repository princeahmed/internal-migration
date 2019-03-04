<?php

/**
 * Plugin Name: User Migration
 */

register_activation_hook( __FILE__, function () {

	add_action( 'activated_plugin', function () {
		wp_redirect( add_query_arg( array(
			'action' => 'migration',
			'page'   => 1
		), get_site_url() ) );

		exit();

	} );

} );

function user_migration() {

	if ( ! empty( $_GET['action'] ) && $_GET['action'] == 'migration' ) {

		$page   = empty( $_GET['page'] ) ? 1 : intval( $_GET['page'] );
		$limit  = 10;
		$offset = ( $page - 1 ) * $limit;

		global $wpdb;


		$sql = "SELECT * FROM $wpdb->users LIMIT $limit OFFSET $offset";

		$users = $wpdb->get_results( $sql );

		if ( empty( $users ) ) {
			wp_die( 'Migration Complete' );
		}

		foreach ( $users as $user ) {
			$user_login = sanitize_title( $user->user_nicename );
			$sql        = "UPDATE $wpdb->users SET user_nicename = '$user_login' WHERE ID = $user->ID";
			$wpdb->query( $sql );
		}

		$next_page = add_query_arg( array(
			'action' => 'migration',
			'page'   => $page + 1
		), site_url() );

		?>

		<script>
            window.location = "<?php echo $next_page;?>";
		</script>
		<?php

		exit();
	}

}

add_action( 'wp', 'user_migration' );