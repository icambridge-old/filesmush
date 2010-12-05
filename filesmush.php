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
	

	require_once "lib/class.smushit.php";
	date_default_timezone_set("America/Los_Angeles");
	error_reporting(-1);
	ini_set("display_errors",1); 
	 
	$shortOpts = "ctqd:";
	$longOpts = array("cron","quiet","help","dir:","test");
	
	$options  = getopt($shortOpts, $longOpts);
	
	if (array_key_exists("help", $options) || $GLOBALS["argc"] == 1){
		print "Filesmush".PHP_EOL;
		print "===================".PHP_EOL;
		print " -d (--dir) <dir> - The directory that is to be backed up.".PHP_EOL;
		print " -t (--test)      - Test mode, doesn't rewrite the file but shows how much space could be saved..".PHP_EOL;
		print " -q (--quiet)     - Doesn't print out any messages.".PHP_EOL;
		print " -c (--cron)      - Crontask, only deal with files less than a day old.".PHP_EOL;
		exit;
	}
	
	$dir = dirname(__FILE__)."/";
	$dir .= ( array_key_exists("dir",$options) ) ? $options["dir"] : $options["d"];
	if ( !is_dir($dir) ){
		print "Error : ".$dir." Directory doesn't exist";
		exit;
	}
	
	if ( array_key_exists("t",$options) || array_key_exists("test",$options) ){
		define("TEST_MODE",true);
	} else {
		define("TEST_MODE",false);
	}
	
	if ( array_key_exists("q",$options) || array_key_exists("quiet",$options) ){
		define("QUIET_MODE",true);
	} else {
		define("QUIET_MODE",false);
	}
	
	if ( array_key_exists("c",$options) || array_key_exists("cron",$options) ){
		define("CRON_MODE",true);
	} else {
		define("CRON_MODE",false);
	}
	
	//
	print "Filesmush starting".PHP_EOL;
	print "Getting filelist from ".$dir.PHP_EOL;
		
	$dirs = array($dir);
	$images = array();
	$timeFilter = time() - (60 * 60 * 24);
	
	do {
		$newDirs = array();
		foreach($dirs as $dir){
			$myDirectory = opendir($dir);

			while($entryName = readdir($myDirectory)) {
				if ($entryName == "." || $entryName == ".."){
					continue;
				}
				
				$entryName = $dir."/".$entryName;
				
				if ( is_dir($entryName) ){
					$newDirs[] = $entryName;
				} elseif ( preg_match("~\.(jpe?g|gif|png)$~isU",$entryName) ){
				
					if ( ( !CRON_MODE ) || ( $timeFilter < filemtime($entryName)) ){
						$images[] = $entryName;
					}
				}
			}

			closedir($myDirectory);
		}
		$dirs = $newDirs;
		unset($newDirs);
	} while( !empty($dirs) );
	
	$smushit = new SmushIt();
	$totalUncompressed = 0;
	$totalCompressed = 0;
	$totalFiles = 0;
	foreach($images as $image){
	
		$smushit->smushFile($image);
		
		if ( !$smushit->savings ){
			qprint($image." - Already compressed");
			continue;
		} 
		$totalFiles++; 
		$totalUncompressed += $smushit->size;
        $totalCompressed += $smushit->compressedSize;
		qprint($image." - smushed - saved ".$smushit->savings."%");
		if ( TEST_MODE ){
			continue;
		}
		
		$fp = fopen($image, 'w+');
		$ch = curl_init($smushit->compressedUrl);
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_exec($ch);
		curl_close($ch);
		fclose($fp);
		
	}
	
	qprint("Number of smused files  : ".$totalFiles);
	qprint("Total uncompressed size : ".bytes2str($totalUncompressed));
	qprint("Total compressed size   : ".bytes2str($totalCompressed));
	qprint("Total savings           : ".bytes2str($totalUncompressed-$totalCompressed));
	qprint("Totaly Savings %        : ".($totalUncompressed === 0) ? 0 : round(($totalUncompressed - $totalCompressed) / $totalUncompressed * 100, 2)  . "%"); 
	/**
	 * Handles the printing of non error messages,
	 * got the idea from Tyler Hall's autosmush.
	 * 
	 * @since 1.0
	 */
	function qprint($string){
		if (!QUIET_MODE){
			print $string.PHP_EOL;
		}	
	}
	
	/**
	 * Copied and pasted from Tyler Hall's autosmush.
	 * Displays the bytes in a string so people can
	 * easier understand them.
	 */
	function bytes2str($val, $round = 0){
	
        $unit = array('','K','M','G','T','P','E','Z','Y');
        while($val >= 1000)
        {
            $val /= 1024;
            array_shift($unit);
        }
        return round($val, $round) . array_shift($unit) . 'B';
    }