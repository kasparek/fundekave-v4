<?php
class FAjax
{

    public $template     = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<FAjax><Response>\n{CONTENT}\n</Response></FAjax>";
    public $itemTemplate = '<Item target="{TARGET}" property="{PROP}"><![CDATA[{DATA}]]></Item>';

    public $data;
    public $responseData;
    public $errorsLater = false;
    public $redirecting = false;

    /**
     * SINGLETON
     * all functions are static
     */
    private static $instance;
    public static function &getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new FAjax();
        }
        return self::$instance;
    }

    public static function preprocessPost($data)
    {
        foreach ($data as $k => $v) {
            if (strpos($k, '-') !== false) {
                $kArr = explode('-', $k);
                if (count($kArr) == 3) {
                    $data[$kArr[0]][$kArr[1]][$kArr[2]] = $v;
                } else {
                    $data[$kArr[0]][$kArr[1]] = $v;
                }
            }
        }
        return $data;
    }

    public static function prepare($actionStr, $data, $options = array())
    {
        $arr    = explode('-', $actionStr);
        $mod    = $arr[0];
        $action = $arr[1];
        $ajax   = (isset($arr[2])) ? (true) : (false);
        if (empty($mod) || empty($action)) {
            //---system parameters missing
            FError::write_log("FAjax::prepare - missing parameters");
            FSystem::fin();
        }

        //---process
        $dataProcessed                   = array();
        $dataProcessed['__ajaxResponse'] = false;

        if (is_array($data)) {
            $dataProcessed = $data;
        } else if (strpos($data, '<FXajax>') === 0) {
            $dataXML = stripslashes($data);
            $xml     = new SimpleXMLElement($dataXML);
            foreach ($xml->Request->Item as $item) {
                $k = (String) $item['name'];
                $v = (String) $item;
                if (isset($dataProcessed[$k])) {
                    if (is_array($dataProcessed[$k])) {
                        $dataProcessed[$k][] = $v;
                    } else {
                        $dataProcessed[$k] = array($dataProcessed[$k], $v);
                    }
                } else {
                    $dataProcessed[$k] = $v;
                }
            }
        } else {
            $arr = explode(';', $data);
            foreach ($arr as $row) {
                $pair = explode('=', $row);
                if (!isset($pair[1])) {
                    $pair = explode(':', $row);
                }
                //backward compatibility
                $dataProcessed[$pair[0]] = $pair[1];
            }
        }
        if ($ajax == true) {
            $dataProcessed['__ajaxResponse'] = true;
        }

        $dataProcessed = FAjax::preprocessPost($dataProcessed);
        if (isset($options['data'])) {
            $dataProcessed = array_merge($dataProcessed, $options['data']);
        }

        FProfiler::write('FAJAX XML INPUT PROCESSING COMPLETE');
        $fajax       = FAjax::getInstance();
        $fajax->data = $dataProcessed;
        //---process k - set pageparam on user if needed
        if (isset($fajax->data['k'])) {
            FSystem::processK($fajax->data['k']);
        }
    }
    public static function process($actionStr, $data, $options = array())
    {
        $fajax = FAjax::getInstance();

        $arr    = explode('-', $actionStr);
        $mod    = $arr[0];
        $action = $arr[1];
        $ajax   = (isset($arr[2])) ? (true) : (false);
        if (empty($mod) || empty($action)) {
            //---system parameters missing
            FError::write_log("FAjax::prepare - missing system parameters");
            FSystem::fin();
        }

        //---dealing with ajax requests
        $className = 'fajax_' . ucfirst($mod);

        if (class_exists($className)) {
            if (call_user_func(array($className, 'validate'), array_merge($fajax->data, array('function' => $action)))) {
                FProfiler::write('FAJAX MODULE VALIDATED ' . $className . '::' . $action);
                call_user_func(array($className, $action), $fajax->data);
                FProfiler::write('FAJAX MODULE COMPLETE');
                FSystem::superInvalidateFlush();
                if ($ajax === true) {
                    if ($fajax->errorsLater === false) {
                        $arrMsg = FError::get();
                        if (!empty($arrMsg)) {
                            $arr = array();
                            foreach ($arrMsg as $k => $v) {
                                $arr[] = $k . (($v > 1) ? (' [' . $v . ']') : (''));
                            }

                            foreach ($arr as $v) {
                                FAjax::addResponse('call', 'msg', 'danger,' . $v);
                            }

                            FError::reset();
                        }
                        $arrMsg = FError::get(1);
                        if (!empty($arrMsg)) {
                            $arr = array();
                            foreach ($arrMsg as $k => $v) {
                                $arr[] = $k . (($v > 1) ? (' [' . $v . ']') : (''));
                            }

                            foreach ($arr as $v) {
                                FAjax::addResponse('call', 'msg', 'success,' . $v);
                            }

                            FError::reset(1);
                        }
                    }
                }
                FProfiler::write('FAJAX ERROR OUTPUT COMPLETE');
            } else {
                //redirect to same page because of user does not have permission to access this page
                $user = FUser::getInstance();
                if ($user->pageParam) {
                    FAjax::redirect(FSystem::getUri('', '', ''));
                } else {
                    FAjax::redirect(FSystem::getUri('', HOME_PAGE, ''));
                }

            }

            //---send response
            if ($ajax === true) {
                $ret = FAjax::buildResponse();
                header("content-type: text/xml");
                echo FSystem::superVars($ret);
                FProfiler::write('FAJAX RESPONSE COMPLETE');
                FSystem::fin();
            } else {
                //check response and if include redirect then redirect
                if ($fajax->redirecting == true) {
                    foreach ($fajax->responseData as $response) {
                        if ($response['PROP'] == 'redirect') {
                            FProfiler::write('FAJAX PROCESSING COMPLETE - REDIRECTING');
                            FHTTP::redirect($response['DATA']);
                        }
                    }
                }
                FAjax::resetResponse();
            }
        } else {
            header("content-type: text/xml");
            FAjax::addResponse('call', 'msg', 'danger,Illegal request');
            FError::write_log("FAjax::prepare - non existent class module: " . $className);
            $ret = FAjax::buildResponse();
            echo FSystem::superVars($ret);
            FProfiler::write('FAJAX RESPONSE COMPLETE');
            FSystem::fin();
        }
        FProfiler::write('FAJAX PROCESSING COMPLETE ajax=' . ($ajax ? 'true' : 'false'));
    }

    public static function errorsLater()
    {
        $fajax              = FAjax::getInstance();
        $fajax->errorsLater = true;
    }

    public static function redirect($url)
    {
        $fajax                 = FAjax::getInstance();
        $fajax->errorsLater    = true;
        $fajax->redirecting    = true;
        $fajax->responseData[] = array('TARGET' => 'call', 'PROP' => 'redirect', 'DATA' => $url);
    }

    public static function isRedirecting()
    {
        $fajax = FAjax::getInstance();
        return $fajax->redirecting;
    }

    public static function addResponse($target, $property, $value = '')
    {
        $fajax                 = FAjax::getInstance();
        $fajax->responseData[] = array('TARGET' => $target, 'PROP' => $property, 'DATA' => $value);
    }

    public static function resetResponse()
    {
        $fajax               = FAjax::getInstance();
        $fajax->responseData = array();
    }

    public static function buildResponse()
    {
        $fajax = FAjax::getInstance();
        $rows  = array();
        //---create new data
        if (!empty($fajax->responseData)) {
            foreach ($fajax->responseData as $responseData) {
                $row = $fajax->itemTemplate;
                foreach ($responseData as $k => $v) {
                    $row = str_replace('{' . $k . '}', $v, $row);
                }

                $rows[] = $row;
            }
        }
        //---process original data
        foreach ($fajax->data as $k => $v) {
            if ($k == 'call') {
                if (!is_array($v)) {
                    $v = array($v);
                }

                foreach ($v as $funcName) {
                    $fArr   = explode(';', $funcName);
                    $rows[] = str_replace(array('{TARGET}', '{PROP}', '{DATA}'), array($k, $fArr[0], isset($fArr[1]) ? $fArr[1] : ''), $fajax->itemTemplate);
                }
            }
        }
        FAjax::resetResponse();
        return str_replace('{CONTENT}', implode("\n", $rows), $fajax->template);
    }
}
