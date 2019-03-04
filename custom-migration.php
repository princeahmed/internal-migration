<?php

/**
 * Plugin Name: Custom Migration
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

function custom_migration() {

	if ( ! empty( $_GET['action'] ) && $_GET['action'] == 'migration' ) {

		$post_type = 'pro_event';

		$page     = empty( $_GET['page'] ) ? 1 : intval( $_GET['page'] );
		$per_page = 10;
		$offset   = ( $page - 1 ) * $per_page;

		$posts = get_posts(
			array(
				'post_type'   => $post_type,
				'offset'      => $offset,
				'numberposts' => $per_page
			)
		);

		if ( empty( $posts ) ) {
			wp_die( 'Migration Complete' );
		}

		foreach ( $posts as $post ) {
			event_calendar_pro_generate_events( $post->ID );
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

add_action( 'wp', 'custom_migration' );