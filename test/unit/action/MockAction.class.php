<?php

class MockAction extends sfActions
{
  public $request;
  public $securityValues = array();

  public function __construct($dispatcher)
  {
         $this->varHolder              = new sfParameterHolder();
         $this->dispatcher             = $dispatcher;
  }

  public function getSecurityValue($name, $default = null)
  {
    return isset($this->securityValues[$name]) ? $this->securityValues[$name] : $default;
  }
}
