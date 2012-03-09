<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package    symfony
 * @subpackage plugin
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author 	   basos <noxelia 4t gmailcom>
 * @version    SVN: $Id: sfSslRequirementFilter.class.php 16720 2009-03-29 20:43:55Z Kris.Wallsmith $
 */
class sfSslRequirementFilter extends sfFilter
{
  public function execute ($filterChain)
  {
  	$exit = false;
    // execute only once and only if not using an environment that is disabled for SSL
    if ($this->isFirstCall() && !sfConfig::get('app_sfSslRequirementExtraPlugin_disable', false))
    {
      // get the cool stuff
      $context = $this->getContext();
      $request = $context->getRequest();

      // only redirect http(s) requests
      if ( substr($request->getUri(), 0, 4) == 'http')
      {
        $controller = $context->getController();

        // get the current action instance
        $actionEntry    = $controller->getActionStack()->getLastEntry();
        $actionInstance = $actionEntry->getActionInstance();
        $actionName     = $actionInstance->getActionName();
        $moduleName     = $actionInstance->getModuleName();
        $appName        = $context->getConfiguration()->getApplication();

        // inject ssl dynamic configuration
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
           $dynConfig = call_user_func( array($sslConfig, 'getSslRequirementMatchDynamicConfig'), $actionName, $actionInstance);
           if ( !is_array($dynConfig) )
              throw new sfException("LOGICAL: getSslRequirementMatchDynamicConfig() should return an array().");
           if ( isset($dynConfig[sfSslRequirementActionMixin::SECURITY_GENERATE_SSL]))
              throw new sfException("LOGICAL: getSslRequirementMatchDynamicConfig() should not configure ".sfSslRequirementActionMixin::SECURITY_GENERATE_SSL);
           $actionInstance->setSslDynamicConfig( $dynConfig ); // mixin
	    }

        // process HEAD or GET requests
        if (in_array($request->getMethod(), array(sfRequest::HEAD, sfRequest::GET)))
        {
	      // request is SSL secured
	      if ($request->isSecure())
	      {
	        // but SSL is not allowed
	        if (!$actionInstance->sslAllowed() && $this->redirectToHttp())
	        {
	          $controller->redirect($actionInstance->getNonSslUrl());
	          $exit = true;
	        }
	      }
	      // request is not SSL secured, but SSL is required
	      elseif ($actionInstance->sslRequired() && $this->redirectToHttps())
	      {
	        $controller->redirect($actionInstance->getSslUrl());
	        $exit = true;
	      }
	    } // get

        //process POST requests
        if (in_array($request->getMethod(), array(sfRequest::POST)) &&
               sfConfig::get('app_sfSslRequirementExtraPlugin_strict_post', true)) 
        {
           // request is not SSL secured
           if (!$request->isSecure())
           {
               // request is not SSL secured, but SSL is required
               // we are POSTing , then redirect does not make sense
               if ($actionInstance->sslRequired())
               {
               	$this->getContext()->getLogger()->log( "sfSslRequirementFilter [$moduleName/$actionName]: LOGICAL: Sensitive data might have been exposed. Insecure posting of this form is not accepted. Please use https. Throwing error!", sfLogger::ERR ) ;
                   throw new sfException( 'LOGICAL: Sensitive data might have been exposed. Insecure posting of this form is not accepted. Please use https.');
                   $exit = true; //reduntant
               }
           }
        } // post
      } // http
    }

    if (!$exit)
        $filterChain->execute();
  }

  protected function redirectToHttps()
  {
    return true;
  }

  protected function redirectToHttp()
  {
    return true;
  }
}
