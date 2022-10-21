<?php

/**
 * @brief       Dtproxy Proxyhelpers extension: Proxy
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox: Proxy Class Generator
 * @since       1.1.0
 * @version     -storm_version-
 */

namespace IPS\toolbox\extensions\toolbox\ProxyHelpers;

use IPS\Content\Item;
use IPS\Data\Store;
use IPS\Http\Url;
use IPS\Node\Model;
use IPS\Output;
use IPS\Patterns\ActiveRecord;
use IPS\Patterns\Singleton;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use IPS\toolbox\DevCenter\Sources\Generator\GeneratorAbstract as devPlusGeneratorAbstract;
use IPS\toolbox\Proxy\Helpers\GeneratorAbstract;
use IPS\toolbox\Proxy\Helpers\Member;
use IPS\toolbox\Proxy\Helpers\Request as HelpersRequest;
use IPS\toolbox\Proxy\Helpers\Store as HelpersStore;
use IPS\Widget;

use function defined;
use function header;

/* To prevent PHP errors (extending class does not exist) revealing path */
if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * proxy
 */
class _proxy
{

    /**
     * add property to \IPS\Data\Store DocComment
     *
     * @param array $classDoc
     */
    public function store(&$classDoc)
    {
        $classDoc[] = ['pt' => 'p', 'prop' => 'dtproxy_proxy_files', 'type' => 'array'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'dt_json', 'type' => 'array'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'dtproxy_templates', 'type' => 'array'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'toolbox_proxy_namespaces', 'type' => 'array'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'toolbox_proxy_classes', 'type' => 'array'];
    }

    /**
     * add property to \IPS\Request proxy DocComment
     *
     * @param array $classDoc
     */
    public function request(&$classDoc)
    {
        $classDoc[] = ['pt' => 'p', 'prop' => 'uid', 'type' => 'int'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'dashboard', 'type' => 'int'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'iid', 'type' => 'int'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'rrid', 'type' => 'int'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'noshow', 'type' => 'int'];
    }

    /**
     * returns a list of classes available to run on classes
     *
     * @param $helpers
     */
    public function map(&$helpers)
    {
        $helpers[Request::class][] = HelpersRequest::class;
        $helpers[Store::class][] = HelpersStore::class;
        $helpers[devPlusGeneratorAbstract::class][] = GeneratorAbstract::class;
        $helpers[Output::class][] = \IPS\toolbox\Proxy\Helpers\Output::class;
        $helpers[Model::class][] = \IPS\toolbox\Proxy\Helpers\Model::class;
        $helpers[Url::class][] = \IPS\toolbox\Proxy\Helpers\Url::class;
        $helpers[Widget::class][] = \IPS\toolbox\Proxy\Helpers\Widget::class;
        $helpers[Item::class][] = \IPS\toolbox\Proxy\Helpers\Item::class;
        $helpers[Singleton::class][] = \IPS\toolbox\Proxy\Helpers\Singleton::class;
        $helpers[Theme::class][] = \IPS\toolbox\Proxy\Helpers\Theme::class;
        $helpers[ActiveRecord::class][] = \IPS\toolbox\Proxy\Helpers\ActiveRecord::class;
        $helpers[Session::class][] = \IPS\toolbox\Proxy\Helpers\Session::class;
        $helpers[\IPS\Member::class][] = Member::class;
        $helpers[\IPS\Log::class][] = \IPS\toolbox\Proxy\Helpers\Log::class;
        $helpers[\IPS\nexus\Customer::class][] = \IPS\toolbox\Proxy\Helpers\Customer::class;
    }
}
