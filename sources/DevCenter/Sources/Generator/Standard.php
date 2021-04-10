<?php

/**
 * @brief       Standard Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox: Dev Center Plus
 * @since       1.0.0
 * @version     -storm_version-
 */

namespace IPS\toolbox\DevCenter\Sources\Generator;

use function defined;
use function header;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * Class _Standard
 *
 * @package IPS\toolbox\DevCenter\Sources\Generator
 * @mixin GeneratorAbstract
 */
class _Standard extends GeneratorAbstract
{
    /**
     * @inheritdoc
     */
    protected function bodyGenerator()
    {
        $this->brief = 'Class';
    }
}
