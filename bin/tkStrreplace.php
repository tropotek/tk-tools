#! /usr/bin/php
<?php
/*
 * @author Tropotek <info@tropotek.com>
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
    if (str_starts_with($param, '--ext=')) {
        $ext = substr($param, 6);
    }
    if (str_starts_with($param, '--noRecurse')) {
        $recurse = false;
    }
    if (str_starts_with($param, '--quiet')) {
        $quiet = true;
    }
    if (str_starts_with($param,'--help')) {
        echo $help;
        exit;
    }
}

/**
 * Simple recursive directory scanner
 */
function scan_directory(string $dir, string $ext = '*', bool $recurse = true): array
{
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
                if ($recurse)
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
 */
function get_file_extension(string $file): string
{
    $temp_vals = explode('.',$file);
    $file_ext = strtolower(rtrim(array_pop($temp_vals)));
    unset ($temp_vals);
    return ($file_ext);
}

if (is_file($path)) {
    if (!is_writable($path)) {
        error_log("File " . $path . " is not writable.");
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
 */
function fileReplace(string $file, string $find, string $replace): int
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
