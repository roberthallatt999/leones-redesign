<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config['enable_devlog_alerts'] = 'n';
$config['legacy_member_templates'] = 'y';
$config['allow_php'] = 'n';
$config['index_page'] = '';
$config['is_system_on'] = 'y';
$config['multiple_sites_enabled'] = 'n';
$config['show_ee_news'] = 'n';

// ExpressionEngine Config Items
// Find more configs and overrides at
// https://docs.expressionengine.com/latest/general/system_configuration_overrides.html

$config['app_version'] = '6.4.17';
$config['encryption_key'] = '12730bc09bc146f0e42b62981023b38cf31539f8';
$config['session_crypt_key'] = '4693fa652d6a79db1bca00e73f5840839dbaf303';

// Multi-environment setup

// Config local path
$configLocalPath = SYSPATH . 'user/config/config.local.php';

if (file_exists($configLocalPath)) {
    require $configLocalPath;
}

// EOF