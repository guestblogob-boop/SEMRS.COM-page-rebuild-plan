<?php
/**
 * Free Tools Pro: switches on the Pro features of the free SEMRS Tools plugin
 * (bulk server checks, custom expert-help box per category, usage report,
 * higher limits). The features live in SEMRS Tools; this module only unlocks them.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'semrs_tools_pro', '__return_true' );
