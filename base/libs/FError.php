<?php
class FError
{
    /**
     *
     * @param $langkey - string or langkey
     * @param $type - 0-error,1-info
     * @return void
     */
    public static function add($langkey, $type = 0)
    {
        $pointer = &$_SESSION['errormsg'][$type];
        if (!isset($pointer[$langkey])) {
            $pointer[$langkey] = 0;
        }

        $pointer[$langkey]++;
    }

    public static function reset($type = 0)
    {
        $_SESSION["errormsg"][$type] = array();
    }

    public static function get($type = 0)
    {
        if (!isset($_SESSION["errormsg"][$type])) {
            $_SESSION["errormsg"][$type] = array();
        }

        return $_SESSION["errormsg"][$type];
    }

    public static function is($type = 0)
    {
        if (!empty($_SESSION["errormsg"][$type])) {
            return true;
        } else {
            return false;
        }

    }

    public static function debug($die = true)
    {
        print_r($_SESSION["errormsg"][0]);
        print_r($_SESSION["errormsg"][1]);
        if ($die === true) {
            die();
        }

    }

    //GLOBAL ERROR HANDLING
    private static $phplog;
    private static $starttime;

    public static function getmicrotime()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float) $usec + (float) $sec);
    }

    public static function init($filename)
    {
        self::$phplog    = $filename;
        self::$starttime = FError::getmicrotime();
        register_shutdown_function('FError::shutdownFunction');
        set_error_handler("FError::handle_error");
    }

    public static function write_log($errText)
    {
        if (is_writable(self::$phplog)) {
            if ($handle = fopen(self::$phplog, "a")) {
                fwrite($handle, date('Y-m-d H:i:s') . ';' . (round(FError::getmicrotime() - self::$starttime, 4)) . ';' . round(memory_get_usage() / 1024) . '/' . round(memory_get_peak_usage() / 1024) . "\n" . $errText . "\n\n");
                fclose($handle);
            }
        }

    }

    public static function shutDownFunction()
    {
        $e = error_get_last();
        if ($e['message']) {
            FError::write_log($e['type'] . ':' . $e['message'] . ' in ' . $e['file'] . ' on line=' . $e['line']);
        }

    }

    public static function handle_error($errno, $errstr, $errfile, $errline)
    {
        switch ($errno) {
            case E_WARNING:
                throw new RuntimeException($errstr, $errno);
                break;
            default:
                FError::write_log("$errstr in $errfile on line $errline");
                break;
        }
    }
}
