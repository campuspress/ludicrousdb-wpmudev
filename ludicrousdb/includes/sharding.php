<?php

class MultisiteDataset_Sharder {
	private $_db;

	public function __construct( object $db ) {
		$this->_db = $db;
	}

	function get_shards() {
		return array_values(
			array_filter(
				array_keys( $this->_db->ludicrous_servers ),
				function( $srv ) {
					return $srv != 'global';
				}
			)
		);
	}

	function shard_for( $blog_id ) {
		$blog_id = (int) $blog_id;
		$shards  = $this->get_shards();
		return $shards[ $blog_id % count( $shards ) ];
	}
}

class MultisiteDataset {
	private $_db;
	private $_selector;

	public function __construct( object $db ) {
		$this->_db       = $db;
		$this->_selector = new MultisiteDataset_QuerySelector();
	}

	public function init() {
		$this->add_query_selector();
	}

	public function get_callback(): callable {
		return [ $this->_selector, 'query_select' ];
	}

	public function add_query_selector() {
		$this->_db->add_callback(
			$this->get_callback(),
			'dataset'
		);
	}

	public function remove_query_selector(): bool {
		$nerf = false;
		foreach ( $this->_db->ludicrous_callbacks['dataset'] as $idx => $cback ) {
			if ( $cback === $this->get_callback() ) {
				$nerf = $idx;
				break;
			}
		}
		if ( $nerf !== false ) {
			unset( $this->_db->ludicrous_callbacks[ $nerf ] );
			return true;
		}
		return false;
	}
}

class MultisiteDataset_QuerySelector {
	private $_handlers = [];

	public function has_handler( int $bid, bool $empty_check = false ): bool {
		return empty( $empty_check )
			? isset( $this->_handlers[ $bid ] )
			: ! empty( $this->_handlers[ $bid ] );
	}

	public function get_handler( int $bid ): string {
		return $this->_handlers[ $bid ];
	}

	public function set_handler( int $bid, string $hndl ) {
		$this->_handlers[ $bid ] = $hndl;
	}

	public function unset_handler( int $bid ) {
		if ( $this->has_handler( $bid ) ) {
			unset( $this->_handlers[ $bid ] );
		}
	}

	public function query_select( $query, $wpdb ) {
		if ( empty( $wpdb->dbhname ) ) {
			return;
		}
		if ( empty( $wpdb->blogid ) ) {
			return;
		}

		$bid = (int) $wpdb->blogid;
		if ( $bid <= 1 ) {
			return;
		}

		if ( ! $this->has_handler( $bid ) ) {
			// initialize to fallback so we don't do multiple DB queries.
			$this->set_handler( $bid, '' );

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

			$this->set_handler( $bid, $row['srv'] );
		}

		if ( ! $this->has_handler( $bid, true ) ) {
			return;
		}

		return [ 'dataset' => $this->get_handler( $bid ) ];
	}
}

/*
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
// $wpdb->add_callback( 'ldb_select_multisite_dataset', 'dataset' );
*/
