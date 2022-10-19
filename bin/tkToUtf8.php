#! /usr/bin/php7.4
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
    Usage: " . basename($argv[0]) . " --path=[FilesDir] --dryrun --ext=[ext]

   --path=[path]             - Change from the current working path
   --ext=php                 - The field extension to preform replace on. (Default is 'php')
   --dryrun                  - do not modify the files. Goot to test files encodings.

";

if ($argc < 2 || $argc > 4) {
    echo $help;
    exit;
}

$startPath = getcwd();
$ext = 'php';
$dryRun = false;

foreach ($argv as $param) {
    if (strtolower(substr($param, 0, 7)) == '--path=') {
        $startPath = substr($param, 7);
        if (!is_dir($startPath)) {
            echo 'Path does not exist: ' . $path;
            exit(-1);
        }
    }
    if (strtolower(substr($param, 0, 6)) == '--ext=') {
        $ext = substr($param, 6);
    }
    if (strtolower(substr($param, 0, 8)) == '--dryrun') {
        $dryRun = true;
    }
    if (strtolower(substr($param, 0, 6)) == '--help') {
        echo $help;
        exit;
    }
}
define('ENC_UTF8', 'UTF-8');


try {

    echo "-------------------------\n";
    echo "--  Encoding to UTF-8  --\n";
    echo "-------------------------\n";
    echo "Path: " . $startPath . "\n";
    if ($dryRun) {
        echo "       - DRY RUN -      \n";
    }
    echo "\n";
    encode($startPath, $ext);
    echo "\n";
} catch (Exception $e) {
    print(basename($argv[0]) . ": \n" . $e->__toString());
    exit(-1);
}

function encode($dir, $ext = 'php')
{
    global $dryRun, $startPath;

    if (is_dir($dir)) {
        foreach (new DirectoryIterator($dir) as $res) {
            if ($res->isDot() || substr($res->getFilename(), 0, 1) == '_' || $res->getFilename() == 'bin' ) {
                continue;
            }
            if ($res->isFile()) {
                $file = $res->getFilename();
                $fext = substr($file, strrpos($file, '.')+1);
                if ($fext != $ext) {
                    continue;
                }

                $path = $res->getRealPath();
                $data = file_get_contents($path);
                $trunkPath = '...' . str_replace(rtrim($startPath, '/'), '', $path);
                $enc = mb_detect_encoding($data);
                $tag = '-';


                if (trim(mb_detect_encoding($data)) != ENC_UTF8) {
                    $utf = fixEncoding($data);
                    if (!$dryRun) {
                        $tag = '+';
                        file_put_contents($path, $utf);
                    }
                }
                printf('%s  %-10s %s', $tag, $enc, $trunkPath );
                echo "\n";

            } elseif ($res->isDir()) {
                encode($res->getRealPath(), $ext);
            }
            unset($res);
        }
    }
}

function fixEncoding($x)
{
    //$utf = iconv(mb_detect_encoding($data), ENC_UTF8, $data);
    //$utf = mb_convert_encoding($data, ENC_UTF8, mb_detect_encoding($data));

    $utf = utf8_encode($x);
    //    //$str = str_replace("\xef\xbb\xbf", '', $str); // remove BOM (Byte-Order Mark)
    if (substr($utf, 3) != "\xEF\xBB\xBF") {
        $utf = "\xEF\xBB\xBF".$utf;

    }

    return $utf;
}