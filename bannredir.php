<?php
/**
 * countig banner hits and redirect
 *
 * @version 060119
 * @copyright 2006 
 **/
require("./local.php");
require(INIT_FILENAME);
FSystem::bannerRedirect($_GET["bid"]);