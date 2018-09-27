<?php
function get_m_time_dir ($directory)
{
    $last_modified_time = 0;
    $dirmtime = filemtime ($directory);
    foreach (glob("$directory/*") as $file)
    {
        if (is_file ($file))
        {
            $filemtime = filemtime ($file);
        } else {
            $filemtime = get_m_time_dir ($file);
        }
        $last_modified_time = max($filemtime, $dirmtime, $last_modified_time);
    }
    return $last_modified_time;
}

$silent = true;
$local = getcwd();
chdir("../");
require_once('setup.php');
require_once(LIBS.'vendor/autoload.php');
$compiler = new GoogleClosureCompiler\Compiler;

//input directory
$baseDir = '';
$dir = $baseDir . str_replace(array($baseDir,'..','.js'),'',$_GET['js']);

//check age
if(file_exists($local.'/'.$dir.'.min.js')) {
	$m_time_dir = get_m_time_dir($local.'/'.$dir);
	$filemtime = filemtime ($local.'/'.$dir.'.min.js');
	if($m_time_dir > $filemtime) {
		unlink($local.'/'.$dir.'.min.js');
	}
}

if(file_exists($local.'/'.$dir.'.min.js')) {
	echo file_get_contents($local.'/'.$dir.'.min.js');
	exit;
}

$code = '';
$dir = strpos($dir, '.min')!==false ? str_replace('.min', '', $dir) : $dir;

$dh = opendir($local.'/'.$dir);
while($file = readdir($dh)) {
	if ($file == '.' || $file == '..') continue;
    $code .= file_get_contents($local.'/'.$dir.'/' . $file) . PHP_EOL;
}
    $host = $_SERVER['HTTP_HOST'];
    $hostArr = explode('.',$host);
    if(in_array('local', $hostArr) || in_array('localhost', $hostArr)) {
        echo $code;
        exit;
    }

$response = $compiler->setJsCode($code)->compile();
if ($response && $response->isWithoutErrors()) {
    $minified = $response->getCompiledCode();
    file_put_contents($local.'/'.$dir.'.min.js', $minified);
    echo $minified;
} else {
	$errors = $response->getErrors();
    echo $code;
    echo "console.log(JSON.parse('".json_encode($errors)."'));";
}
