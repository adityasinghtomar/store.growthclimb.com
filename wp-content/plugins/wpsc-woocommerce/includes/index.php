<?php

// Load widgets.
foreach ( glob( dirname( __FILE__ ) . '/widget/*.php' ) as $filename ) {
	include_once $filename;
}

// Load custom field types.
foreach ( glob( dirname( __FILE__ ) . '/custom-field-types/*.php' ) as $filename ) {
	include_once $filename;
}

// Load report.
foreach ( glob( dirname( __FILE__ ) . '/report/*.php' ) as $filename ) {
	include_once $filename;
}
