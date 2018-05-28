<?php
/**
 * Created by PhpStorm.
 * User: mifsudm
 * Date: 2/4/14
 * Time: 12:19 PM
 */

/**
 * @return string
 * @deprecated Remove when all commands are moved to the tk console command
 */
function vd()
{
    $output = '';
    $args = func_get_args();

    foreach ($args as $var) {
        $objStr = $var;
        if ($var === null) {
            $objStr = 'NULL';
        } else if (is_bool($var)) {
            $objStr = $var == true ? 'true' : 'false';
        } else if (is_string($var)) {
            $objStr = str_replace("\0", '|', $var);
        } else if (is_object($var) || is_array($var)) {
            $objStr = str_replace("\0", '|', print_r($var, true));
        }
        $type = gettype($var);
        if ($type == 'object') {
            $type = get_class($var);
        }
        $output .= "\nvd({" . $type . "}): " . $objStr . "";
    }
    echo "---------------------------------------------------------";
    echo $output . "\n";
    debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    echo "---------------------------------------------------------\n";
    return $output;
}

/**
 * Format JSON to text or HTML
 *
 * @param $json
 * @param bool $html
 * @return string
 * @deprecated use \Tbx\Utils::jsonPrettyPrint()
 */
function jsonPrettyPrint($json, $html = false)
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






