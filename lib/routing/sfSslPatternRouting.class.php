<?php
/**
 * sfSslPatternRouting class controls the generation of urls of routes of actions 
 * with generate_ssl security parameter set.
 *
 *
 * @package    symfony
 * @subpackage routing
 * @author     basos <noxelia 4t gmailcom>
 * @version    SVN: $Id: sfPatternRouting.class.php 24061 2009-11-16 22:35:03Z FabianLange $
 */
class sfSslPatternRouting extends sfPatternRouting
{ 
	protected 
	  $appName = null ;

  /**
   * Initializes this Routing.
   *
   * Available options:
   *
   *  * application: [optional]   The application that this routing is located at 
   *           (to generate ssl urls based on generate_ssl security.yml entry) 
   *           When not specified sf_app configuration variable is used
   *
   * @see sfPatternRouting
   */
  public function initialize(sfEventDispatcher $dispatcher, sfCache $cache = null, $options = array())
  {
    $options = array_merge(array(
      'application'                => null,
    ), $options);
    
    $this->appName = ($options['application'] ? $options['application'] : sfConfig::get('sf_app', null)) ;
    if (!is_readable(sfConfig::get('sf_apps_dir'). '/'. $this->appName))
    {
    	throw new sfConfigurationException('Invalid application ('.$this->appName.') specified in routing params');
    }

    parent::initialize($dispatcher, $cache, $options);

  }
  
  /**
   * Copied from sfPatternRouting and slighty modified (MOD marks)
   * TODO: Maybe change ABI and simplify this subclass (see note on MOD comment below)
   * Extention for the sfSslRequirementPlugin
   *   When using absolute urls it is ensured that routes which correspond
   *   to module/action with require_ssl = true and generate_ssl = true, url will be 
   *   generated with https prefix irrelevant of the current request protocol,
   *   when allow_ssl = false and generate_ssl = true, url will be generated with http
   *   prefix, irrelevant of the current request protocol
   *   when not absolute an sfConfigurationException is thrown
   * For more info 
   * @see sfRouting 
   */
  public function generate($name, $params = array(), $absolute = false)
  {
    // fetch from cache
    if (null !== $this->cache)
    {
      $cacheKey = 'generate_'.$name.'_'.md5(serialize(array_merge($this->defaultParameters, $params))).'_'.md5(serialize($this->options['context']));
      if ($this->options['lookup_cache_dedicated_keys'] && $url = $this->cache->get('symfony.routing.data.'.$cacheKey))
      {
        return $this->fixGeneratedUrl($url, $absolute);
      }
      elseif (isset($this->cacheData[$cacheKey]))
      {
        return $this->fixGeneratedUrl($this->cacheData[$cacheKey], $absolute);
      }
    }

    if ($name)
    {
      // named route
      if (!isset($this->routes[$name]))
      {
        throw new sfConfigurationException(sprintf('The route "%s" does not exist.', $name));
      }
      $route = $this->routes[$name];      
      $this->ensureDefaultParametersAreSet();
    }
    else
    {
      // find a matching route
      if (false === $route = $this->getRouteThatMatchesParameters($params, $this->options['context']))
      {
        throw new sfConfigurationException(sprintf('Unable to find a matching route to generate url for params "%s".', is_object($params) ? 'Object('.get_class($params).')' : str_replace("\n", '', var_export($params, true))));
      }
    }

    $url = $route->generate($params, $this->options['context'], $absolute);

    // store in cache
    if (null !== $this->cache)
    {
      if ($this->options['lookup_cache_dedicated_keys'])
      {
        $this->cache->set('symfony.routing.data.'.$cacheKey, $url);
      }
      else
      {
        $this->cacheChanged = true;
        $this->cacheData[$cacheKey] = $url;
      }
    }
    /*MOD:: We had better to pass $route to the modified version of this subclass
     * or else we would have the serious overhead of calculating it again.
     * Maybe in future the fixGeneratedUrl prototype can change (it is a protected method anyway)
     */ 
    
    return $this->fixGeneratedUrl2($url, $route, $params, $absolute);
  }
  
   /** Adds prefix, host, http(s) in the url
    *  Modified version for ssl prefix addition, based on the fixGeneratedUrl
    */
  protected function fixGeneratedUrl2($url, $route, $bparams = array(), $absolute = false)
  {
  	if (sfConfig::get('app_sfSslRequirementExtraPlugin_disable', false))
  	     return parent::fixGeneratedUrl( $url, $absolute );

  	// cover the case where module/action params are specified in routing.yml(defaults) or after the route binding (parameters)
    $params = array_merge((array)$route->getDefaults(), $bparams);

    $moduleName = $params['module'];
    $actionName = $params['action'];
    $appName = $this->appName ;

    // get the security shit
	$actionSecCont = new sfActionSecurityConfigurationContainer($appName, $moduleName, $actionName) ;
	// get the sfSimplifiedConfigAction simplified interface to sfAction
	$action = new sfSimplifiedConfigAction( $actionSecCont, $this->dispatcher ) ;

	// inject dynamic configuration
    $class = $moduleName.'ActionsSslDynConfig';
    if (!class_exists($class,false)) 
    {
       // load the correct class
       $path = sfConfig::get('sf_apps_dir').'/'.$appName.'/'.'modules/'.$moduleName.'/config/'.'sslDynConfig.class.php';
           
       if (file_exists($path))
       {
	       require_once($path);
       }
    }
    if (class_exists($class,false)) 
	{
	   $sslConfig = new $class();
       $dynConfig = call_user_func( array($sslConfig, 'getSslRequirementGenerateDynamicConfig'), $actionName, $params);
       if ( !is_array($dynConfig) )
          throw new sfException("LOGICAL: getSslRequirementGenerateDynamicConfig() should return an array().");
       $action->setSslDynamicConfig( $dynConfig );
	}
	
	// logic
	$generate_ssl = $action->sslGenerate();
    $allow_ssl = $action->sslAllowed();
    $require_ssl = $action->sslRequired();

    $has_ssl = isset($this->options['context']['is_secure']) && $this->options['context']['is_secure'] ;

    if ( !$generate_ssl ) 
    {
       // if not generate_ssl do as usual
       return parent::fixGeneratedUrl( $url, $absolute );
    }
    // else if generate_ssl = true

    if ( $require_ssl ) 
    {
       // when require (and allow, implicit)
       if ( !$has_ssl ) 
       {
          // context is not secure
          if ( $absolute ) 
          {
              // Make context secure
              $this->options['context']['is_secure'] = true ;
              // revert to insecure after
              $has_ssl = false ;
          }
          else 
          {
             // not absolute, ensure that content MUST be secure
             throw new sfException( 'LOGICAL: Route requires ssl but it is not generated in an absolute way nor the current request context is secured with ssl ('.$url.').' );   
          }
       }
       // else if has_ssl, ok
    }
    elseif ( !$allow_ssl ) {
       // if not require ssl and not allow_ssl
       if ( $has_ssl ) 
       {
          // context is secure
          if ( $absolute ) 
          {
             // Make contect insecure
             $this->options['context']['is_secure'] = false ;
             // revert to secure after
             $has_ssl = true;
          }
          else 
          {
             // if not absolute, ensure content MUST be insecure
             throw new sfException('LOGICAL: Route disallows ssl but is not generated in an absolute way not the current request context is insecure with ssl ('.$url.').');
          }
       }
       // else, if ! $has_ssl, ok
    } // end not allow ssl
         
    $url =  parent::fixGeneratedUrl( $url, $absolute );
    
    // revert is_secure
    if (isset($this->options['context']['is_secure']))
         $this->options['context']['is_secure'] = $has_ssl ;
    
    return $url ;
  }
}