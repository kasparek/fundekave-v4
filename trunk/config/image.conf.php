<?php
class ImageConfig {
	public static $log = 'tmp/php.log';
	public static $quality = 80;
	public static $libraryBasePath = '';
	public static $sourceBasePath = 'obr/';
	public static $targetBasePath = 'image/';
	public static $contentType = 'image/jpeg';
	public static $maxScaleUpRatio = 1;
	public static $minNoScaleRatio = 0.8;
	public static $maxNoScaleRatio = 1;
	public static $optimize = false;
	public static $output = true;
	public static $sideDefault = 800;
	public static $sideOptions = '40,50,170,200,220,300,320,400,512,576,640,720,800,912,1024,1152,1280,1440,1600,2048';
	public static $maxSize = 2048;
	public static $cutDefault = 'prop';
	public static $cutOptions = 'prop,crop,flush';
	public static $salt = 'saltyImageFDK-bebrmBUBU';
}