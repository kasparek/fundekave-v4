<?php
require_once('_compiler/JSCompiler.php');

//input directory
$baseDir = '';
$dir = $baseDir . str_replace(array($baseDir,'..','.js'),'',$_GET['js']);

//check if parameter is not missing
if(empty($dir)) {
	echo 'Missing parameter.';
	exit;
}

//check if input directory exists
if(!is_dir($dir)) {
	echo 'Directory does not exists.';
	exit;
}

//compiler 
$c = new JSCompiler();
$output = $c->addDir($dir)->simpleMode()->cacheDir($baseDir)->hideDebugInfo()->write();
//$output = $c->addDir($dir)->localCompile()->prettyPrint()->cacheDir($baseDir)->hideDebugInfo()->write();