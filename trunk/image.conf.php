<?php
class image_conf {
var $log = 'tmp/php.log';
var $quality = 85;
var $libraryBasePath = '';
var $sourceBasePath = 'obr/';
var $targetBasePath = 'image/';
var $contentType = 'image/jpeg';
var $maxScaleUpRatio = 1;
var $minNoScaleRatio = 0.8;
var $maxNoScaleRatio = 1;
var $optimize = false;
var $output = true;
var $sideDefault = 600;
var $sideOptions = '40,50,170,200,300,400,500,600,800,1000,1200,1400,1600';
var $maxSize = '1600';
var $cutDefault = 'prop';
var $cutOptions = 'prop,crop,flush';
var $salt = 'saltyImageFDK-bebrmBUBU';
}