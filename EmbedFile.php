<?php
/**
 * EmbedFile.php - Adds a parser function embedding video from popular sources.
 * See README for details. For licensing information, see LICENSE. For a
 * complete list of contributors, see CREDITS
 */

# Confirm MW environment
if (!defined('MEDIAWIKI')) {
       echo <<<EOT
To install EmbedFile, put the following line in LocalSettings.php:
require_once( "\$IP/extensions/EmbedFile/EmbedFile.php" );
EOT;
    exit( 1 );
}

# Credits
$wgExtensionCredits['parserhook'][] = array(
	'path'        => __FILE__,
	'name'        => 'EmbedFile',
	'author'      => array('Arsen Borovinskiy',),
	'url'         => 'http://k.psu.ru/wiki/Extension:EmbedFile',
	'version'     => '1.0',
	'descriptionmsg' => 'embedfile-desc'
);
$dir = dirname(__FILE__) . '/';
$wgExtensionMessagesFiles['embedfile'] = $dir . 'EmbedFile.i18n.php';
require_once($dir . "EmbedFile.hooks.php");
require_once($dir . "EmbedFile.Services.php");


$wgHooks['ParserFirstCallInit'][] = "EmbedFile::setup";
if (version_compare($wgVersion, '1.7', '<')) {
	// Hack solution to resolve 1.6 array parameter nullification for hook args
	function wfEmbedFileLanguageGetMagic( &$magicWords ) {
		EmbedFile::parserFunctionMagic( $magicWords );
		return true;
	}
	$wgHooks['LanguageGetMagic'][] = 'wfEmbedFileLanguageGetMagic';
} else {
	$wgHooks['LanguageGetMagic'][] = 'EmbedFile::parserFunctionMagic';
}
