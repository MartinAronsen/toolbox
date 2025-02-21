<?php

/**
 * @brief       Profile Singleton
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  toolbox
 * @since       -storm_since_version-
 * @version     -storm_version-
 */

namespace IPS\toolbox;

use IPS\Db;
use IPS\IPS;
use Exception;
use IPS\Theme;
use IPS\Member;
use IPS\Plugin;
use IPS\Request;
use IPS\Http\Url;
use IPS\Settings;
use IPS\Dispatcher;
use ReflectionClass;
use RuntimeException;
use OutOfRangeException;
use IPS\Patterns\Singleton;
use InvalidArgumentException;
use IPS\toolbox\Profiler\Git;
use UnexpectedValueException;
use IPS\toolbox\Profiler\Time;
use IPS\toolbox\Profiler\Debug;
use IPS\toolbox\Profiler\Memory;
use IPS\toolbox\Profiler\Parsers\Logs;
use IPS\toolbox\Profiler\Parsers\Files;
use IPS\toolbox\Profiler\Parsers\Caching;
use IPS\toolbox\Profiler\Parsers\Database;
use IPS\toolbox\Profiler\Parsers\Templates;

use function md5;
use function count;
use function round;
use function header;
use function is_dir;
use function defined;
use function explode;
use function implode;
use function in_array;
use function is_array;
use function is_object;
use function mb_substr;
use function microtime;
use function json_decode;
use function json_encode;
use function base64_encode;
use function function_exists;

use const DT_MY_APPS;
use const PHP_VERSION;
use const IPS\NO_WRITES;
use const IPS\CACHING_LOG;
use const IPS\CACHE_PAGE_TIMEOUT;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class _Profiler extends Singleton
{
    /**
     * @brief   Singleton Instances
     * @note    This needs to be declared in any child class.
     */
    protected static $instance;

    public function __construct()
    {
        Application::loadAutoLoader();
    }

    /**
     * @return int|null
     */
    protected function getFrameworkTime(): ?float
    {
        if (Settings::i()->dtprofiler_enabled_execution) {
            return round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 4) * 1000;
        }
        return null;
    }

    protected function myApps(): array
    {
        $myApps = defined('DT_MY_APPS') ? explode(',', DT_MY_APPS) : [];

        if (empty($myApps) === false) {
            $newMyApps = [];
            $applications = Application::applications();
            /** @var Application $app */
            foreach ($applications as $app) {
                if (!in_array($app->directory, $myApps, true)) {
                    continue;
                }
                $name = $app->_title;
                Member::loggedIn()->language()->parseOutputForDisplay($name);
                $url = Url::internal('app=toolbox');
                $source = $url->setQueryString(
                    ['appKey' => $app->directory, 'module' => 'devcenter', 'controller' => 'sources']
                );
                $build = $url->setQueryString(
                    ['appToBuild' => $app->directory, 'module' => 'bt', 'controller' => 'build', 'do' => 'form']
                );
                $assets = $url->setQueryString(
                    ['appKey' => $app->directory, 'module' => 'devcenter', 'controller' => 'dev']
                );
                $devCenter = Url::internal(
                    'app=core&module=applications&controller=developer&appKey=' . $app->directory,
                    'admin'
                );

                $newMyApps[] = [
                    'name' => $name,
                    'app' => $app->directory,
                    'hash' => md5($app->directory),
                    'subs' => [
                        'Add Sources' => ['url' => (string) $source, 'icon' => 'fa-arrow-down'],
                        'Add Assets' => ['url' => (string) $assets, 'icon' => 'fa-code'],
                        'Build' => ['url' => (string) $build, 'icon' => 'fa-fw fa-cog'],
                        'DevCenter' => ['url' => (string) $devCenter, 'icon' => 'fa-fw fa-cogs', 'target' => '']
                    ]
                ];
            }
            $myApps = $newMyApps;
        }

        return $myApps;
    }

    /**
     * @return mixed
     * @throws InvalidArgumentException
     * @throws OutOfRangeException
     * @throws RuntimeException
     * @throws UnexpectedValueException
     */
    public function run(): mixed
    {
        if (CACHE_PAGE_TIMEOUT !== 0 && !Member::loggedIn()->member_id || Request::i()->isAjax()) {
            return '';
        }

        $framework = $this->getFrameworkTime();
        $logs = Logs::i()->build();
        $database = Database::i()->build();
        $templates = Templates::i()->build();
        $extra = implode(' ', $this->extra());
        $info = $this->info();
        $environment = $this->environment();
        $debug = Settings::i()->dtprofiler_enable_debug ? Debug::build() : null;
        $time = null;
        $executions = Time::build();
        $files = Settings::i()->dtprofiler_enabled_files ? Files::i()->build() : null;
        $cache = CACHING_LOG ? Caching::i()->build() : null;
        $memory = Settings::i()->dtprofiler_enabled_memory ? Memory::build() : null;
        $myApps = $this->myApps();

        if (Settings::i()->dtprofiler_enabled_execution) {
            $total = round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 4) * 1000;
            $profileTime = $total - $framework;
            $time = [
                'total' => $total,
                'framework' => $framework,
                'profiler' => $profileTime,
            ];
        }


        return Theme::i()
            ->getTemplate('bar', 'toolbox', 'front')
            ->bar(
                $time,
                $memory,
                $files,
                $templates,
                $database,
                $cache,
                $logs,
                $extra,
                $info,
                $environment,
                $debug,
                $executions,
                $myApps
            );
    }

    /**
     * hook into this to add "buttons" to the bar
     *
     * @return array
     */
    protected function extra(): array
    {
        return [];
    }

    /**
     * @return array
     * @throws InvalidArgumentException
     * @throws OutOfRangeException
     * @throws RuntimeException
     */
    protected function info(): array
    {
        $data = base64_encode((string) Request::i()->url());
        $url = Url::internal('app=toolbox&module=bt&controller=bt', 'front')->setQueryString([
            'do' => 'clearCaches',
            'data' => $data,
        ]);
        $info = [];
        $info['server'] = [
            'IPS' => Application::load('core')->version,
            'PHP' => '<a href="' . (string) $url->setQueryString(
                ['do' => 'phpinfo']
            ) . '" data-ipsDialog data-ipsDialog-title="phpinfo()"><i class="fa">🅟🅗🅟</i><br>' . PHP_VERSION . '</a>',
            'MySql' => Db::i()->server_info,

        ];
        $info['adminer'] = '<a href="' . (string) Url::internal(
            'app=toolbox&module=bt&controller=bt&do=adminer'
        ) . '" data-ipsdialog data-ipsdialog-title="Adminer">Adminer</a>';
        $slowestLink = Database::$slowestLink;
        $slowestTime = Database::$slowest;
        $info['other'] = [
            'Controller' => $this->getLocation(),
            'Slowest Query' => "<a href='#' data-ipsdialog data-ipsdialog-content='#{$slowestLink}' data-ipsdialog-title='SQL Query'>{$slowestTime}ms</a>",
        ];

        $info['cache'] = (string) $url;
        //        $info[ 'apps' ][ 'enable' ] = Url::internal('app=toolbox&module=bt&controller=bt', 'front')->setQueryString(['do' => 'thirdParty', 'data' => $data, 'enable' => 1]);
//        $info[ 'apps' ][ 'disable' ] = Url::internal( 'app=toolbox&module=bt&controller=bt', 'front' )->setQueryString( [
//            'do'     => 'thirdParty',
//            'data'   => $data,
//            'enable' => 0,
//        ] );
//
//        $info[ 'apps' ][ 'app' ] = [];
//        /* @var Application $app */
//        foreach ( $this->apps( true ) as $app ) {
//            $name = $app->_title;
//            $title = $name;
//            $title .= $app->enabled ? ' (Enabled)' : ' (Disabled)';
//            Member::loggedIn()->language()->parseOutputForDisplay( $name );
//            Member::loggedIn()->language()->parseOutputForDisplay( $title );
//
//            $info[ 'apps' ][ 'app' ][ $name ] = [
//                'url'    => Url::internal( 'app=toolbox&module=bt&controller=bt', 'front' )->setQueryString( [
//                    'do'      => 'enableDisableApp',
//                    'data'    => $data,
//                    'enabled' => $app->enabled ? 1 : 0,
//                    'id'      => $app->id,
//                ] ),
//                'title'  => $title,
//                'status' => $app->enabled,
//            ];
//        }
//
//        /* @var Plugin $plugin */
//        foreach ( $this->plugins() as $plugin ) {
//            $name = $plugin->_title;
//            $title = $name;
//            $title .= $plugin->enabled ? ' (Enabled)' : ' (Disabled)';
//            Member::loggedIn()->language()->parseOutputForDisplay( $name );
//            Member::loggedIn()->language()->parseOutputForDisplay( $title );
//            $info[ 'apps' ][ 'app' ][ $name ] = [
//                'url'    => Url::internal( 'app=toolbox&module=bt&controller=bt', 'front' )->setQueryString( [
//                    'do'      => 'enableDisableApp',
//                    'data'    => $data,
//                    'enabled' => $plugin->enabled ? 1 : 0,
//                    'id'      => $plugin->id,
//                ] ),
//                'title'  => $title,
//                'status' => $plugin->enabled,
//            ];
//        }

        $app = Request::i()->app;
        $info['git_url'] = Url::internal('app=toolbox&module=bt&controller=bt', 'front')->setQueryString([
            'do' => 'gitInfo',
            'id' => $app,
        ]);

//         $sources = [];
//         foreach ($this->apps(false) as $app) {
//             $title = $app->_title;
//             Member::loggedIn()->language()->parseOutputForDisplay($title);
//
//             $sources[] = [
//                 'url'  => ,
//                 'name' => $title
//             ];
//         }
//         $info['sources'] = $sources;
        return $info;
    }

    /**
     * @return array|string
     * @throws RuntimeException
     */
    protected function getLocation()
    {
        $location = [];
        if (isset(Request::i()->app)) {
            $location[] = Request::i()->app;
        }

        if (isset(Request::i()->module)) {
            $location[] = 'modules';
            if (Dispatcher::hasInstance()) {
                if (Dispatcher::i() instanceof Dispatcher\Front) {
                    $location[] = 'front';
                } else {
                    $location[] = 'admin';
                }
            }
            $location[] = Request::i()->module;
        }

        if (isset(Request::i()->controller)) {
            $location[] = '_' . Request::i()->controller;
        }

        $do = Request::i()->do ?? 'manage';

        $class = 'IPS\\' . implode('\\', $location);
        $location = $class . '::' . $do;
        $link = null;
        $url = null;
        $line = null;
        try {
            $reflection = new ReflectionClass($class);
            $method = $reflection->getMethod($do);
            $line = $method->getStartLine();
            $declaredClass = $method->getDeclaringClass();
            $url = $declaredClass->getFileName();
            $link = (new Editor())->replace($url);
            $location .= ':' . $line;
        } catch (Exception $e) {
        }

        if ($link) {
            $url = (new Editor())->replace($url, $line);
            return '<a href="' . $url . '">' . $location . '</a>';
        }

        return $location;
    }

    /**
     * @param bool $skip
     *
     * @return array
     */
    public function apps($skip = true): array
    {
        if (NO_WRITES) {
            return [];
        }

        $dtApps = [];
        if ($skip === true) {
            $dtApps = [
                'toolbox',
            ];
        }
        $apps = [];

        foreach (Application::applications() as $app) {
            if (!in_array($app->directory, IPS::$ipsApps, true)) {
                if (in_array($app->directory, $dtApps, true)) {
                    continue;
                }

                $apps[] = $app;
            }
        }

        return $apps;
    }

    /**
     * @return string|null
     * @throws UnexpectedValueException
     */
    protected function environment(): ?string
    {
        if (!Settings::i()->dtprofiler_enabled_enivro) {
            return null;
        }

        $data = [];

        if (!empty($_GET)) {
            foreach ($_GET as $key => $val) {
                if (is_object($val)) {
                    continue;
                }
                if (!is_array($val)) {
                    $val = json_decode($val, true) ?? $val;
                }

                $data[$key] = [
                    'name' => Theme::i()
                                   ->getTemplate('dtpsearch', 'toolbox', 'front')
                                   ->keyvalue('$_GET : ' . $key, $val)
                ];
            }
        }

        if (!empty($_POST)) {
            foreach ($_POST as $key => $val) {
                if (is_object($val)) {
                    continue;
                }
                if (!is_array($val)) {
                    $val = json_decode($val, true) ?? $val;
                }

                $data[$key] = [
                    'name' => Theme::i()
                                   ->getTemplate('dtpsearch', 'toolbox', 'front')
                                   ->keyvalue('$_POST : ' . $key, $val)
                ];
            }
        }

        if (!empty(Request::i()->returnData())) {
            $request = Request::i()->returnData();
            foreach ($request as $key => $val) {
                if (is_object($val)) {
                    continue;
                }
                if (!is_array($val)) {
                    $val = json_decode($val, true) ?? $val;
                }
                $data[$key] = [
                    'name' => Theme::i()
                                   ->getTemplate('dtpsearch', 'toolbox', 'front')
                                   ->keyvalue('$_REQUEST : ' . $key, $val)
                ];
            }
        }

        if (!empty($_COOKIE)) {
            foreach ($_COOKIE as $key => $val) {
                if (!is_array($val)) {
                    $val = json_decode($val, true) ?? $val;
                }
                $data[$key] = [
                    'name' => Theme::i()
                                   ->getTemplate('dtpsearch', 'toolbox', 'front')
                                   ->keyvalue('$_COOKIE : ' . $key, $val)
                ];
            }
        }

        //        if ( !empty( $_SESSION ) ) {
        //            foreach ( $_SESSION as $key => $val ) {
        //                if ( is_object( $val ) ) {
        //                    continue;
        //                }
        //                if ( !is_array( $val ) ) {
        //                    $val = json_decode( $val, \true ) ?? $val;
        //                }
        //                $data[ $key ] = [ 'name' => Theme::i()->getTemplate( 'dtpsearch', 'toolbox', 'front' )->keyvalue( '$_SESSION : ' . $key, $val ) ];
        //            }
        //        }

        if (!empty($_SERVER)) {
            foreach ($_SERVER as $key => $val) {
                if (is_object($val)) {
                    continue;
                }
                if (!is_array($val)) {
                    $val = json_decode($val, true) ?? $val;
                }
                $data[$key] = [
                    'name' => Theme::i()
                                   ->getTemplate('dtpsearch', 'toolbox', 'front')
                                   ->keyvalue('$_SERVER : ' . $key, $val)
                ];
            }
        }

        $return = null;
        if (is_array($data) && count($data)) {
            $return = Theme::i()
                ->getTemplate('dtpsearch', 'toolbox', 'front')
                ->button(
                    'Environment',
                    'environment',
                    'Environment Variables.',
                    $data,
                    json_encode($data),
                    count($data),
                    'random',
                    true,
                    false
                );
        }

        return $return;
    }

    /**
     * @return array
     */
    public function plugins(): array
    {
        if (NO_WRITES) {
            return [];
        }

        $plugins = [];

        foreach (Plugin::plugins() as $plugin) {
            if ($plugin->enabled) {
                $plugins[] = $plugin;
            }
        }

        return $plugins;
    }

    /**
     * @param $info
     *
     * @throws InvalidArgumentException
     * @throws OutOfRangeException
     */
    public function getLastCommitId(&$info): void
    {
        if (Settings::i()->dtprofiler_git_data) {
            $app = Request::i()->id;
            $path = \IPS\Application::getRootPath() . '/applications/' . $app . '/.git/';
            //            print_r($path);exit;
            if (is_dir($path) && function_exists('exec')) {
                $app = Application::load($app);
                $name = $app->_title;
                Member::loggedIn()->language()->parseOutputForDisplay($name);
                $git = new Git($path);
                $id = null;
                $branch = null;
                $msg = [];
                $branches = null;
                $id = $git->getLastCommitId();
                $msg = $git->getLastCommitMessage();
                $branch = $git->getCurrentBranchName();
                $aBranches = $git->getBranches();
                $branches = [];
                if (!empty($aBranches)) {
                    foreach ($aBranches as $val) {
                        $branches[] = [
                            'name' => $val,
                        ];
                    }
                }

                $info = [
                    'version'  => $app->version,
                    'app'      => $name,
                    'id'       => mb_substr($id, 0, 6),
                    'fid'      => $id,
                    'msg'      => implode('<br>', $msg),
                    'branch'   => $branch,
                    'branches' => $branches,
                ];
            }
        }
    }

    /**
     * @param $info
     *
     * @throws InvalidArgumentException
     */
    public function hasChanges(&$info): void
    {
        if (function_exists('exec')) {
            /* @var Application $app */
            foreach (Application::enabledApplications() as $app) {
                $path = \IPS\Application::getRootPath() . '/applications/' . $app->directory . '/.git/';
                if (is_dir($path)) {
                    $name = $app->_title;
                    Member::loggedIn()->language()->parseOutputForDisplay($name);
                    $git = new Git($path);
                    if ($git->hasChanges()) {
                        $info['changes'][] = [
                            'name'      => $name,
                            'directory' => $app->directory,
                        ];
                    }
                }
            }
        }
    }
}
