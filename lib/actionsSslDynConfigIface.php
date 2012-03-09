<?php
/**
 * This interface must be implemented from <moduleName>ActionsSslDynConfig classes for each
 *   module that needs to provided dynamic ssl requirement configuration 
 * @author basos <noxelia 4t gmailcom>
 */
interface actionsSslDynConfigIface
{
 /**
  * Called from sf_ssl_requirement plugin, to dynamically configure it during URL MATCHING
  * 
  * @param string $actionName
  * @param sfAction $actionInstance
  * @return array
  */
  public function getSslRequirementMatchDynamicConfig( $actionName, $actionInstance );

 /**
  * Called from sf_ssl_requirement plugin, to dynamically configure it during URL GENERATION
  * 
  * @param string $actionName
  * @param array $routeParams
  * @return array
  */
  public function getSslRequirementGenerateDynamicConfig( $actionName, $routeParams );
}