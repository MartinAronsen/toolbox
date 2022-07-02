<?php

use IPS\Settings;

use function str_replace;


$path = str_replace('/applications/toolbox/sources/Profiler/Adminer/db.php', '',
        str_replace('\\', '/', __FILE__)) . '/';
require_once $path . 'init.php';

    function adminer_object()
    {
        // required to run any plugin
        include_once 'Plugin.php';

        $plugins = array(// specify enabled plugins here
        );

        // It is possible to combine customization and plugins:
        class AdminerCustomizationAdminer extends AdminerPlugin
        {
//            function permanentLogin($create = false)
//            {
//                // key used for permanent login
//                return 'somerandonstring';
//            }
            public function navigation($missing)
            {
                $return = parent::navigation($missing);
            }

            function databasesPrint($missing)
            {
                return [];
            }
        }

        return new AdminerCustomizationAdminer($plugins);
    }

    $xa = [
        'driver' => 'server',
        'server' => Settings::i()->getFromConfGlobal('sql_host'),
        'username' => Settings::i()->getFromConfGlobal('sql_user'),
        'password' => Settings::i()->getFromConfGlobal('sql_pass'),
        'db' => Settings::i()->getFromConfGlobal('sql_database'),
        'permanent' => true,
    ];

require_once("adminer.php");
