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
//	Exit if .php file accessed directly
if (!defined('ABSPATH')) exit;

// for 5.0 conversion

function seamless_donations_addon_version_check($addon, $version) {
    // validation code that will run in later versions to disable plugins, if necessary
    // false prevents the add-ons from doing anything
    return true;
}

