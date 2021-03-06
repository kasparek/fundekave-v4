<?php
class fCaptcha {
    function &init($confArray=array()) {    
                
        $tempfolder = (isset($confArray['tempFolder']))?($confArray['tempFolder']):('./data/b2evo_captcha_tmp/');
        
        $ftpWebPath = (isset($confArray['libPath']))?($confArray['libPath']):(ROOT.ROOT_LIBS);
        
        $urlWebPath = '/';
        
        $TTF_folder = $ftpWebPath.'b2evo_captcha/b2evo_captcha_fonts/';
        
        $counter_filename = 'b2evo_captcha_counter.txt';
        $filename_prefix = 'b2evo_captcha_';
        
        $minchars = 3;
        $maxchars = 3;
        $minsize = 10;
        $maxsize = 10;
        $maxrotation = 15;
        $noise = FALSE;
        $websafecolors = TRUE;
        $debug = FALSE;
        $collect_garbage_after = 50;
        $maxlifetime = 600;
        $case_sensitive = FALSE;
        $validchars = 'qwertasdfgxcvb';
        $CAPTCHA_CONFIG = array('urlwebpath'=>$urlWebPath,'ftpwebpath'=>$ftpWebPath,'validchars'=>$validchars,'tempfolder'=>$tempfolder,'TTF_folder'=>$TTF_folder,'minchars'=>$minchars,'maxchars'=>$maxchars,'minsize'=>$minsize,'maxsize'=>$maxsize,'maxrotation'=>$maxrotation,'noise'=>$noise,'websafecolors'=>$websafecolors,'debug'=>$debug,'counter_filename'=>$counter_filename,'filename_prefix'=>$filename_prefix,'collect_garbage_after'=>$collect_garbage_after,'maxlifetime'=>$maxlifetime,'case_sensitive'=>$case_sensitive);
        require_once($ftpWebPath.'b2evo_captcha/b2evo_captcha.class.php');
        $ret = &new b2evo_captcha($CAPTCHA_CONFIG);
        return  $ret;
    }
}