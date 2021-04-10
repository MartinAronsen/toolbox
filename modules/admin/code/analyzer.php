<?php

/**
 * @brief       View Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox: Code Analyzer
 * @since       1.0.0
 * @version     -storm_version-
 */

namespace IPS\toolbox\modules\admin\code;

use Exception;
use InvalidArgumentException;
use IPS\Application;
use IPS\Data\Store;
use IPS\Dispatcher\Admin;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\MultipleRedirect;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use IPS\toolbox\Code\Db;
use IPS\toolbox\Code\FileStorage;
use IPS\toolbox\Code\InterfaceFolder;
use IPS\toolbox\Code\Langs;
use IPS\toolbox\Code\Settings;

use OutOfRangeException;
use RuntimeException;
use UnexpectedValueException;

use function array_merge;
use function defined;
use function header;
use function in_array;
use function is_array;
use function ksort;
use function round;

/* To prevent PHP errors (extending class does not exist) revealing path */

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * view
 */
class _analyzer extends Controller
{

    /**
     * @inheritdoc
     * @throws RuntimeException
     */
    public function execute()
    {
        Output::i()->cssFiles = array_merge(Output::i()->cssFiles, Theme::i()->css('dtcode.css', 'toolbox', 'admin'));
        Output::i()->jsFiles = array_merge(
            Output::i()->jsFiles,
            Output::i()->js('admin_toggles.js', 'toolbox', 'admin')
        );

        Admin::i()->checkAcpPermission('view_manage');

        parent::execute();
    }

    /**
     * @inheritdoc
     */
    protected function manage()
    {
        $form = new Form();
        (new FileStorage('chrono'))->check();
        (new Langs('chrono'))->verify();
        //(new Db('chrono'))->check();
        foreach (Application::applications() as $key => $val) {
            if (!defined('DTCODE_NO_SKIP') && in_array($val->directory, IPS::$ipsApps, true)) {
                continue;
            }
            $apps[$val->directory] = Member::loggedIn()->language()->addToStack("__app_{$val->directory}");
        }

        ksort($apps);

        $apps = new Form\Select(
            'dtcode_app', null, true, [
                'options' => $apps,
            ]
        );

        $form->add($apps);

        if ($values = $form->values()) {
            Output::i()->redirect(
                $this->url->setQueryString(
                    [
                        'do'          => 'queue',
                        'application' => $values['dtcode_app'],
                    ]
                )->csrf()
            );
        }

        Output::i()->output = $form;
    }

    /**
     *
     * @throws Exception
     */
    protected function queue()
    {
        Output::i()->output = new MultipleRedirect(

            $this->url->setQueryString(
                [
                    'do'          => 'queue',
                    'application' => Request::i()->application,
                ]
            )->csrf(), function ($data) {
            $total = 7;
            $percent = round(100 / $total);
            $app = Request::i()->application;
            $complete = 0;
            if (is_array($data) && isset($data['complete'])) {
                $app = (string)$data['app'];
                $complete = (int)$data['complete'];
            }

            $warnings = [];

            if ($complete !== 0 && isset(Store::i()->dtcode_warnings)) {
                $warnings = Store::i()->dtcode_warnings;
            }

            switch ($complete) {
                default:
                    $complete++;
                    break;
                case 0:
                    $warnings['langs_check'] = (new Langs($app))->check();
                    $complete = 1;
                    break;
                case 1:
                    $verify = (new Langs($app))->verify();
                    $warnings['langs_verify'] = $verify['langs'] ?? [];
                    $warnings['root_path'] = $verify['root'] ?? [];
                    $complete = 2;
                    break;
                case 2:
                    $warnings['settings_check'] = (new Settings($app))->buildSettings()->check();
                    $complete = 3;
                    break;
                case 3:
                    $warnings['settings_verify'] = (new Settings($app))->buildSettings()->verify();
                    $complete = 4;
                    break;
                case 4:
                    $warnings['sql_check'] = (new Db($app))->check();
                    $complete = 5;
                    break;
                case 5:
                    $warnings['filestorage_check'] = (new FileStorage($app))->check();
                    $complete = 6;
                    break;
                case 6:
                    $warnings['interface_occupied'] = (new InterfaceFolder($app))->check();
                    $complete = 7;
                    break;
            }

            Store::i()->dtcode_warnings = $warnings;

            if ($complete > $total) {
                return null;
            }

            $language = Member::loggedIn()->language()->addToStack(
                'dtcode_queue_complete',
                false,
                [
                    'sprintf' => [
                        $complete,
                        $total,
                    ],
                ]
            );

            return [
                ['complete' => $complete, 'app' => $app],
                $language,
                $percent * $complete,
            ];
        }, function () {
            $url = Url::internal('app=toolbox&module=code&controller=analyzer&do=results');
            $url->setQueryString(['application' => Request::i()->application]);
            Output::i()->redirect($url->csrf(), 'dtcode_analyzer_complete');
        }
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws OutOfRangeException
     * @throws UnexpectedValueException
     */
    protected function results()
    {
        $app = null;
        if (isset(Request::i()->application)) {
            $app = Application::load(Request::i()->application);
        }

        $title = 'dtcode_results';
        $options = [];
        if ($app !== null) {
            $title = 'dtcode_results_app';
            $options = ['sprintf' => [Member::loggedIn()->language()->addToStack('__app_' . $app->directory)]];
        }

        Output::i()->title = Member::loggedIn()->language()->addToStack($title, false, $options);

        if (isset(Store::i()->dtcode_warnings)) {
            /**
             * @var array $warnings
             */
            $warnings = Store::i()->dtcode_warnings;
            $output = '';
            foreach ($warnings as $key => $val) {
                switch ($key) {
                    case 'langs_check':
                        $output .= Theme::i()->getTemplate('code')->results(
                            $val['langs'] ?? [],
                            'dtcode_langs_php',
                            [],
                            true
                        );

                        $output .= Theme::i()->getTemplate('code')->results(
                            $val['jslangs'] ?? [],
                            'dtcode_jslangs_php',
                            [],
                            true
                        );
                        break;
                    case 'langs_verify':
                        $output .= Theme::i()->getTemplate('code')->results(
                            $val,
                            'dtcode_langs_verify',
                            [
                                'File',
                                'Key',
                                'Line'
                            ],
                            true
                        );
                        break;
                    case 'settings_check':
                        $output .= Theme::i()->getTemplate('code')->results(
                            $val,
                            'dtcode_settings_check',
                        );
                        break;
                    case 'settings_verify':
                        $output .= Theme::i()->getTemplate('code')->results(
                            $val,
                            'dtcode_settings_verify'
                        );
                        break;
                    case 'filestorage_check':
                        $output .= Theme::i()->getTemplate('code')->results(
                            $val,
                            'filestorage_check',
                            [
                                'Extension',
                                'Error Message'
                            ]
                        );
                        break;
                    case 'sql_check':
                        $output .= Theme::i()->getTemplate('code')->results(
                            $val,
                            'dtcode_sql',
                            [
                                'File',
                                'Application',
                                'Table',
                                'Definition'
                            ]
                        );
                        break;
                    case 'interface_occupied':
                        $output .= Theme::i()->getTemplate('code')->results(
                            $val,
                            'interface_occupied',
                            [
                                'File'
                            ],
                            true
                        );
                        break;
                    case 'root_path':
                        $output .= Theme::i()->getTemplate('code')->results(
                            $val,
                            'root_path',
                            [
                                'File',
                                'Key',
                                'Line'
                            ]
                        );
                        break;
                }
            }

            Output::i()->output = $output;
        }
    }
}
