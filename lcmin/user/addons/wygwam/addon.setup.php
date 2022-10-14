<?php

require_once 'autoload.php';
include_once 'Helper.php';
$addonJson = json_decode(file_get_contents(__DIR__ . '/addon.json'));

return array(
    'name'              => $addonJson->name,
    'description'       => $addonJson->description,
    'version'           => $addonJson->version,
    'namespace'         => $addonJson->namespace,
    'author'            => 'EEHarbor',
    'author_url'        => 'https://eeharbor.com/wygwam',
    'docs_url'          => 'https://eeharbor.com/wygwam/documentation',
    'settings_exist'    => true,
    'services'          => array(),
    'models'            => array(
        'Config' => 'Model\Config'
    ),
    'fieldtypes'     => array(
        'wygwam' => array(
            'compatibility' => 'text'
        )
    )
);
