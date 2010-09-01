<?php
class image_conf {
var $libraryBasePath = '';
var $sourceBasePath = 'obr/';
var $targetBasePath = 'image/';
var $contentType = 'image/jpeg';
var $maxScaleUpRatio = 1.5;
var $minNoScaleRatio = 0.8;
var $maxNoScaleRatio = 1.2;
var $sharpen = false;
var $output = true;
var $sideDefault = 600;
var $sideOptions = '200,400,600,800,1000,1200,1400,1600';
var $cutDefault = 'prop';
var $cutOptions = 'prop,crop,flush';
}