<?php
class FMathProtect {
  
  var $operation = '+';
  var $value1;
  var $value2;
  
  var $salt = 'let=sSaltThisMD5.it--does-the___magic';
  
  function __construct() {
    $this->value1 = rand(1,4);
    $this->value2 = rand(1,4);
  }
  
  function getQuestion() {
    return $this->value1 . ' ' . $this->operation . ' ' . $this->value2;  
  }
  
  function getQuestionHide() {
    return $this->value1.md5($this->calc($this->value1,$this->operation,$this->value2).$this->salt).$this->value2;
  }
  
  function validate($valHide,$answer) {
    $answer = trim($answer) * 1;
    if(!$answer) return false;
    $v1 = $valHide{0};
    $v2 = $valHide{strlen($valHide)-1};
    $res = $this->calc($v1,$this->operation,$v2);
    if(md5($res.$this->salt) == md5($answer.$this->salt)) return true; 
  }
  
  function calc($v1,$op,$v2) {
    switch($op) {
      case '+':
        return $v1 + $v2;
        break;
      case '-':
        return $v1 - $v2;
        break;
    }
  }
}