//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Member;
use IPS\toolbox\Editor;

if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

class toolbox_hook_adminSupportTemplate extends _HOOK_CLASS_
{

/* !Hook Data - DO NOT REMOVE */
public static function hookData() {
 return parent::hookData();
}
/* End Hook Data */

    public function methodIssues($table)
    {
        Member::loggedIn()->language()->words['advise_removal_of_php8_incompatible_code'] = Member::loggedIn()->language()->get('no_fucks_left').Member::loggedIn()->language()->get('advise_removal_of_php8_incompatible_code');
        $table->parsers['scanner_method']  = static function($val, $row){
            if(isset($row['subclassFile'])){
                $parts = explode(':', $row['subclassFile']);
                $file = $parts[0] ?? null;
                if( $file ){
                    $file = \IPS\ROOT_PATH.'/'.str_replace(\IPS\ROOT_PATH,'', $file);
                    $editor = (new Editor())->replace($file, $parts[1] ?? 0);
                    if( $editor ) {
                        return '<a href="' . $editor . '">' . $val . '</a>';
                    }
                }
            }
            return $val;
        };

        $table->parsers['subclassFile'] = static function($val, $row){
            $parts = explode(':', $val);
            $file = $parts[0] ?? null;
            if($file === null){
                return $val;
            }
            $file = \IPS\ROOT_PATH.'/'.str_replace(\IPS\ROOT_PATH,'', $file);
            $editor = (new Editor())->replace($file, $parts[1] ?? 0);
            if( $editor === null){
                return $val;
            }
            return '<a href="'.$editor.'">'.$val.'</a>';
        };

	    if ( \is_callable('parent::tabs') )
	    {
		    return \call_user_func_array( 'parent::' . __FUNCTION__, [ $table ] );
	    }

    }
}
