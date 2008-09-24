<?php
//---my weather class using PEAR weather class
class fWeather {
    var $cacheEnabled = false;
    var $cacheDir = '/home/fundekave/www/fundekave/data/weather/';
    var $imageDir =  'data/weather/images/';
    var $imageSmallDir = '32x32/';
    var $imageLargeDir = '64x64/';
    var $service = "WeatherDotCom";
    var $serviceOptions = array("debug" => 2, "httpTimeout" => 30);
    var $serviceVerification = false;
    var $serviceUser = '';
    var $servicePass = '';
    var $unitsFormat = 'metric';
    var $dateFormat = "d.m.Y";
    var $timeFormat = "H:i";
    
    var $locationId = '';
    
    var $serviceInst;
    var $arrLocation = array();
    var $arrWeather = array();
    var $arrForecast = array();
    
    function __construct() {
require_once "Services/Weather.php";
$this->serviceInst = &Services_Weather::service($this->service, $this->serviceOptions);
//        require_once "Services/Weather.php";
 //       $this->serviceInst = &Services_Weather::service($this->service, $this->serviceOptions);
        if (Services_Weather::isError($this->serviceInst)) die("Error: ".$this->serviceInst->getMessage()."\n");
        if($this->serviceVerification) $this->serviceInst->setAccountData($this->serviceUser, $this->servicePass);
        if($this->cacheEnabled) {
             $status = $this->serviceInst->setCache("file", array("cache_dir" => $this->cacheDir));
             if (Services_Weather::isError($status)) echo "Error: ".$status->getMessage()."\n";
        }
        $this->serviceInst->setUnitsFormat($this->unitsFormat);
        $this->serviceInst->setDateTimeFormat($this->dateFormat, $this->timeFormat);
    }
    function searchLocation($strFilter) {
        $search = $this->serviceInst->searchLocation($strFilter);
        $ret = false;
        if (Services_Weather::isError($search)) {
            //$ret = "Error: ".$search->getMessage();
            $ret = false;
        } elseif(!is_array($search)) {
            $this->locationId = $search;
            $ret = true;
        } else $ret = $search;
        return $ret;
    }
    function setLocationId($locationId) {
        $this->locationId = $locationId;
    }
    function getData() {
        //ERRROR if (Services_Weather::isError($arrLocation)){ echo "Error: ".$arrLocation->getMessage()."\n"; exit; }
        
        $this->arrLocation = $this->serviceInst->getLocation($this->locationId);
        $this->arrWeather = $this->serviceInst->getWeather($this->locationId);
        $this->arrForecast = $this->serviceInst->getForecast($this->locationId,3);
        
    }
    function printWeather() {
        
        
        
        echo $weatherLocation = '<div id="weatherlocation">'
        .$this->arrLocation['name'].' ['.$this->arrLocation['time'].']<br />'
        .'<img src="data/weather/images/sunrise.gif">'.$this->arrLocation['sunrise']
        .'<img src="data/weather/images/sunset.gif">'.$this->arrLocation['sunset']
        .'</div>';
        
        echo $weatherAct = '<div id="weatheractual">'
        .'<img src="data/weather/64x64/'.$this->arrWeather['conditionIcon'].'.png"><br />'
        .$this->arrWeather['condition'].' '.$this->arrWeather['temperature'].'C<br />'
        .'Vitr:'.$this->arrWeather['wind'].'m/s '.$this->arrWeather['windDirection'].'Rosny bod:'.$this->arrWeather['dewPoint']
        .'</div>';
        
        $weatherForecast = '<div id="weatherforecast">';
        foreach ($this->arrForecast['days'] as $day) {
            $weatherForecast.='<div class="weatherforecastday">'	
            .'<img src="data/weather/images/sunrise.gif">'.$day['sunrise']
            .'<img src="data/weather/images/sunset.gif">'.$day['sunset'].'<br />'
            .'<img src="data/weather/64x64/'.$day['day']['conditionIcon'].'.png"><br />'
            .$day['temperatureLow'].'C - '.$day['temperatureHigh'].'C<br />'
            .'Vitr: '.$day['day']['wind'].'m/s Srazky: '.$day['day']['precipitation'].'mm'
            .'</div>';
        }
        echo $weatherForecast.='</div>';
    }
}
?>