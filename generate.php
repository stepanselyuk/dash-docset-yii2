<?php
/**
 * Created by PhpStorm.
 * Author: Stepan Seliuk <stepan@selyuk.com>
 * Date: 07/05/14
 * Time: 00:27
 *
 * Special thanks to https://github.com/akirk/dash-phpunit
 */

function build_path() {

	return implode( DIRECTORY_SEPARATOR, func_get_args() );
}

/**
 * @param sqlite3 $db
 * @param string  $name
 * @param string  $type
 * @param string  $file
 */
function add_to_index( $db, $name, $type, $file ) {

	static $i = 0;

	$db->query( 'INSERT OR IGNORE INTO searchIndex(name, type, path) VALUES ("' . $name . '", "' . $type . '", "' . $file . '")' );

	echo ++$i . '. Added "' . $name . '" as a ' . $type . ' in file ' . $file . PHP_EOL;
}

list( , $docsetPath ) = $argv;

$docsetPath = build_path( __DIR__, $docsetPath );

// Creating Info.plist

$plist = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
	<key>CFBundleIdentifier</key>
	<string>yii2</string>
	<key>CFBundleName</key>
	<string>Yii 2</string>
	<key>DocSetPlatformFamily</key>
	<string>yii2</string>
	<key>isDashDocset</key>
	<true/>
	<key>dashIndexFilePath</key>
	<string>index.html</string>
	<key>isJavaScriptEnabled</key><true/>
</dict>
</plist>';

file_put_contents( build_path( $docsetPath, '../../Info.plist' ), $plist );

if ( file_exists( build_path( $docsetPath, '../docSet.dsidx' ) ) ) {
	@unlink( build_path( $docsetPath, '../docSet.dsidx' ) );
}

// Creating SQLite DB
$db = new sqlite3( build_path( $docsetPath, '../docSet.dsidx' ) );

$db->query( 'CREATE TABLE searchIndex(id INTEGER PRIMARY KEY, name TEXT, type TEXT, path TEXT)' );
$db->query( 'CREATE UNIQUE INDEX anchor ON searchIndex (name, type, path)' );

$extTypes = [ 'Callback',
              'Command',
              'Component',
              'Constructor',
              'Element',
              'Entry',
              'Error',
              'Event',
              'Exception',
              'Field',
              'File',
              'Filter',
              'Framework',
              'Function',
              'Instance',
              'Library',
              'Literal',
              'Macro',
              'Mixin',
              'Module',
              'Namespace',
              'Object',
              'Option',
              'Package',
              'Protocol',
              'Record',
              'Resource',
              'Sample',
              'Service',
              'Struct',
              'Style',
              'Subroutine',
              'Tag',
              'Union',
              'Value', ];

foreach ( scandir( $docsetPath ) as $file ) {

	if ( is_file( build_path( $docsetPath, $file ) ) && strpos( $file, '.html' ) !== false ) {

		if ( strpos( $file, 'guide' ) === 0 ) {

			$name = substr( $file, strlen( 'guide-' ), -strlen( '.html' ) );
			$name = str_replace( '-', ' ', $name );
			$name = ucfirst( $name );

			if ( $name == '' ) $name = 'Guide';

			add_to_index( $db, $name, 'Guide', $file );

			echo 'Added "' . $name . '" as a Guide.' . PHP_EOL;

		}
		elseif ( strpos( $file, 'yii' ) === 0 ) {

			$content = file_get_contents( build_path( $docsetPath, $file ) );

			if ( preg_match( '/<h1>\n*(?:Abstract\s)?(Class|Class|Interface|Trait)\s([0-9a-zA-Z\\\]+)\n*<\/h1>/', $content, $m ) ) {

				$class = trim( $m[ 2 ] );

				add_to_index( $db, $class, $m[ 1 ], $file );

				foreach ( $extTypes as $type ) {

					if ( stripos( $m[ 2 ], $type ) !== false ) {
						add_to_index( $db, $class, $type, $file );
					}

				}

			}
			else {

				trigger_error( 'Can\'t find class name in file ' . $file . PHP_EOL, E_ERROR );
			}

			echo $class . PHP_EOL;

			if ( preg_match_all( '/<div class="summary doc-(property|method|event|const)">.*<table[^>]*>(.+)<\/table>.*<\/div>/sU', $content, $m, PREG_SET_ORDER ) ) {

				foreach ( $m as $item ) {

					if ( preg_match_all( '/<tr[^>]*id="(.+)"[^>]*>.*<td[^>]*><a[^>]*href="(.+)".*<\/tr>/sU', $item[ 2 ], $k, PREG_SET_ORDER ) ) {

						foreach ( $k as $elem ) {

							$type = ucfirst( $item[ 1 ] );
							if ( $type == 'Const' ) $type = 'Constant';

							add_to_index( $db, $class . '::' . $elem[ 1 ], $type, $elem[ 2 ] );
						}

					}

				}

			}
			else {

				trigger_error( 'Can\'t find any sections in file ' . $file . PHP_EOL, E_ERROR );
			}

		}

	}
}
