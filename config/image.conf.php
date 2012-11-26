<?php
class ImageConfig {
	public static $quality = 80;
	public static $sourceBasePath = 'obr/';
	public static $targetBasePath = 'image/';
	public static $contentType = 'image/jpeg';
	public static $minNoScaleRatio = 0.8;
	public static $maxNoScaleRatio = 1;
	public static $optimize = false;
	public static $sideDefault = 700;
	public static $sideOptions = '40,50,170,200,300,700,1024,1600,2048';
	public static $maxSize = 2048;
	public static $cutDefault = 'prop';
	public static $cutOptions = 'prop,crop,flush';
	public static $salt = 'saltyImageFDK-bebrmBUBU';
}