<?php

function volleytnt_sql2keyval( $sql ) {
	global $wpdb;
	$return = array();
	if ( $data = $wpdb->get_results( $sql, ARRAY_A ) ) foreach ( $data as $row ) {
		$k = array_shift( $row );
		$v = array_shift( $row );
		$return[ $k ] = $v;
	}
	return $return;
}

function volleytnt_date_format( $type = 'php' ) {
	$_s = array( '{g}', '{m}', '{a}' );
	switch ( $type ) {
		case 'js':
			$_r = array( 'd', 'm', 'yy' );
			break;
		case 'sql':
			$_r = array( '%e', '%c', '%Y' );
			break;
		case 'php':
		default:
			$_r = array( 'j', 'n', 'Y' );
			break;
	}
	return str_replace( $_s, $_r, __( '{g}/{m}/{a}', 'volleytnt' ) );
}

?>