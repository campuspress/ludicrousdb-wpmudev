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
	private $_sharder;

	public function __construct( object $db ) {
		$this->_db       = $db;
		$this->_selector = new MultisiteDataset_QuerySelector();
		$this->_sharder  = new MultisiteDataset_Sharder( $db );
	}

	public function init() {
		$this->add_query_selector();

		add_action(
			'wp_insert_site',
			[ $this, 'handle_insert_site' ]
		);
	}

	public function handle_insert_site( object $site ) {
		$this->remove_query_selector();
		$shard = $this->_sharder->shard_for( $site->id );
		if ( $this->_selector->shard_update( $site->id, $shard, $this->_db ) ) {
			$this->_selector->set_dataset( $site->id, $shard );
		} else {
			$this->_selector->unset_dataset( $site->id );
		}
		$this->add_query_selector();
	}

	public function get_selector(): MultisiteDataset_QuerySelector {
		return $this->_selector;
	}

	public function get_callback(): callable {
		return [ $this->get_selector(), 'query_select' ];
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
	private $_datasets = [];

	public function has_dataset( int $bid, bool $empty_check = false ): bool {
		return empty( $empty_check )
			? isset( $this->_datasets[ $bid ] )
			: ! empty( $this->_datasets[ $bid ] );
	}

	public function get_dataset( int $bid ): string {
		return $this->_datasets[ $bid ];
	}

	public function set_dataset( int $bid, string $hndl ) {
		$this->_datasets[ $bid ] = $hndl;
	}

	public function unset_dataset( int $bid ) {
		if ( $this->has_dataset( $bid ) ) {
			unset( $this->_datasets[ $bid ] );
		}
	}

	public function shard_update( $blog_id, $shard, $wpdb ): bool {
		$global = 'global__r';
		$dbh    = $wpdb->dbhs[ $global ];
		if ( empty( $dbh ) ) {
			return false; // should be unreachable, yet...
		}

		// TODO: prepare query
		$result = mysqli_query( $dbh, "UPDATE {$wpdb->blogs} SET srv='{$shard}' WHERE blog_id={$blog_id} LIMIT 1;" );
		return ! empty( $result );
	}

	// TODO: refactor
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

		if ( ! $this->has_dataset( $bid ) ) {
			// initialize to fallback so we don't do multiple DB queries.
			$this->set_dataset( $bid, '' );

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

			$this->set_dataset( $bid, $row['srv'] );
		}

		if ( ! $this->has_dataset( $bid, true ) ) {
			return;
		}

		return [ 'dataset' => $this->get_dataset( $bid ) ];
	}
}


define( 'LDB_MULTISITE_DATASET', 'ldb_multisite_dataset' );
$GLOBALS[ LDB_MULTISITE_DATASET ] = new MultisiteDataset( $wpdb );
$GLOBALS[ LDB_MULTISITE_DATASET ]->init();
