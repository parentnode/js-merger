<?php
// wtf-js-merger for WhatTheFramework JavaScript segments
// copyright 2013 Martin Kaestel Nielsen, think.dk and hvadhedderde under MIT-License
// http://whattheframework.org

ini_set("auto_detect_line_endings", true);
error_reporting(E_ALL);

$access_item = array();
$access_default = "page,list";

$access_item = false;

if(isset($read_access) && $read_access) {
	return;
}


// merge-path info required from Apache conf
if(isset($_SERVER["JS_PATH"])) {
	$path = $_SERVER["JS_PATH"];
}
else {
	print "No JS_PATH?";
	exit();
}


// INCLUDE LICENSE TEXT???
$license = $path."/lib/license.txt";



$file_include[] = $path."/lib/seg_basic_include.js";
$file_output[] = $path."/seg_basic.js";

$file_include[] = $path."/lib/seg_mobile_light_include.js";
$file_output[] = $path."/seg_mobile_light.js";

$file_include[] = $path."/lib/seg_mobile_include.js";
$file_output[] = $path."/seg_mobile.js";

$file_include[] = $path."/lib/seg_mobile_touch_include.js";
$file_output[] = $path."/seg_mobile_touch.js";

$file_include[] = $path."/lib/seg_tablet_include.js";
$file_output[] = $path."/seg_tablet.js";

$file_include[] = $path."/lib/seg_desktop_include.js";
$file_output[] = $path."/seg_desktop.js";

$file_include[] = $path."/lib/seg_desktop_light_include.js";
$file_output[] = $path."/seg_desktop_light.js";

$file_include[] = $path."/lib/seg_desktop_ie_include.js";
$file_output[] = $path."/seg_desktop_ie.js";

$file_include[] = $path."/lib/seg_tv_include.js";
$file_output[] = $path."/seg_tv.js";

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="da" lang="da">
<head>
	<!-- All material protected by copyrightlaws (as if you didnt know) //-->
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>-- parse javascript --</title>
	<style type="text/css">
	* {
		font-family: monaco;
		font-size: 10px;
	}
	.good {
		color: green;
	}
	.bad {
		color: red;
	}
	.notminified {
		color: green; font-weight: normal;
	}
	.file {
		color: black; font-weight: bold;
	}
	.file div {display: none;}
	.open div {display: block;}
	</style>
</head>
<body>

<?php

$_ = '';

foreach($file_include as $index => $source) {


	if(file_exists($source)) {

//		$fp = @fopen($source, "r");
		$includes = file($source);

	}
	else {
		
		$includes = false;
		
	}

	// read include file
	if(!$includes) {
		$_ .= $source . " -> " . $file_output[$index] . "<br />";
		$_ .= "No include file<br /><br /><hr />";
	}
	else {

		$files = array();

		foreach($includes as $include) {
//			print htmlspecialchars($include) . "<br>";
			if(strpos($include, "//") !== 0 && preg_match("/src=\"([a-zA-Z0-9\.\/_\:\-\=\?]+)\"/i", $include, $matches)) {
//				print "no c:$include<br>".$matches[1]."<br>";

				// external include
				if(preg_match("/http[s]?:\/\//i", $matches[1])) {
					$filepath = $matches[1];
				}
				// local, absolute include
				else if(strpos($matches[1], "/") === 0) {
					$filepath = "http://".$_SERVER["HTTP_HOST"].$matches[1];
				}
				// relative include
				// JS include can only be relative if they are always included from same level dir
				// if relative path is found here, expect included file to be located in $path/lib
				else {
					
//					$filepath = "../".$matches[1];
					$filepath = $path."/lib/".basename($matches[1]);
				}
//				print $filepath."<br>";
				$files[] = $filepath;
			}
			else {
//				print "c:$include<br>";
			}
		}


		$fp = @fopen($file_output[$index], "w+");

		if(!$fp) {
			print "make files writable first";
			exit;
		}

		if(file_exists($license)) {
			fwrite($fp, "/*\n");
			fwrite($fp, file_get_contents($license)."\n");
			fwrite($fp, "wtf-js-merged @ ".date("Y-m-d h:i:s")."\n");
			fwrite($fp, "*/\n");
		}

		// write compiled js
		$include_size = 0;
		$a = '';

		foreach($files as $file) {

			fwrite($fp, "\n");
			fwrite($fp, "/*".basename($file)."*/\n");

			// calculate pre filesize
			$file_size = strlen(join('', file($file)));
			$include_size += $file_size ? $file_size : 0;
			$minisize = 0;

			$a .= '<div class="file" onclick="this.className = this.className.match(/open/) ? \'file\' : \'file open\'">' . $file . " (".$file_size;

			$lines = file($file);
			$switch = false;
			foreach($lines as $linenumber => $line) {

				$minified = "";
				if(trim($line)) {

					if(!$switch && strpos($line, "/*") !== false) {
						$com_line = $index;
						$com_s_pos = strpos($line, "/*");
						$switch = true;
					}

					if(!$switch && strpos(trim($line), "//") === 0) {
//					if(!$switch && strpos($line, "//") !== false && strpos($line, "://") != strpos($line, "//")-1) {
						$minified = substr($line, 0, strpos($line, "//"));
					}
					else if(!$switch && trim($line)) {
						$minified = $line;
					}
					else if($switch && strpos($line, "*/") !== false) {

						$com_e_pos = strpos($line, "*/");
						$switch = false;
						$comment = substr($line, $com_s_pos, ($com_e_pos-$com_s_pos+2));
						$minified = str_replace(substr($line, $com_s_pos, ($com_e_pos-$com_s_pos+2)), "", $line);
					}

					$com_s_pos = 0;

					if(trim($minified)) {
						fwrite($fp, $minified);
						$minisize += strlen($minified);
						
					}
				}


				if($line == $minified) {
					$a .= '<div class="notminified"><code>'.$linenumber.':'.htmlentities($minified).'</code></div>';
				}
				else {
					$a .=  '<div class="minified"><span class="bad">'.$linenumber.':'.$line.'</span><span class="good">' . htmlentities($minified) . '</span></div>';
				}

			}
			$a .= "/".$minisize.")";

			$a .=  '</div>';
			
		}
		
		fclose($fp);

		$_ .= $source . " ($include_size bytes) -> " . $file_output[$index] . " (".filesize($file_output[$index])." bytes)<br />";
		$_ .= count($includes) . " include files<br /><br />";

		$_ .= $a."<br /><br /><hr />";

	}


}

print $_;

?>
</body>
</html>
