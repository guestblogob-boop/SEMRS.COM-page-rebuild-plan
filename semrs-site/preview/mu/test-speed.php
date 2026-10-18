<?php
/**
 * TEST ONLY (preview): ?semrs_test_gtag=1 prints a fake Google tag and an
 * AdSense-like tag so the SEMRS Pro "delay JavaScript" rewrite can be checked.
 * Nothing is loaded from Google (the URLs point at this site).
 */

add_action(
	'wp_head',
	function () {
		if ( empty( $_GET['semrs_test_gtag'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		echo '<script async src="' . esc_url( home_url( '/?fake=googletagmanager.com/gtag/js' ) ) . '"></script>' . "\n";
		echo "<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());</script>\n";
		echo '<script async src="' . esc_url( home_url( '/?fake=pagead2.googlesyndication.com/pagead/js/adsbygoogle.js' ) ) . '"></script>' . "\n";
		echo '<script type="application/ld+json">{"@context":"https://schema.org","name":"gtag( test"}</script>' . "\n";
	}
);
