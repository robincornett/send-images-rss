<?php

/**
 * set up custom feed we can tweak without actually creating a new one.
 * source: http://vip.wordpress.com/documentation/altering-feeds/
 *
 * Gary: I don't really know how extending classes works, but I think I've done this right? Basically
 * I want the Feed Converter class to run as a whole if the query parameter is met.
 *
 */
class Alternate_Feed extends Feed_Converter {
	function __construct() {
		add_action( 'query_vars', array( $this, 'custom_feed_query' ) );
		add_filter( 'pre_get_posts', array( $this, 'change_custom_feed' ) );
	}

	public function custom_feed_query( $query_vars ) {
		$query_vars[] = 'custom';
		return $query_vars;
	}

	public function change_custom_feed( $query ) {
		$value = ( 'email' === $query->get( 'custom' ) );
		if ( $query->is_feed() && 'email' === $query->get( 'custom' ) ) {
			parent::__construct();
		}
		return $query;
	}

}
