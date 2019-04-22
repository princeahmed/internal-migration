if ( ! empty( $_GET['action'] ) && $_GET['action'] == 'migration' ) {

	//$post_type = 'pro_event';
	$paginate = empty( $_GET['paginate'] ) ? 1 : intval( $_GET['paginate'] );

	var_dump($paginate);

	$per_page = 1;
	$offset   = ( $paginate - 1 ) * $per_page;
	var_dump($paginate);
	$args = array(
		'offset'      => $offset,
		'numberposts' => $per_page,
		'category'    => 'photos-videos',
		'meta_query'  => array(
			'relation' => 'AND',
			array(
				'key'     => 'images',
				'value'   => '',
				'compare' => '!=',
			)
		)
	);


	$posts = get_posts( $args );


	if ( empty( $posts ) ) {
		wp_die( 'Migration Complete' );
	}

	foreach ( $posts as $post ) {
		setup_postdata( $post );

		var_dump($post);

		$images = get_post_meta( $post->ID, 'images', true );

		if ( empty( $images ) ) {
			continue;
		}

		global $gid, $image_ids;

		$image_ids = explode( ',', $images );

		try {
			$gid = nggdb::add_gallery( $post->post_title, 'wp-content\\gallery\\test-gallery/', 'Gallery for ' . $post->post_title, '0', '10', '1' );

			class Set_Image_To_Gallery extends Mixin {

				function import_media_library_action() {

					global $gid, $image_ids;

					$retval         = array();
					$gallery_mapper = C_Gallery_Mapper::get_instance();
					$gallery        = $gallery_mapper->find( $gid );
					$gallery_id     = $gallery->gid;
					$gallery_name   = $gallery->name;
					$image_mapper   = C_Image_Mapper::get_instance();
					$attachment_ids = array_map( 'intval', $image_ids );

					if ( empty( $attachment_ids ) || ! is_array( $attachment_ids ) ) {
						$retval['error'] = __( 'An unexpected error occured.', 'nggallery' );
					}

					if ( empty( $retval['error'] ) && $gallery_id == 0 ) {
						if ( strlen( $gallery_name ) > 0 ) {
							$gallery = $gallery_mapper->create( array( 'title' => $gallery_name ) );
							if ( ! $gallery->save() ) {
								$retval['error'] = $gallery->get_errors();
							} else {
								$gallery_id = $gallery->id();
							}
						} else {
							$retval['error'] = __( 'No gallery name specified', 'nggallery' );
						}
					}

					if ( empty( $retval['error'] ) ) {

						$retval['gallery_id'] = $gallery_id;

						$storage = C_Gallery_Storage::get_instance();

						foreach ( $attachment_ids as $id ) {

							$abspath    = get_attached_file( $id );
							$file_data  = @file_get_contents( $abspath );
							$file_name  = M_I18n::mb_basename( $abspath );
							$attachment = get_post( $id );

							if ( empty( $attachment ) ) {
								continue;
							}

							$image = $storage->upload_image( $gallery_id, $file_name, $file_data );

							// Potentially import metadata from WordPress
							$image = $image_mapper->find( $image );
							if ( ! empty( $attachment->post_excerpt ) ) {
								$image->alttext = $attachment->post_excerpt;
							}
							if ( ! empty( $attachment->post_content ) ) {
								$image->description = $attachment->post_content;
							}
							$image = apply_filters( 'ngg_medialibrary_imported_image', $image, $attachment );
							$image_mapper->save( $image );
							$retval['image_ids'][] = $image->{$image->id_field};

						}

					}

					if ( ! empty( $retval['error'] ) ) {
						return $retval;
					} else {
						$retval['gallery_name'] = esc_html( $gallery_name );
					}

					return $retval;
				}
			}

			$obj = new Set_Image_To_Gallery();
			$obj->import_media_library_action();

			$shrtcode = '[ngg src="galleries" ids="' . $gid . '" display="basic_thumbnail"]';

			if ( preg_match( '/\[gallery .+\]/i', $post->post_content ) ) {
				$content = preg_replace( '/\[gallery .+\]/i', '[ngg src="galleries" ids="' . $gid . '" display="basic_thumbnail"]', $post->post_content );
			} else {
				$content = str_replace($shrtcode, '', $post->post_content);
				$content .= '<br>'.$shrtcode;
			}

			wp_update_post( array(
				'ID'           => $post->ID,
				'post_content' => $content,
			) );

		} catch ( Exception $exception ) {
			echo $exception->getMessage();
		}
	}

	var_dump($paginate);

	$next_page = add_query_arg( array(
		'action'   => 'migration',
		'paginate' => $paginate + 1
	), site_url() );


	?>

	<script>
		window.location = "<?php echo $next_page;?>";
	</script>
	<?php
	exit();
}