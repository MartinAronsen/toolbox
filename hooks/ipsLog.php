//<?php namespace acfcbf1c0f9b2b1ebc46ff1144ea8e99e;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER[ 'SERVER_PROTOCOL' ] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class toolbox_hook_ipsLog extends _HOOK_CLASS_
{

    public static function debug($message, $category = null)
    {
        if ($category === 'request') {
            return;
        }

        return parent::debug($message, $category); // TODO: Change the autogenerated stub
    }
}
