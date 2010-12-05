#!/usr/bin/php
<?php
	/**
	 * An adapation of Autosmush to work on the 
	 * local filesystem. Got the idea from 
	 * the folks WPEngine.
	 *
	 * @author Iain Cambridge
	 * @license http://backie.org/copyright/bsd-license/ BSD License
	 * @package Filesmsuh
	 * @copyright Iain Cambridge All rights reserved 2010 (c)
	 * @version 1.0
	 */
	 
	$shortOpts = "tqd:";
	$longOpts = array("help","dir:","test");
	
	$options  = getopt($shortOpts, $longOpts);
	
	if(array_key_exists('help', $options) || $GLOBALS['argc'] == 1){
		print "Filesmush".PHP_EOL;
		print "===================".PHP_EOL;
		print " -d (--dir) <dir> - The directory that is to be backed up.".PHP_EOL;
		print " -t (--test)      - Test mode, doesn't rewrite the file but shows how much space could be saved..".PHP_EOL;
		print " -q (--quiet)     - Doesn't print out any messages.".PHP_EOL;
		exit;
	}
	
	$dir = dirname(__FILE__);
	$dir .= ( array_key_exist($options["dir"]) ) ? $options["dir"] : $options["d"];
	if ( !is_dir($dir) ){
		print "Error : Directory doesn't exist");
		exit;
	}
	
	
	//
	print "Filesmush starting".PHP_EOL:
	print "Getting filelist".PHP_EOL;
		
	$dirs = array($dir);
	$images = array();
	do {
		$newDirs = array();
		foreach($dirs as $dir){
			$myDirectory = opendir($dir);

			while($entryName = readdir($myDirectory)) {
				$entryName = $dir."/".$entryName;
				print $entryName;
				if ( is_dir($entryName) ){
					$newDirs[] = $entryName;
				} elseif ( preg_match("~\.(jpe?g|gif|png)$~isU~",$entryName) ){
					$images[] = $entryName;
				}
			}

			closedir($myDirectory);
		}
		$dirs = $newDirs;
		unset($newDirs);
	} while( !empty($dirs) );
	
	
	
	/**
	 * Handles the printing of non error messages,
	 * got the idea from Tyler Hall's autosmush.
	 * 
	 * @since 1.0
	 */
	function qprint($string){
		if (!QUIET){
			print $string.PHP_EOL;
		}	
	}
	