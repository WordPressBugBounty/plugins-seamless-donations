<?php
/*
 * Seamless Donations by David Gewirtz, adopted from Allen Snook
 *
 * Lab Notes: http://zatzlabs.com/lab-notes/
 * Plugin Page: http://zatzlabs.com/seamless-donations/
 * Contact: http://zatzlabs.com/contact-us/
 *
 * Copyright (c) 2015-2022 by David Gewirtz
 *
 */
// https://seamlessdonations.local/wp-content/plugins/seamless-donations/pay/paypalstd/try.php

// derive server URL without touching WordPress at all
$the_domain = sanitize_text_field( $_SERVER['HTTP_HOST'] );
$https  = sanitize_text_field( $_SERVER['HTTPS'] );
if ( strtolower( $https ) == 'on' ) {
	$url = 'https://' . $the_domain . '?PAYPALIPN=1';
} else {
	$url = 'http://' . $the_domain . '?PAYPALIPN=1';
}

header( 'Location: ' . $url );
exit;
