<?php
class CategoryVO extends Fvob {

	var $table = 'sys_pages_category';
	var $primaryCol = 'categoryId';

	var $columns = array('categoryId' => 'categoryId',
	'typeId' => 'typeId',
	'name' => 'name',
	'description' => 'description',
	'ord' => 'ord',
	'public' => 'public'
	);

  var $categoryId;
  var $typeId;
  var $name;
  var $description = '';
  var $ord = 0;
  var $public = 1;
}