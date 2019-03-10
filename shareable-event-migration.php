<?php

/**
 * Plugin Name: Shareable Event Migration
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

	global $wpdb;

	if ( ! empty( $_GET['action'] ) && $_GET['action'] == 'migration' ) {

		$post_type = 'event_calendar';

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

			$address   = get_post_meta( $post->ID, 'wpcf-address', true );
			$url       = get_post_meta( $post->ID, 'wpcf-field_event_url_url', true );
			$url_title = get_post_meta( $post->ID, 'wpcf-field_event_url_title', true );
			$email     = get_post_meta( $post->ID, 'wpcf-email', true );
			$promote   = get_post_meta( $post->ID, 'wpcf-promote_this_event', true );
			$dates     = get_post_meta( $post->ID, 'wpcf-event_calendar_date', false );
			$status    = array_pop( wp_get_post_terms( $post->ID, 'event_calendar_status' ) );

			$post_id = wp_update_post( array( 'ID' => $post->ID, 'post_type' => 'pro_event' ) );

			if ( ! empty( $address ) ) {
				update_post_meta( $post_id, '_address', sanitize_textarea_field( $address ) );
			}

			if ( ! empty( $url ) ) {
				update_post_meta( $post_id, '_url', esc_url( $url ) );
			}

			if ( ! empty( $url_title ) ) {
				update_post_meta( $post_id, '_url_title', sanitize_textarea_field( $url_title ) );
			}

			if ( ! empty( $email ) ) {
				update_post_meta( $post_id, '_email', sanitize_email( $email ) );
			}

			if ( ! empty( $promote ) ) {
				update_post_meta( $post_id, '_promote', sanitize_email( $promote ) );
			}

			if ( ! empty( $status ) ) {
				update_post_meta( $post_id, '_status', sanitize_key( $status->name ) );
			}

			if ( ! empty( $dates ) ) {

				$values = [];

				if ( $dates ) {
					$start_date = date( 'Y-m-d', $dates[0] );
					$start_time = date( 'h:i', $dates[0] );
					$end_date   = date( 'Y-m-d', $dates[1] );
					$end_time   = date( 'h:i', $dates[1] );

					update_post_meta( $post_id, 'startdate', $start_date );
					update_post_meta( $post_id, 'starttime', $start_time );
					update_post_meta( $post_id, 'enddate', $end_date );
					update_post_meta( $post_id, 'endtime', $end_time );

					$values[] = $wpdb->prepare( '(%d, %s, %s, %s, %s)', $post->ID, $start_date, $end_date, $start_time, $end_time );
				}


				$values = array_unique( $values );

				$sql = "INSERT INTO {$wpdb->prefix}ecp_events (post_id, start_date, end_date, start_time, end_time) values " . implode( ', ', $values );

				$wpdb->query( $sql );

			}

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