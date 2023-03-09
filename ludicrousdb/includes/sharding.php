<?php

function get_shards( $wpdb ) {
	return array_values(
		array_filter(
			array_keys( $wpdb->ludicrous_servers ),
			function( $srv ) {
				return $srv != 'global';
			}
		)
	);
}

function shard_for( $blog_id, $wpdb ) {
	$blog_id = (int) $blog_id;
	$shards  = get_shards( $wpdb );
	return $shards[ $blog_id % count( $shards ) ];
}

function ldb_select_multisite_dataset( $query, $wpdb ) {
	if ( empty( $wpdb->dbhname ) ) {
		return;
	}
	if ( empty( $wpdb->blogid ) ) {
		return;
	}

	static $handlers = [];

	$bid = (int) $wpdb->blogid;
	if ( $bid <= 1 ) {
		return;
	}

	if ( ! isset( $handlers[ $bid ] ) ) {
		$handlers[ $bid ] = false; // initialize to fallback so we don't do multiple DB queries

		// TODO: perhaps don't hardocde global datasource
		$global = 'global__r';
		$dbh    = $wpdb->dbhs[ $global ];
		if ( empty( $dbh ) ) {
			return; // should be unreachable, yet...
		}

		$result = mysqli_query( $dbh, "SELECT srv FROM {$wpdb->blogs} WHERE blog_id={$bid};" );
		if ( ! $result || false === $row = mysqli_fetch_assoc( $result ) ) {
			return; // simple return falls back to global dataset
		}
		if ( empty( $row['srv'] ) ) {
			return; // simple return falls back to global dataset
		}

		if ( ! in_array( $row['srv'], array_keys( $wpdb->ludicrous_servers ), true ) ) {
			// return; // simple return falls back to global dataset
			return $wpdb->bail( "Unknown connection handler for [{$bid}]" );
		}

		$handlers[ $bid ] = $row['srv'];
	}

	if ( empty( $handlers[ $bid ] ) ) {
		return;
	}

	return [ 'dataset' => $handlers[ $bid ] ];
}
$wpdb->add_callback( 'ldb_select_multisite_dataset', 'dataset' );
