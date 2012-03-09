<?php

/** 
 * A stub action to take security information on action without a real action (a real context)
 *   from the actions moduleName and actionName and the appName
 *   Basic purpose to provide the getSecurityValue method
 * @author basos <noxelia 4t gmailcom>
 *
 */
class sfActionSecurityConfigurationContainer
{
	protected
	$moduleName             = '',
    $actionName             = '',
    $security 				= array();
     
    static $securities				= array();
    

  /**
   * Constructor this action.
   *
   * @param string    $appName   The application name
   * @param string    $moduleName The module name.
   * @param string    $actionName The action name.
   *
   */
  public function __construct( $appName, $moduleName, $actionName)
  {
    $this->moduleName = $moduleName ;
    $this->actionName = $actionName ;
    // include security configuration (optional)

    // cache the shit for one module (per execution)
    if (!isset(self::$securities[$appName.$moduleName])) {
    	// try to get caching if we are "called" from the current application

    	$localSecPath = 'modules/'.$moduleName.'/config/security.yml' ;
    	$cached = false ;
    	if (sfApplicationConfiguration::hasActive()) {
    		$activeAppConfiguration = sfApplicationConfiguration::getActive();
    		if ($activeAppConfiguration->getApplication() === $appName) {
	    		//bingo, get the cache
	    		$configCache = $activeAppConfiguration->getConfigCache() ;
			    if ($file = $configCache->checkConfig($localSecPath, true))
			    {
			      // sets $this->security
			      require($file);
			    }
			    $cached = true ;
    		}
    	}
    	if (! $cached ) {
	    	// parse application/module specific security configuration yaml (skip caching)
	    	$appPath = sfConfig::get('sf_apps_dir').'/'.$appName;
	    	$secPath = $appPath . '/'. $localSecPath ;
	    	if (is_readable($secPath)) {
	    		$this->security = sfSecurityConfigHandler::getConfiguration( array($secPath) );
	    	}
    	}
    	// whatever found or not, localy cache it for the execution
    	self::$securities[$appName.$moduleName] = $this->security ;
    }
    $this->security = self::$securities[$appName.$moduleName] ;
  }
  
  /**
   * Gets the module name associated with this action.
   *
   * @return string A module name
   */
  public function getModuleName()
  {
    return $this->moduleName;
  }

  /**
   * Gets the action name associated with this action.
   *
   * @return string An action name
   */
  public function getActionName()
  {
    return $this->actionName;
  }
  
  /**
   * Returns a value from security.yml.
   *
   * @param string $name    The name of the value to pull from security.yml
   * @param mixed  $default The default value to return if none is found in security.yml
   *
   * @return mixed
   */
  public function getSecurityValue($name, $default = null)
  {
    $actionName = strtolower($this->getActionName());

    if (isset($this->security[$actionName][$name]))
    {
      return $this->security[$actionName][$name];
    }

    if (isset($this->security['all'][$name]))
    {
      return $this->security['all'][$name];
    }

    return $default;
  }
  
}