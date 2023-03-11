<?php
/**
 * Custom network sharding setup with LudicrousDB.
 * @phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound
 */

/**
 * Global constants setup
 */
final class MultisiteDataset_Config {
	const GLOBAL_DATASET = 'global';
	const GLOBAL_READER  = 'global__r';
	const DATASET_FIELD  = 'srv';
}

/**
 * Shard distribution
 * Used in new site creation
 */
class MultisiteDataset_Sharder {

	/**
	 * WPDB
	 *
	 * @var LudicrousDB
	 */
	private $_db;

	/**
	 * Constructor
	 *
	 * @param object $db LudicrousDB instance
	 */
	public function __construct( object $db ) {
		$this->_db = $db;
	}

	/**
	 * Gets a list of registered shards
	 */
	public function get_shards() {
		return array_values(
			array_filter(
				array_keys( $this->_db->ludicrous_servers ),
				function( $srv ) {
					return $srv != MultisiteDataset_Config::GLOBAL_DATASET;
				}
			)
		);
	}

	/**
	 * Distributes a site among registered shards
	 *
	 * @param int $blog_id Site ID
	 */
	public function shard_for( int $blog_id ): string {
		$blog_id = (int) $blog_id;
		$shards  = $this->get_shards();
		return $shards[ $blog_id % count( $shards ) ];
	}

	/**
	 * Whether or not we have a valid, registered shard
	 *
	 * @param string $shard Shard name
	 */
	public function is_valid_shard( string $shard ): bool {
		if ( $shard === MultisiteDataset_Config::GLOBAL_DATASET ) {
			return true;
		}
		return in_array( $shard, $this->get_shards(), true );
	}
}

/**
 * Main dataset handler class
 */
class MultisiteDataset {

	/**
	 * WPDB
	 *
	 * @var LudicrousDB
	 */
	private $_db;

	/**
	 * Selector
	 *
	 * @var MultisiteDataset_QuerySelector
	 */
	private $_selector;

	/**
	 * Shard dispatcher
	 *
	 * @var MultisiteDataset_Sharder
	 */
	private $_sharder;

	/**
	 * Constructor
	 *
	 * @param object $db LudicrousDB instance
	 */
	public function __construct( object $db ) {
		$this->_db       = $db;
		$this->_selector = new MultisiteDataset_QuerySelector();
		$this->_sharder  = new MultisiteDataset_Sharder( $db );
	}

	/**
	 * Register callbacks and listen to WP hooks
	 */
	public function init() {
		$this->add_query_selector();

		add_action(
			'wp_insert_site',
			array( $this, 'handle_insert_site' )
		);
	}

	/**
	 * Handles new network site creation
	 *
	 * @param object $site WordPress site object: WP_Site instance
	 */
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

	/**
	 * Gets the sharding query selector instance
	 */
	public function get_selector(): MultisiteDataset_QuerySelector {
		return $this->_selector;
	}

	/**
	 * Gets the actual sharding callback
	 */
	public function get_callback(): callable {
		return array( $this->get_selector(), 'query_select' );
	}

	/**
	 * Registers query selector callback
	 */
	public function add_query_selector() {
		$this->_db->add_callback(
			$this->get_callback(),
			'dataset'
		);
	}

	/**
	 * Deregisters query selector callback
	 */
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

/**
 * Handles DB queries
 */
class MultisiteDataset_QuerySelector {

	/**
	 * Local datasets cache
	 *
	 * @var array
	 */
	private $_datasets = array();

	/**
	 * Checks whether we have a dataset cached locally
	 *
	 * @param int  $bid Site ID
	 * @param bool $empty_check Whether to validate for presence
	 */
	public function has_dataset( int $bid, bool $empty_check = false ): bool {
		return empty( $empty_check )
			? isset( $this->_datasets[ $bid ] )
			: ! empty( $this->_datasets[ $bid ] );
	}

	/**
	 * Gets local dataset cache
	 *
	 * @param int $bid Site ID
	 */
	public function get_dataset( int $bid ): string {
		return $this->_datasets[ $bid ];
	}

	/**
	 * Sets local dataset cache for a site
	 *
	 * @param int    $bid Site ID
	 * @param string $hndl Dataset
	 */
	public function set_dataset( int $bid, string $hndl ) {
		$this->_datasets[ $bid ] = $hndl;
	}

	/**
	 * Clears local dataset cache
	 *
	 * @param int $bid Site ID
	 */
	public function unset_dataset( int $bid ) {
		if ( $this->has_dataset( $bid ) ) {
			unset( $this->_datasets[ $bid ] );
		}
	}

	/**
	 * Update shard info in DB for a particular site
	 *
	 * @param int         $blog_id Site ID to update
	 * @param string      $shard Shard (dataset) name
	 * @param LudicrousDB $wpdb LudicrousDB instance
	 */
	public function shard_update( $blog_id, $shard, $wpdb ): bool {
		$global = MultisiteDataset_Config::GLOBAL_READER;
		$dbh    = $wpdb->dbhs[ $global ];
		if ( empty( $dbh ) ) {
			return false; // should be unreachable, yet...
		}

		$blog_id = (int) $blog_id;
		if ( ! $blog_id ) {
			return false;
		}
		$sharder = new MultisiteDataset_Sharder( $wpdb );
		if ( ! $sharder->is_valid_shard( $shard ) ) {
			return false;
		}

		$shard_esc = mysqli_real_escape_string( $dbh, $shard );
		$fld       = MultisiteDataset_Config::DATASET_FIELD;
		$result    = mysqli_query(
			$dbh,
			"UPDATE {$wpdb->blogs} SET {$fld}='{$shard_esc}' WHERE blog_id={$blog_id} LIMIT 1;"
		);
		return ! empty( $result );
	}

	/**
	 * LudicrousDB dataset handler callback
	 * TODO: refactor
	 *
	 * @param string      $query MySQL query
	 * @param LudicrousDB $wpdb LudicrousDB instance
	 */
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

			$global = MultisiteDataset_Config::GLOBAL_READER;
			$dbh    = $wpdb->dbhs[ $global ];
			if ( empty( $dbh ) ) {
				return; // should be unreachable, yet...
			}

			$fld    = MultisiteDataset_Config::DATASET_FIELD;
			$result = mysqli_query( $dbh, "SELECT {$fld} FROM {$wpdb->blogs} WHERE blog_id={$bid};" );
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

		return array( 'dataset' => $this->get_dataset( $bid ) );
	}
}


define( 'LDB_MULTISITE_DATASET', 'ldb_multisite_dataset' );
$GLOBALS[ LDB_MULTISITE_DATASET ] = new MultisiteDataset( $wpdb );
$GLOBALS[ LDB_MULTISITE_DATASET ]->init();
