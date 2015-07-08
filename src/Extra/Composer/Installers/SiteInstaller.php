<?php
namespace Extra\Composer\Installers;

class SiteInstaller extends BaseInstaller
{
    protected $locations = array(
        'core'      => 'core/',
        'platform'    => 'profiles/{$name}/',
    );
}