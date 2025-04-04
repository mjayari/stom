<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'stom_db' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

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
define( 'AUTH_KEY',         'Bw/kiy+,Qh>zX_:F~?BrjP&T-X,gPs[)[j>-RtS*:/:Ss$e.[1`!I|am8MkQDp6u' );
define( 'SECURE_AUTH_KEY',  '0,+A7w&B*GIzVe/bk[5NIW1{e@(84x|V7G4!(ND l^7g^`/#V(fBnmc[0CB|KB|>' );
define( 'LOGGED_IN_KEY',    ',k#[6`))[ jSUfuc0G[n1PmTIby6HY+Hu7sF,`Q{1pMi{/GBW;^ytq:+J4AK195%' );
define( 'NONCE_KEY',        '!KF$w/W[X>bu~A`PxH@lf(4;B_rr5@/lS>opQsT^s~,WZn5}`|3u vbvb1RzdBkw' );
define( 'AUTH_SALT',        '.VA=,GZ=trc$yQnz|*GZ6WA,z+:-C5yY?JDgz>xb$Z^vp:nfreeL*bVFA9g){u8|' );
define( 'SECURE_AUTH_SALT', 'UA02 @JT+_9k}YwA@B4yYUxb~=Kdg8c#!o~%7_Ar+K*daRdH*bH6dWuB)txlb_ZN' );
define( 'LOGGED_IN_SALT',   'NkCo=>Vh{o{zhUO0al%g8{rT4OdJX?RIvggC?#lBfV*rZl3Es-hpf!HPGPr`7Ytn' );
define( 'NONCE_SALT',       ';m=FY@9p*aY&i|0J<ntUkh(SGV;m[atDX~*[LevE~08}ik/u8|zeNF^a^hPV76#&' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';


/**
 * To turn off automatic updates for WordPress core
 */
define('WP_AUTO_UPDATE_CORE', false );

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
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
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
