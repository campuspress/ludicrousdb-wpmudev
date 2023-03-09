<?php

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

/* define( 'CAMPUS_AUDIT_IS_TEST_ENV', true ); */

/* require_once $_tests_dir . '/includes/functions.php'; */

/*
 function _manually_load_plugin() { */
/*
  if ( ! defined( 'CAMPUS_AUDIT_TESTS_DATA_DIR' ) ) { */
/*
	  define( */
/*
		  'CAMPUS_AUDIT_TESTS_DATA_DIR', */
/*
		  trailingslashit( dirname( __FILE__ ) ) . 'data' */
/*
	  ); */
/* 	} */

/*
  require_once dirname( dirname( __FILE__ ) ) . '/campus-audit-trail.php'; */
/*
 } */
/* tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' ); */

require_once $_tests_dir . '/includes/bootstrap.php';

if ( ! function_exists( 'xd' ) ) {
	function xd() {
		die( var_export( func_get_args() ) );
	}
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
