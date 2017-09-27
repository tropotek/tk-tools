#! /usr/bin/php
<?php
/*
 * Tropotek Web Development Tools.
 * 
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2005 Michael Mifsud
 */

$argv = $_SERVER['argv'];
$argc = $_SERVER['argc'];
$help = "
Replace text in files.
   Usage: ".basename($argv[0])." <Path> '<FindText>' '<ReplaceText>' [--ext='php'] [--noRecurse] [--quiet]
   
Used to replace text within a file or recurse through a directory and replace on multiple files:

   <Path>                    - The path of the file or directory to perform replace on.
   <FindText>                - The text to find.
   <ReplaceText>             - The text to insert into the file.
   --ext='php'               - The field extension to preform replace on. (Default is 'php')
   --noRecurse               - Do not recurse into directory
   --quiet                   - Do not use verbose output
   
Example:
   ".basename($argv[0])." . 'href=\"default.htm\"' 'href=\"index.htm\"' --ext=html
   ".basename($argv[0])." /home/user/directory/file.html 'href=\"default.htm\"' 'href=\"index.htm\"'
   
NOTE: Does not follow symbolic link directories.

Copyright (c) 2002
Report bugs to info@tropotek.com
Tropotek home page: <http://www.tropotek.com/>
";

echo "\n";
if ($argc < 4 || $argc > 5){
	echo $help;
	exit;
}

$retVal = 0;
$path = $argv[1];
$findText = $argv[2];
$replaceText = $argv[3];
$recurse = true;
$ext = 'php';
$quiet = false;

$filesChanged = 0;
$stringsReplaced = 0;

foreach ($argv as $param) {
    if (substr($param, 0, 6) == '--ext=') {
        $ext = substr($param, 6);
    }
    if (substr($param, 0, 11) == '--noRecurse') {
        $recurse = false;
    }
    if (substr($param, 0, 7) == '--quiet') {
        $quiet = true;
    }
    if (strtolower(substr($param, 0, 6)) == '--help') {
        echo $help;
        exit;
    }
}

/**
 * Simple recursive directory scanner
 *
 * @param string $dir The directory to scan
 * @param string $ext The file extension to grab *=all
 * @param bool $recurse If true, recurse into subdirectories
 * @return array An array of files encountered
 */
function scan_directory($dir, $ext = '*', $recurse = true) {
    $files = array ();
    if ($handle = opendir($dir)) {
        while (false !== ($file = readdir($handle))) {
            if ($file == '.' || $file == '..' ||
              $file == 'CVS' || preg_match('|^\.|', $file)) {
                continue;
            }
            if (is_link($dir . '/' . $file)) {
                continue;
            }
            if (is_dir ($dir . '/' . $file)) {
                if ($recurse == true)
                    $files = array_merge($files, scan_directory ($dir . '/' . $file, $ext));
            } else {
                if($ext == get_file_extension($file) || $ext == '*') {
                    $files[] = $dir . '/' . $file;
                }
            }
        }
        closedir($handle);
    }
    return $files;
}

/**
 * Return the extension of a file
 *
 * @param string $file the file name to examine
 * @return string Tthe file's extension
 */
function get_file_extension($file) {
    $temp_vals = explode('.',$file);
    $file_ext = strtolower(rtrim(array_pop($temp_vals)));
    unset ($temp_vals);
    return ($file_ext);
}

if (is_file($path)) {
    if (!is_writable($path)) {
        error("File " . $path . " is not writable.");
        exit(-1);
    }
    $count = fileReplace($path, $findText, $replaceText);
    if ($count > 0) {
        $filesChanged ++;
        $stringsReplaced += $count;
    }
} elseif (is_dir($path)) {
    if (substr($path, -1) == '/' || substr($path, -1) == '\\') {
        $path = substr($path, 0, -1);
    }
    $fileList = scan_directory($path, $ext, $recurse);
    foreach($fileList as $file) {
        if (is_writable($file)) {
            $count = fileReplace($file, $findText, $replaceText);
            if ($count > 0) {
                if (!$quiet) {
                    printf(" - %s  [%d] \n", $file, $count);
                }
                $filesChanged ++;
                $stringsReplaced += $count;
            }
        }
    }
}

/**
 * Find and replace some text in a file
 *
 * @param string $file
 * @param string $find
 * @param string $replace
 * @return int
 */
function fileReplace($file, $find, $replace) 
{
    $count = 0;
    $buff = file_get_contents($file, false);
    $newBuff = str_replace($find, $replace, $buff, $count);
    if ($count > 0) {
        file_put_contents($file, $newBuff);
    }
    return $count;
}

if (!$quiet) {
    printf("\n  String Replacer. Ver: 0.1\n");
    printf("------------------------------\n");
    printf("Files Updated:             %d\n", $filesChanged);
    printf("Strings Updated:           %d\n", $stringsReplaced);
    echo "\n\n";
}
