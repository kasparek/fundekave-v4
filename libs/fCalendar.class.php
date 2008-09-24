<?php

/**
 *  File: calendar.php | (c) dynarch.com 2004
 *  Distributed as part of "The Coolest DHTML Calendar"
 *  under the same terms.
 *  -----------------------------------------------------------------
 *  This file implements a simple PHP wrapper for the calendar.  It
 *  allows you to easily include all the calendar files and setup the
 *  calendar by instantiating and calling a PHP object.
 */

define('NEWLINE', "\n");

class fCalendar {
    var $calendar_lib_path;
    var $calendar_file;
    var $calendar_lang_file;
    var $calendar_setup_file;
    var $calendar_theme_file;
    var $calendar_options;

    function __construct($calendar_lib_path = './js/calendar/',$lang= 'cs-utf8',$theme= 'skins/aqua/theme',$stripped= true) {
        if ($stripped) {
            $this->calendar_file = 'calendar_stripped.js';
            $this->calendar_setup_file = 'calendar-setup_stripped.js';
        } else {
            $this->calendar_file = 'calendar.js';
            $this->calendar_setup_file = 'calendar-setup.js';
        }
        $this->calendar_lang_file = 'lang/calendar-' . $lang . '.js';
        $this->calendar_theme_file = $theme.'.css';
        $this->calendar_lib_path = preg_replace('/\/+$/', '/', $calendar_lib_path);
        $this->calendar_options = array('ifFormat' => '%Y/%m/%d',
                                        'daFormat' => '%Y/%m/%d');
    }
    function set_option($name, $value) {
        $this->calendar_options[$name] = $value;
    }
    function get_load_files_code() {
    
        return '<link rel="stylesheet" type="text/css" media="all" href="' . $this->calendar_lib_path . $this->calendar_theme_file . '" />' . NEWLINE 
        . '<script type="text/javascript" src="' . $this->calendar_lib_path . $this->calendar_file . '"></script>' . NEWLINE
        . '<script type="text/javascript" src="' . $this->calendar_lib_path . $this->calendar_lang_file . '"></script>' . NEWLINE
        . '<script type="text/javascript" src="' . $this->calendar_lib_path . $this->calendar_setup_file . '"></script>';
    }
    function _make_calendar($other_options = array()) {
        $js_options = $this->_make_js_hash(array_merge($this->calendar_options, $other_options));
        return( '<script type="text/javascript">Calendar.setup({' . $js_options . '});</script>' );
    }
    function make_input_field($cal_options = array(), $field_attributes = array()) {
        $id = $this->_gen_id();
        $attrstr = $this->_make_html_attr(array_merge($field_attributes, array('id'   => $this->_field_id($id), 'type' => 'text')));
        $ret = '<input ' . $attrstr .'/>'
        . '<a href="#" id="'. $this->_trigger_id($id) . '">'
        . '<img align="middle" border="0" src="' . $this->calendar_lib_path . 'img.gif" alt="" /></a>';
        $options = array_merge($cal_options, array('inputField' => $this->_field_id($id), 'button' => $this->_trigger_id($id)));
        return $ret . $this->_make_calendar($options);
    }
    function make_button($inputFieldId,$cal_options = array('firstDay'=>1,'showsTime' => false,'showOthers'  => false,'ifFormat' => '%d.%m.%Y','timeFormat'=> '24')) {
        $ret = '<a href="#" id="'. $this->_trigger_id($inputFieldId) . '"><img class="calendarico" src="' . $this->calendar_lib_path . 'img.gif" /></a>';
        $options = array_merge($cal_options, array('inputField' => $inputFieldId, 'button' => $this->_trigger_id($inputFieldId)));
        $ret .= $this->_make_calendar($options);
        return $ret;
    }

    /// PRIVATE SECTION
    function _field_id($id) { return 'f-calendar-field-' . $id; }
    function _trigger_id($id) { return 'f-calendar-trigger-' . $id; }
    function _gen_id() { static $id = 0; return ++$id; }
    function _make_js_hash($array) {
        $jstr = '';
        reset($array);
        while (list($key, $val) = each($array)) {
            if (is_bool($val)) $val = $val ? 'true' : 'false';
            else if (!is_numeric($val)) $val = '"'.$val.'"';
            if ($jstr) $jstr .= ',';
            $jstr .= '"' . $key . '":' . $val;
        }
        return $jstr;
    }
    function _make_html_attr($array) {
        $attrstr = '';
        reset($array);
        while (list($key, $val) = each($array)) { $attrstr .= $key . '="' . $val . '" '; }
        return $attrstr;
    }
};
?>