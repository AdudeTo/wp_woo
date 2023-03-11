<?php

/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress 123 */


if ( file_exists( dirname( __FILE__ ) . '/wp-config-local.php' )) {
    // IMPORTANT: ensure your local config does not include wp-settings.php
    require_once dirname( __FILE__ ) . '/wp-config-local.php'; 


} else {
	define( 'DB_NAME', '' );

	/** Database username */
	define( 'DB_USER', '' );
	
	/** Database password */
	define( 'DB_PASSWORD', '' );
	
	/** Database hostname  */
	define( 'DB_HOST', 'localhost' );
	
	/** Database charset to use in creating database tables. */
	define( 'DB_CHARSET', 'utf8mb4' );
	
	/** The database collate type. Don't change this if in doubt. */
	define( 'DB_COLLATE', '' );	

}

	/**#@+
	 * Authentication unique keys and salts.
	 *
	 * Change these to different unique phrases! You can generate these using
	 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
	 *
	 * You can change these at any point in time to invalidate all existing cookies.
	 * This will force all users to have to log in again.
	 *
	 * @since 2.6.0
	 */
	define( 'AUTH_KEY',         '6Tpb?-ct_F_@7JQvTA[s~_!74.S[T:{<K688_C`vZw-ls0XE^5~~k&6!:v &6Az{' );
		define( 'SECURE_AUTH_KEY',  'Dt IbJ6YDuyHuuOjAt2}d8?-[0C-oXcO@+fL)Q}@Sm4b/`V:6l$VD;*fQZQELnlw' );
		define( 'LOGGED_IN_KEY',    'v[G6:G]!y~Am^y=;?G1?~!*.kK`uq4IOVIM3kn}By7uucO/kvjZbjF7:75R1ul]p' );
		define( 'NONCE_KEY',        'vv_9G)S>6A2V ]rS BH;DeW+ 2Zwh*E4xU;DmT~sn%|Pr1E<vW!b+OHgqD(V-mvc' );
		define( 'AUTH_SALT',        'VY3sMz4*b;y?9LL])7~Dt1fC/%b9n3GG#[uC/[Q4O1hg,63+Pc,391n63dvxgDW7' );
		define( 'SECURE_AUTH_SALT', 'UT4n0x>8I*.WGx.OOS45R_4V2Yhx||lEq}&[{Dl<.p#GW55 &;,Y*lfd<x;;`-5N' );
		define( 'LOGGED_IN_SALT',   'S6[(ERB{R$C(.JBQ&G![j.2I^]h<JeIr}eszAP*~{zLkc:zNnZ:n|(,EuIe.&i:@' );
		define( 'NONCE_SALT',       '!OEFOCNZ[)Xpyrcz33rUy| Za2n<2wWwY1,9Nt(Sl?ms@L#VLU-}$!G JSJ;;:Vw' );
		
		/**#@-*/














/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
