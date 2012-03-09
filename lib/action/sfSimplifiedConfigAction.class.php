<?php
  
  /**
   * 
   * Minimal class to mimic sfAction behaviour on
   * 	a) Security configuration implementing getSecurityValue
   * 	b) Action Mixin support via __call and event component.method_not_found
   * 	NOTE: That the same event dispatcher that was used to register the Mixin (usualy
   * 		in config.php) should be provided here, or else mixin will not be called.
   * @author basos <noxelia 4t gmailcom>
   */
  class sfSimplifiedConfigAction
  {
	  	protected 
	  	  $securityConfigurationContainer = null,
	  	  $dispatcher = null,
	  	  $varHolder  = null;
	  
	  /**
	   * 
	   * @param fActionSecurityConfigurationContainer $actionSecurityContainer  The getSecurityValue provider
	   * @param sfEventDispatcher $dispatcher  An event dispatcher for method not found
	   */
	   public function __construct( sfActionSecurityConfigurationContainer $actionSecurityContainer, sfEventDispatcher $dispatcher )
	   {
	   	  $this->securityConfigurationContainer = $actionSecurityContainer ;
	   	  $this->dispatcher = $dispatcher;
	   	  
	   	  // initialize var holder for overloaded variables
	   	  $this->varHolder = new sfParameterHolder();
	   }
	   
      /**
       * Calls methods defined via sfEventDispatcher.
       *
       * @param string $method The method name
       * @param array  $arguments The method arguments
       *
       * @return mixed The returned value of the called method
       *
       * @throws sfException If called method is undefined
       */
       public function __call($method, $arguments)
       {
          $event = $this->dispatcher->notifyUntil(new sfEvent($this, 'component.method_not_found', array('method' => $method, 'arguments' => $arguments)));
          if (!$event->isProcessed())
          {
            throw new sfException(sprintf('Call to undefined method %s::%s.', get_class($this), $method));
          }
      
          return $event->getReturnValue();
       }
       
     /**
      * Sets a variable for the template.
      *
      * This is a shortcut for:
      *
      * <code>$this->setVar('name', 'value')</code>
      *
      * @param string $key   The variable name
      * @param string $value The variable value
      *
      * @return boolean always true
      *
      * @see setVar()
      */
     public function __set($key, $value)
     {
       return $this->varHolder->setByRef($key, $value);
     }
   
     /**
      * Gets a variable for the template.
      *
      * This is a shortcut for:
      *
      * <code>$this->getVar('name')</code>
      *
      * @param string $key The variable name
      *
      * @return mixed The variable value
      *
      * @see getVar()
      */
     public function & __get($key)
     {
       return $this->varHolder->get($key);
     }
   
     /**
      * Returns true if a variable for the template is set.
      *
      * This is a shortcut for:
      *
      * <code>$this->getVarHolder()->has('name')</code>
      *
      * @param string $name The variable name
      *
      * @return boolean true if the variable is set
      */
     public function __isset($name)
     {
       return $this->varHolder->has($name);
     }
   
     /**
      * Removes a variable for the template.
      *
      * This is just really a shortcut for:
      *
      * <code>$this->getVarHolder()->remove('name')</code>
      *
      * @param string $name The variable Name
      */
     public function __unset($name)
     {
       $this->varHolder->remove($name);
     }
	   
	   /**
	    * Local interface to the getSecurityValue method of the $securityConfigurationContainer
	    */
	   public function getSecurityValue($key, $default = null)
	   {
	   	  return $this->securityConfigurationContainer->getSecurityValue($key, $default);
	   }
  }