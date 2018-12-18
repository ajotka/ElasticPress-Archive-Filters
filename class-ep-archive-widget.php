<?php

class EP_Archive_Widget extends WP_Widget {
	/**
	 * Create widget
	 */
	public function __construct() {
		parent::__construct(
			// Base ID of your widget
			'ep-archive', 
			// Widget name will appear in UI
			__('ElasticPress - Archive', 'ep-archive'), 
			// Widget description
			array( 'description' => __( 'Widget for elastic search query', 'ep-archive' ), ) 
		);
	}

	/**
	 * Output widget
	 *
	 * @param  array $args
	 * @param  array $instance
	 * @since 2.5
	 */
	public function widget( $args, $instance ) {
		
		echo $args['before_widget'];

		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . esc_html( apply_filters( 'widget_title', $instance['title'] ) ) . $args['after_title'];
		}

		global $wpdb, $wp_locale;

		$defaults = array(
			'type'            => 'monthly',
			'limit'           => '',
			'format'          => 'html',
			'before'          => '',
			'after'           => '',
			'show_post_count' => false,
			'echo'            => 1,
			'order'           => 'DESC',
	        'alpha_order'      => 'ASC',
	        'post_order'       => 'DESC',
			'post_type'       => 'post',
		);

		$r = wp_parse_args( $args, $defaults );

		$order = 'desc';
		$sql_where = $wpdb->prepare( "WHERE post_type = %s OR post_type = %s AND post_status = 'publish'", 'post', 'page' );
		$where = apply_filters( 'getarchives_where', $sql_where);
		$join = apply_filters( 'getarchives_join', '' );

		$query = "SELECT YEAR(post_date) AS `year`, count(ID) as posts FROM $wpdb->posts $join $where GROUP BY YEAR(post_date) ORDER BY post_date $order ";
		$key   = md5( $query );

		$output = '';

		if ( ! $results = wp_cache_get( $key, 'posts' ) ) {
			$results = $wpdb->get_results( $query );
			wp_cache_set( $key, $results, 'posts' );
		}

		$selected_filters = ep_facets_get_selected();
		
		?>
		<?php if ( $results ): ?>
			<div class="terms sorting">
				<div class="inner">
					<?php foreach ( (array) $results as $result ) : ?>
					<?php	
						$filters = $selected_filters;
						$selected = ! empty( $selected_filters['taxonomies']['date']['terms'][ $result->year ] );
						if ( $selected ) {
							if ( ! empty( $filters['taxonomies']['date'] ) && ! empty( $filters['taxonomies']['date']['terms'][ $result->year ] ) ) {
								unset($filters['taxonomies']['date']['terms'][$result->year]);
							}
						} else {
							if ( empty( $filters['taxonomies']['date'] ) ) {
								$filters['taxonomies']['date'] = array(
									'terms' => array(),
								);
							}
							
							$filters['taxonomies']['date']['terms'][$result->year] = true;
						}
					?>
					<div class="term  <?php if ( $selected ) : ?>selected<?php endif; ?>" data-year-name="<?php echo esc_attr( sprintf( '%d', $result->year ) ); ?>" data-year-slug="<?php echo esc_attr( sprintf( '%d', $result->year ) ); ?>">
						<a href="<?php echo esc_attr( ep_archive_build_query_url($filters) ); ?>">
							<input type="checkbox" <?php if ( $selected ) : ?>checked<?php endif; ?>>
							<?php if( $result->year == date('Y')) {
								echo _e('Current year', 'ep-archive');
							} else {
								echo esc_html( sprintf( '%d', $result->year ) ); 
							}
							?>
						</a>
					</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>
		<?php

		echo $args['after_widget'];
	}

	/**
	 * Output widget form
	 *
	 * @param  array $instance
	 * @since 2.5
	 */
	public function form( $instance ) {
		// Filter settings from EP page
		$dashboard_url = admin_url( 'admin.php?page=elasticpress' );

		if ( defined( 'EP_IS_NETWORK' ) && EP_IS_NETWORK ) {
			$dashboard_url = network_admin_url( 'admin.php?page=elasticpress' );
		}

		$feature  = ep_get_registered_feature( 'facets' );
		$settings = array();

		if ( $feature ) {
			$settings = $feature->get_settings();
		}

		$settings = wp_parse_args( $settings, array(
			'match_type' => 'all',
		) );

		$set = esc_html__( 'all', 'ep-archive' );
		$not_set = esc_html__( 'any', 'ep-archive' );

		if ( 'any' === $settings['match_type'] ) {
			$set = esc_html__( 'any', 'ep-archive' );
			$not_set = esc_html__( 'all', 'ep-archive' );
		}
		//

		$title = ( ! empty( $instance['title'] ) ) ? $instance['title'] : __( 'EP Archive title', 'ep-archive' );
		$sorting = ( ! empty( $instance['sorting'] ) ) ? $instance['sorting'] : '';

		$sorting_options = array(
			'desc' => array(
				'name' => 'Descending',
				'slug' => 'desc' 
			), 
			'asc'	=> array(
				'name' => 'Ascending',
				'slug' => 'asc' 
			)
		);
		?>
		<div class="widget-ep-sorting">
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>">
					<?php esc_html_e( 'Title:', 'ep-archive' ); ?>
				</label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
			</p>

			<p>
				<label for="<?php echo $this->get_field_id( 'sorting' ); ?>">
					<?php esc_html_e( 'Sorting:', 'ep-archive' ); ?>
				</label><br>

				<select id="<?php echo $this->get_field_id( 'sorting' ); ?>" name="<?php echo $this->get_field_name( 'sorting' ); ?>">
					<?php foreach ( $sorting_options as $item ) : ?>
						<option <?php selected( $sorting, $item['name'] ); ?> value="<?php echo esc_attr( $item['slug'] ); ?>"><?php echo esc_html( $item['name'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>

			<p><?php echo wp_kses_post( sprintf( __( 'Faceting will  filter out any content that is not tagged to all selected terms; change this to show <strong>%s</strong> content tagged to <strong>%s</strong> selected term in <a href="%s">ElasticPress settings</a>.', 'ep-archive' ), $set, $not_set, esc_url( $dashboard_url ) ) ); ?></p>
		</div>

		<?php
	}

	/**
	 * Sanitize fields
	 *
	 * @param  array $new_instance
	 * @param  array $old_instance
	 * @since 2.5
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();

		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['sorting'] = ( ! empty( $new_instance['sorting'] ) ) ? strip_tags( $new_instance['sorting'] ) : '';

		return $instance;
	}
}
