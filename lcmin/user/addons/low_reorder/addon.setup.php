<?php

require_once 'autoload.php';
$addonJson = json_decode(file_get_contents(__DIR__ . '/addon.json'));

return array(
    'name'           => $addonJson->name,
    'description'    => $addonJson->description,
    'version'        => $addonJson->version,
    'namespace'      => $addonJson->namespace,
    'author'         => 'Low',
    'author_url'     => 'http://gotolow.com/',
    'docs_url'       => 'http://gotolow.com/addons/low-reorder',
    'settings_exist' => true
);
