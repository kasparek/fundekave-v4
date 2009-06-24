<?php
chdir('../');
require("./local.php");
require(INIT_FILENAME);
$targetPath = ROOT . ROOT_UPLOADIFY;

$fileArray = array();
foreach ($_POST as $key => $value) {
	if ($key != 'folder') {
		if (file_exists($targetPath . $value)) {
			$fileArray[$key] = $value;
		}
	}
}
echo json_encode($fileArray);