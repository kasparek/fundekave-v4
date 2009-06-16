<?php
interface iPage
{
    static function process($data = array());
    static function build();
}