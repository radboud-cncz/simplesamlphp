<?php

use Webmozart\Assert\Assert;

$config = \SimpleSAML\Configuration::getInstance();

if (!array_key_exists('as', $_REQUEST)) {
    $t = new \SimpleSAML\XHTML\Template($config, 'core:authsource_list.twig');

    $t->data['sources'] = \SimpleSAML\Auth\Source::getSources();
    $t->send();
    exit();
}

$asId = (string) $_REQUEST['as'];
$as = new \SimpleSAML\Auth\Simple($asId);

if (array_key_exists('logout', $_REQUEST)) {
    $as->logout($config->getBasePath() . 'logout.php');
}

if (array_key_exists(\SimpleSAML\Auth\State::EXCEPTION_PARAM, $_REQUEST)) {
    // This is just a simple example of an error

    /** @var array $state */
    $state = \SimpleSAML\Auth\State::loadExceptionState();

    Assert::keyExists($state, \SimpleSAML\Auth\State::EXCEPTION_DATA);

    $e = $state[\SimpleSAML\Auth\State::EXCEPTION_DATA];
    throw $e;
}

if (!$as->isAuthenticated()) {
    $url = \SimpleSAML\Module::getModuleURL('core/authenticate.php', ['as' => $asId]);
    $params = [
        'ErrorURL' => $url,
        'ReturnTo' => $url,
    ];
    $as->login($params);
}

$attributes = $as->getAttributes();
$authData = $as->getAuthDataArray();

$t = new \SimpleSAML\XHTML\Template($config, 'status.php', 'attributes');

$t->data['header'] = '{status:header_saml20_sp}';
$t->data['attributes'] = $attributes;
$t->data['authData'] = $authData;
$t->data['nameid'] = !is_null($as->getAuthData('saml:sp:NameID')) ? $as->getAuthData('saml:sp:NameID') : false;
$t->data['logouturl'] = \SimpleSAML\Utils\HTTP::getSelfURLNoQuery() . '?as=' . urlencode($asId) . '&logout';
$t->send();
