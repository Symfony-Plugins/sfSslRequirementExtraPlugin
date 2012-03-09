<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package    symfony
 * @subpackage plugin
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author     Jonathan H. Wage <jonwage@gmail.com>
 * @author	   basos <noxelia 4t gmailcom>
 * @version    SVN: $Id: sfSslRequirementActionMixin.class.php 29815 2010-06-14 15:30:29Z Kris.Wallsmith $
 */
class sfSslRequirementActionMixin
{
   const SECURITY_REQUIRE_SSL = 'require_ssl';
   const SECURITY_ALLOW_SSL = 'allow_ssl';
   const SECURITY_GENERATE_SSL = 'generate_ssl';
  
  /**
   * Registers the new methods in the component class.
   *
   * @param sfEventDispatcher A sfEventDispatcher instance
   */
  static public function register(sfEventDispatcher $dispatcher)
  {
    $mixin = new sfSslRequirementActionMixin();

    $dispatcher->connect('component.method_not_found', array($mixin, 'listenToMethodNotFound'));

    return $mixin;
  }

  /**
   * Listens to component.method_not_found event.
   *
   * @param  sfEvent A sfEvent instance
   *
   * @return Boolean true if the method has been found in this class, false otherwise
   */
  public function listenToMethodNotFound(sfEvent $event)
  {
  	$method = $event['method'] ;

    // search our extentions
    if (method_exists($this, $method))
    {
      $event->setReturnValue(call_user_func(array($this, $method), $event->getSubject(), $event['arguments']));
      return true;
    }

    return false;
  }
  
  /**
   * 
   * Sets the dynamic security configuration. 
   * This overrides configuration from security.yml and should be set at runtime to implement some logic.
   * Places to set the config:
   *  a) Route matching :: at sslRequirement filter before any security check
   *  b) Route generation :: at sfSslPatterRouting fixGeneratedUrl2, before logic checking
   * @param mixed $action
   * @param array $params
   * @throws sfException
   */
  protected function setSslDynamicConfig( $action, array $params )
  {
     $config = $params[0];
     if (!is_array($config))
        throw new sfException('Arg 0 for method setDynamicConfig should be an array.' );
        
     // set new object dynamic property
     $action->_dynamic_config = $config;
  }
  
  protected function getSslDynamicConfig( $action )
  {
     if (isset($action->_dynamic_config)) 
     {
        return $action->_dynamic_config;
     }
     else 
     {
        return array();
     }
  }
  
  protected function getSslDynamicConfigValue( $action, $name, $default )
  {
     $dynconfig = $this->getSslDynamicConfig($action);
     if (isset($dynconfig[$name]))
        return $dynconfig[$name];
     else
        return $default;
  }

  /**
   * Returns true if the action must always be called in SSL.
   *
   * @param  sfAction A sfAction instance
   *
   * @return Boolean  true if the action must always be called in SSL, false otherwise
   */
  protected function sslRequired($action)
  {
    if ( null !== ($var = $this->getSslDynamicConfigValue($action, self::SECURITY_REQUIRE_SSL, null)))
       return $var;
    else
       return $action->getSecurityValue(self::SECURITY_REQUIRE_SSL, false);
  }

  /**
   * Returns true if the action can be called in SSL.
   *
   * @param  sfAction A sfAction instance
   *
   * @return Boolean  true if the action can be called in SSL, false otherwise
   */
  protected function sslAllowed($action)
  {
    if ($this->sslRequired($action))
        return true;
    elseif ( null !== ($var = $this->getSslDynamicConfigValue($action, self::SECURITY_ALLOW_SSL, null)))
       return $var;
    else 
       return $action->getSecurityValue(self::SECURITY_ALLOW_SSL, false);
  }
  
  /**
   * Returns true if the action route must be generated with/without SSL.
   *
   * @param  sfAction A sfAction instance
   *
   * @return Boolean 
   */
  protected function sslGenerate($action)
  {
    if ( null !== ($var = $this->getSslDynamicConfigValue($action, self::SECURITY_GENERATE_SSL, null)))
       return $var;
    else 
       return $action->getSecurityValue(self::SECURITY_GENERATE_SSL, false);
  }

  /**
   * Returns the SSL URL for the given action.
   *
   * @param  sfAction A sfAction instance
   *
   * @return Boolean  The fully qualified SSL URL for the given action
   */
  protected function getSslUrl($action)
  {
    if (!$domain = $action->getSecurityValue('ssl_domain'))
    {
      $domain = substr_replace($action->getRequest()->getUri(), 'https', 0, 4);
    }

    return $domain;
  }

  /**
   * Returns the non SSL URL for the given action.
   *
   * @param  sfAction A sfAction instance
   *
   * @return Boolean  The fully qualified non SSL URL for the given action
   */
  protected function getNonSslUrl($action)
  {
    if (!$domain = $action->getSecurityValue('non_ssl_domain'))
    {
      $domain = substr_replace($action->getRequest()->getUri(), 'http', 0, 5);
    }

    return $domain;
  }

}
