<?php

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/bootstrap.php';

if ( ! function_exists( 'xd' ) ) {
	function xd() {
		die( var_export( func_get_args() ) );
	}
}

if ( ! function_exists( 'msds_ldb_delete_blog' ) ) {
	function msds_ldb_delete_blog( $blog_id ) {
		global $wpdb;
		$tables = [
			"wptests_{$blog_id}_commentmeta",
			"wptests_{$blog_id}_comments",
			"wptests_{$blog_id}_links",
			"wptests_{$blog_id}_options",
			"wptests_{$blog_id}_postmeta",
			"wptests_{$blog_id}_posts",
			"wptests_{$blog_id}_term_relationships",
			"wptests_{$blog_id}_term_taxonomy",
			"wptests_{$blog_id}_termmeta",
			"wptests_{$blog_id}_terms",
		];
		$wpdb->query( "DELETE FROM {$wpdb->blogs} WHERE blog_id=$blog_id" );
		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE {$table}" );
		}
	}
}

for ( $i = 0; $i <= 161-3; $i++ ) {
	$dataset  = substr( md5( "{$i}" ), 0, 3 );
	$wpdb->add_database(
		array(
			'host'     => DB_HOST,
			'user'     => DB_USER,
			'password' => DB_PASSWORD,
			'name'     => DB_NAME,
			'dataset'  => $dataset,
			'write'    => 1,
			'read'     => 1,
		)
	);
}

$wpdb->add_database(
	array(
		'host'     => DB_HOST,
		'user'     => DB_USER,
		'password' => DB_PASSWORD,
		'name'     => DB_NAME,
		'dataset'  => 'srv-one',
		'write'    => 1,
		'read'     => 1,
	)
);
$wpdb->add_database(
	array(
		'host'     => DB_HOST,
		'user'     => DB_USER,
		'password' => DB_PASSWORD,
		'name'     => DB_NAME,
		'dataset'  => 'srv-two',
		'write'    => 1,
		'read'     => 1,
	)
);
$wpdb->add_database(
	array(
		'host'     => DB_HOST,
		'user'     => DB_USER,
		'password' => DB_PASSWORD,
		'name'     => DB_NAME,
		'dataset'  => 'srv-three',
		'write'    => 1,
		'read'     => 1,
	)
);

$wpdb->add_database(
	array(
		'host'     => DB_HOST,
		'user'     => DB_USER,
		'password' => DB_PASSWORD,
		'name'     => DB_NAME,
		'dataset'  => 'srv-four',
		'write'    => 1,
		'read'     => 1,
	)
);

$fld = MultisiteDataset_Config::DATASET_FIELD;
$wpdb->query( "alter table {$wpdb->blogs} add column {$fld} varchar(32) after lang_id;" );
