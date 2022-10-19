<?php
namespace Tbx;


/**
 * @author Tropotek <info@tropotek.com>
 */
class Util
{

    /**
     * Sort an array of software versions....
     *
     * @see http://php.net/version_compare
     */
    public static function sortVersionArray(array &$array): bool
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
     */
    public static function incrementVersion(string $currVersion, string $maskVersion = '0.0.x', int $step = 1): string
    {
        preg_match('/^([0-9]+)\.([0-9]+)\.([a-z0-9]+)$/', $currVersion, $currParts);
        preg_match('/^([0-9]+)\.([0-9]+)\.([a-z0-9\-_]+)$/', $maskVersion, $maskParts);
        $ver = '1.0.0';
        if (count($maskParts) && version_compare($currParts[1] . '.' . $currParts[2], $maskParts[1] . '.' . $maskParts[2], '<')) {
            return $maskParts[1] . '.' . $maskParts[2] . '.0';
        }
        if (!empty($currParts[1])) {
            $x = 0;
            if (is_numeric($currParts[3]))
                $x = ($currParts[3] + $step);
            $ver = $currParts[1] . '.' . $currParts[2] . '.' . $x;
        }
        return $ver;
    }

    /**
     * Return the user executing the cmd home path
     */
    public static function getHomePath(): ?string
    {
        // Cannot use $_SERVER superglobal since that's empty during UnitUnishTestCase
        // getenv('HOME') isn't set on Windows and generates a Notice.
        $home = getenv('HOME');
        if (!empty($home)) {
            // home should never end with a trailing slash.
            $home = rtrim($home, '/');
        } elseif (!empty($_SERVER['HOMEDRIVE']) && !empty($_SERVER['HOMEPATH'])) {
            // home on windows
            $home = $_SERVER['HOMEDRIVE'] . $_SERVER['HOMEPATH'];
            // If HOMEPATH is a root directory the path can end with a slash. Make sure
            // that doesn't happen.
            $home = rtrim($home, '\\/');
        }
        return empty($home) ? NULL : $home;
    }

    public static function jsonEncode(mixed $obj): string
    {
        return self::jsonPrettyPrint(json_encode($obj));
    }

    public static function jsonDecode(string $json): mixed
    {
        $str = json_decode($json);
        return $str;
    }

    /**
     * Format JSON to text or HTML
     */
    public static function jsonPrettyPrint(string $json, bool $html = false): string
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