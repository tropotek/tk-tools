<?php
namespace Tbx;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2018 Michael Mifsud
 */
class Utils
{

    /**
     * Sort an array of software versions....
     *
     * @see http://php.net/version_compare
     * @param array $array
     * @return bool true on success or false on failure.
     */
    public static function sortVersionArray(&$array)
    {
        return usort($array, function ($a, $b) {
            if ($a == $b) {
                return 0;
            } else if (version_compare($a, $b, '<')) {
                return -1;
            } else {
                return 1;
            }
        });
    }

    /**
     * Compare a current version to a version mask and return
     * the next version number.
     *
     * Increments the patch $currentVersion by the $step value
     * EG:
     *  o 1.3.9 will become 1.3.10 if no other params are supplied.
     *  o If a $maskVersion of 2.1.x is supplied with a $currentVersion
     *    of 1.3.9 then the result will be 2.1.0
     *  o If a $maskVersion of 1.3.x is supplied with a $currentVersion
     *    of 1.3.9 then the result will be 1.3.10
     *
     * @param string  $currVersion The current version to increment
     * @param string  $maskVersion The proposed mask version. Default 0.0.x
     * @param integer $step The number to increment the version by. Default 1
     * @return string
     */
    public static function incrementVersion($currVersion, $maskVersion = '0.0.x', $step = 1)
    {
        preg_match('/^([0-9]+)\.([0-9]+)\.([0-9]+)$/', $currVersion, $currParts);
        preg_match('/^([0-9]+)\.([0-9]+)\.([0-9]+)$/', $maskVersion, $maskParts);
        if (count($maskParts) && version_compare($currParts[1] . '.' . $currParts[2], $maskParts[1] . '.' . $maskParts[2], '<')) {
            return $maskParts[1] . '.' . $maskParts[2] . '.0';
        }
        $ver = $currParts[1] . '.' . $currParts[2] . '.' . ($currParts[3] + $step);
        return $ver;
    }

    /**
     * Format JSON to text or HTML
     *
     * @param $json
     * @param bool $html
     * @return string
     */
    public static function jsonPrettyPrint($json, $html = false)
    {
        $tabcount = 0;
        $result = '';
        $inquote = false;
        $ignorenext = false;

        if ($html) {
            $tab = "&nbsp;&nbsp;&nbsp;&nbsp;";
            $newline = "<br/>";
        } else {
            $tab = "    ";
            $newline = "\n";
        }

        for ($i = 0; $i < strlen($json); $i++) {
            $char = $json[$i];
            if ($ignorenext) {
                $result .= $char;
                $ignorenext = false;
            } else {
                switch ($char) {
                    case ':':
                        $result .= $char . (!$inquote ? " " : "");
                        break;
                    case '{':
                        if (!$inquote) {
                            $tabcount++;
                            $result .= ' ' . $char . $newline . str_repeat($tab, $tabcount);
                        } else {
                            $result .= $char;
                        }
                        break;
                    case '}':
                        if (!$inquote) {
                            $tabcount--;
                            $result = trim($result) . $newline . str_repeat($tab, $tabcount) . $char;
                        } else {
                            $result .= $char;
                        }
                        break;
                    case '[':
                        if (!$inquote) {
                            $tabcount++;
                            $result .= ' ' . $char . $newline . str_repeat($tab, $tabcount);
                        } else {
                            $result .= $char;
                        }
                        break;
                    case ']':
                        if (!$inquote) {
                            $tabcount--;
                            $result = trim($result) . $newline . str_repeat($tab, $tabcount) . $char;
                        } else {
                            $result .= $char;
                        }
                        break;
                    case ',':
                        if (!$inquote) {
                            $result .= $char . $newline . str_repeat($tab, $tabcount);
                        } else {
                            $result .= $char;
                        }
                        break;
                    case '"':
                        $inquote = !$inquote;
                        $result .= $char;
                        break;
//                case '\\':
//                    if ($inquote) $ignorenext = true;
//                    $result .= $char;
//                    break;
                    default:
                        $result .= $char;
                }
            }
        }
        $result = str_replace('"_empty_": ', '"": ', $result);
        $result = str_replace('\/', '/', $result);

        return $result;
    }

}