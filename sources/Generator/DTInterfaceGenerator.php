<?php

/**
 * @brief       DTInterfaceGenerator Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox: Base
 * @since       1.2.0
 * @version     -storm_version-
 */

namespace IPS\toolbox\Generator;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}


use IPS\toolbox\Application;
use Laminas\Code\Generator\InterfaceGenerator;

use function defined;
use function header;
use function preg_replace;

\IPS\toolbox\Application::loadAutoLoader();

/**
 * Class _DTClassGenerator
 *
 * @package IPS\toolbox\DevCenter\Sources\Generator
 * @mixin \IPS\toolbox\DevCenter\Sources\Generator\DTInterfaceGenerator
 */
class _DTInterfaceGenerator extends InterfaceGenerator
{
    public function generate()
    {
        $parent = parent::generate();
        $addIn = <<<'eof'
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER[ 'SERVER_PROTOCOL' ] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}
eof;

        $parent = preg_replace('/namespace(.+?)([^\n]+)/', 'namespace $2' . self::LINE_FEED . self::LINE_FEED . $addIn,
            $parent);

        return $parent;
    }
}
