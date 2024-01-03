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
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

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


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



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
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}


define('AUTH_KEY',         'ODq8kr42eH0dfYMIwCIHCr3TcSBV9m6lDvNDIQnisZ2/GK97ZnMP9C8z78zeZz/AaiY16RX0LTkSJh23dNYNjw==');
define('SECURE_AUTH_KEY',  'NWS4LC7Tm4ldcAc7CT0XtapKQVp4TijwBLYjb2N8ajAU5OpQ3SfwmAmJPMEHPc3g6ZPl5jLXVN+PzPb8HazjyQ==');
define('LOGGED_IN_KEY',    '7Y5zQZ1HxRifHAdzR8uaGxkw7GaS9PkkNFbQyd8zdJgi63NY1e0ZPs5k0s4KaCOjXcs4LBgCSvkMOqPxrlDP4A==');
define('NONCE_KEY',        'wdLxO2bDcDzETSROk3b12TC21Q+iemgC510vYzvHaFKE/T0EQZFpV30xYAFQzignqXvio/kG9JTbmLSHHYpOvw==');
define('AUTH_SALT',        'lusr1t7FXbWA8xsvfKkxT5fNpBwQxByEbCUPafgBA6j8EyVnHlc2s7JS8pMeJzCjBGydc753npvXGtzk8/h/Aw==');
define('SECURE_AUTH_SALT', 'lwzfDnF0cm2Y3+dYQQiG/9zXY9biDz7e3mo/KGrWemss64Mb0gAs2pRmsJOsygo0tXvQ4x7T7WbeQH1d8EiECA==');
define('LOGGED_IN_SALT',   'Yo8SwTOdw2EZF416sKefKIE/bVh9sQux7oRdarUFp3JTdRi7vltqCyuwc6f20ajRRwwHYR2MLAsNYptMz82sCA==');
define('NONCE_SALT',       'imek63kJwdGvbiPZqSgE+9MICwJ0tfn4PW7DhomCJyPJDeuecKDE05UOaOzneAKfWR4HzH7ADkaqrKFE+TzVvQ==');
define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
