<?php
/**
 * @brief      Moderators Singleton
 * @copyright  -storm_copyright-
 * @package    IPS Social Suite
 * @subpackage toolbox\Proxy
 * @since      -storm_since_version-
 * @version    -storm_version-
 */

namespace IPS\toolbox\Proxy\Generator;

use Exception;
use IPS\Application;
use IPS\core\extensions\core\ModeratorPermissions\ModeratorPermissions;
use IPS\Data\Store;

use function array_keys;
use function array_merge;
use function defined;
use function header;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * Moderators Class
 *
 * @mixin Moderators
 */
class _Moderators extends GeneratorAbstract
{

    /**
     * @brief Singleton Instances
     * @note  This needs to be declared in any child class.
     * @var static
     */
    protected static $instance = null;

    /**
     * creates the jsonMeta for the json file and writes the provider to disk.
     */
    public function create()
    {
        $file = 'modsProvider.php';
        $jsonMeta = [];

        if (isset(Store::i()->dt_json)) {
            $jsonMeta = Store::i()->dt_json;
        }

        $jsonMeta['registrar'][] = [
            'signature' => [
                "IPS\\Member::modPermission",
            ],
            'provider'  => 'mods',
            'language'  => 'php',
        ];

        $jsonMeta['providers'][] = [
            'name'   => 'mods',
            'source' => [
                'contributor' => 'return_array',
                'parameter'   => 'dtProxy\\modsProvider::get',
            ],
        ];

        Store::i()->dt_json = $jsonMeta;

        $toggles = [
            'view_future'         => [],
            'future_publish'      => [],
            'pin'                 => [],
            'unpin'               => [],
            'feature'             => [],
            'unfeature'           => [],
            'edit'                => [],
            'hide'                => [],
            'unhide'              => [],
            'view_hidden'         => [],
            'move'                => [],
            'lock'                => [],
            'unlock'              => [],
            'reply_to_locked'     => [],
            'delete'              => [],
            'split_merge'         => [],
            'feature_comments'    => [],
            'unfeature_comments'  => [],
            'add_item_message'    => [],
            'edit_item_message'   => [],
            'delete_item_message' => [],
        ];

        foreach (Application::allExtensions('core', 'ModeratorPermissions', false) as $k => $ext) {
            if ($ext instanceof ModeratorPermissions) {
                /**
                 * @var \IPS\Content\ModeratorPermissions $ext
                 */
                $class = null;

                foreach ($ext->actions as $s) {
                    $class = $ext::$class;
                    $toggles[$s][] = "can_{$s}_{$class::$title}";
                }

                if (isset($class::$commentClass)) {
                    foreach ($ext->commentActions as $s) {
                        $commentClass = $class::$commentClass;
                        $toggles[$s][] = "can_{$s}_{$commentClass::$title}";
                    }
                }

                if (isset($class::$reviewClass)) {
                    foreach ($ext->reviewActions as $s) {
                        $reviewClass = $class::$reviewClass;
                        $toggles[$s][] = "can_{$s}_{$reviewClass::$title}";
                    }
                }
            }
        }

        $apps = Application::appsWithExtension('core', 'ModeratorPermissions');
        $perms = [[]];

        /**
         * @var Application $app
         */
        foreach ($apps as $app) {
            $extensions = $app->extensions('core', 'ModeratorPermissions', true);

            /* @var ModeratorPermissions $extension */
            foreach ($extensions as $extension) {
                try {
                    $perms[] = array_keys($extension->getPermissions($toggles));
                } catch (Exception $e) {
                }
            }
        }

        $perms = array_merge(...$perms);

        $this->writeClass('Mods', 'modsProvider', $perms);
    }
}

