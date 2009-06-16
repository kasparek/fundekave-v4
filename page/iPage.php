<?php
interface iPage
{
	/**
	 * 
	 * @param $data - array containing _POST and [__get] _GET, [__files] _FILES
	 * @return void
	 */
    static function process($data);
    static function build();
}