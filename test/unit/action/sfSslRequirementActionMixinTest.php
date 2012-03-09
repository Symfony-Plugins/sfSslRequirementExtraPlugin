<?php

if (!isset($_SERVER['SYMFONY']))
{
  die("You must set the \"SYMFONY\" environment variable to the symfony lib dir (export SYMFONY=/path/to/symfony/lib/).\n");
}

require_once $_SERVER['SYMFONY'].'/vendor/lime/lime.php';
require_once $_SERVER['SYMFONY'].'/autoload/sfCoreAutoload.class.php';
sfCoreAutoload::register();

require_once dirname(__FILE__).'/../../../lib/action/sfSslRequirementActionMixin.class.php';
require_once dirname(__FILE__).'/MockAction.class.php';
require_once dirname(__FILE__).'/MockRequest.class.php';

$t = new lime_test(13);

$dispatcher = new sfEventDispatcher();
$action = new MockAction($dispatcher);
sfSslRequirementActionMixin::register($dispatcher);
$request = new MockRequest();
$action->request = $request;

// ->getSslUrl()
$t->diag('->getSslUrl()');

$action->securityValues = array('ssl_domain' => 'https://example.com/foo');
$t->is($action->getSslUrl(), 'https://example.com/foo', '->getSslUrl() uses the action\'s "ssl_domain" security value');

$action->securityValues = array();
$request->uri = 'http://example.com/foo/bar';
$t->is($action->getSslUrl(), 'https://example.com/foo/bar', '->getSslUrl() converts the current URI if no "ssl_domain" is set');

// ->getNonSslUrl()
$t->diag('->getNonSslUrl()');

$action->securityValues = array('non_ssl_domain' => 'http://example.com/foo');
$t->is($action->getNonSslUrl(), 'http://example.com/foo', '->getNonSslUrl() uses the action\'s "non_ssl_domain" security value');

$action->securityValues = array();
$request->uri = 'https://example.com/foo/bar';
$t->is($action->getNonSslUrl(), 'http://example.com/foo/bar', '->getNonSslUrl() converts the current URI if no "non_ssl_domain" is set');

// ->sslAllowed()
$t->diag('->sslAllowed() ->sslRequired() ->sslGenerate()');

$action->securityValues = array('require_ssl' => true);
$t->is($action->sslAllowed(), true, '->sslAllowed() returns true when "require_ssl" is true');
$t->is($action->sslRequired(), true, '->sslRequired() returns true when "require_ssl" is true');
$t->is($action->sslGenerate(), false, '->sslGenerate() returns false when "generate_ssl" is false');

$action->securityValues = array('allow_ssl' => true, 'generate_ssl'=>true);
$t->is($action->sslAllowed(), true, '->sslAllowed() returns true when "allow_ssl" is true');
$t->is($action->sslRequired(), false, '->sslRequired() returns false when "allow_ssl" is true');
$t->is($action->sslGenerate(), true, '->sslGenerate() returns true when "generate_ssl" is true');

// dynamic config
$t->diag('Dynamic config');
$dynConfig = array('allow_ssl'=>false, 'generate_ssl'=>false);
$action->setSslDynamicConfig($dynConfig);
$t->is($action->sslAllowed(), false, '->sslAllowed() returns false when "allow_ssl" dyn configed false');
$t->is($action->sslGenerate(), false, '->sslGenerate() returns false when "generate_ssl" dyn configed false');

$dynConfig = array('require_ssl'=>true);
$action->setSslDynamicConfig($dynConfig);
$t->is($action->sslRequired(), true, '->sslRequired() returns true when "require_ssl" dyn configed true');
