<?php
namespace Extra\Composer\Installers;

class DrupalInstaller extends BaseInstaller
{
    protected $locations = array(
        'core'      => 'core/',
        'platform'    => 'profiles/{$name}/'
    );
}