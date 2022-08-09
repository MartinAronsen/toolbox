<?php

/**
 * @brief       Adminer Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox
 * @since       5.0.0
 * @version     -storm_version-
 */


namespace IPS\toolbox\modules\admin\settings;

use Exception;
use IPS\Http\Url;
use IPS\Output;
use IPS\Theme;
use IPS\toolbox\Application; 
use IPS\Request;
 
use function libxml_use_internal_errors;
use function ob_end_clean; 
use function ob_start; 
use function pq;
use function preg_replace;
use function preg_replace_callback;
use function str_replace;

if (!\defined('\IPS\SUITE_UNIQUE_KEY')) {
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * adminer
 */
class _adminer extends \IPS\Dispatcher\Controller
{
  /**
   * @brief    Has been CSRF-protected
   */
  public static $csrfProtected = true;
  /**
   * Execute
   *
   * @return	void
   */
  public function execute()
  {
    \IPS\Dispatcher::i()->checkAcpPermission('adminer_manage');
    parent::execute();
  }

  /**
   * ...
   *
   * @return	void
   */
  protected function manage()
  {
    if (\IPS\Request::i()->isAjax()) {
      $this->adminer();
    } else {
      ob_start();

      require Application::getRootPath(
        'toolbox'
      ) . '/applications/toolbox/sources/Profiler/AdminerDb/db.php';
      $content = ob_get_contents();
      try {
        ob_end_clean();
      } catch (Exception $e) {
      }

      $content = preg_replace_callback('#<html (.*)#', static function ($m) {
        return '<html ' . $m[1] . '<head>';
      }, $content);

      $content = preg_replace_callback('#<body (.*)#', static function ($m) {
        return '</head><body ' . $m[1];
      }, $content);

      /* Swap out certain tags that confuse phpQuery */
      $content = preg_replace('/<(\/)?(html|head|body)(>| (.+?))/', '<$1temp$2$3', $content);
      $content = str_replace('<!DOCTYPE html>', '<hypertemp></hypertemp>', $content);
      $content = '<div http-equiv="Content-Type" content="text/html; charset=utf-8"></div><ipscontent id="ipscontent">' . $content . '</ipscontent>';
      /* Load phpQuery  */
      require_once Application::getRootPath('core') . '/system/3rd_party/phpQuery/phpQuery.php';
      libxml_use_internal_errors(true);
      $phpQuery = \phpQuery::newDocumentHTML($content);
      $css = [];
      $js = [];
      $url = (string)Url::internal('app=toolbox&module=settings&controller=adminer');
      /** @var \DOMElement $link */
      foreach ($phpQuery->find('link') as $link) {
        if ($link->getAttribute('rel') !== 'stylesheet') {
          continue;
        }
        $l = $link->getAttribute('href');
        if ($l) {
          $css[] = $url . str_replace('?', '&', $l);
          pq($link)->remove();
        }
      }
      /** @var \DOMElement $script */
      foreach ($phpQuery->find('script') as $script) {
        $l = $script->getAttribute('src');
        if ($l) {
          pq($script)->remove();
          Output::i()->jsFiles[] = $url . str_replace('?', '&', $l);
        } else {
          $d = pq($script)->html();
          $d = str_replace('?server', $url . '&server', $d);
          pq($script)->html($d);
        }
      }
      foreach ($phpQuery->find('input') as $script) {
        $l = $script->getAttribute('src');
        if ($l) {
          pq($script)->attr('src', $url . str_replace('?', '&', $l));
        }
      }
      foreach ($phpQuery->find('form') as $form) {
        $l = $form->getAttribute('action');
        if (!$l) {
          pq($form)->attr('action', (string)\IPS\Request::i()->url())
            ->append('<input type="hidden" name="formSubmitted" value="1">')
            ->append('<input type="hidden" name="target" value="' . \IPS\Settings::i()->getFromConfGlobal('sql_database') . '">');
        }
      }


      $url2 = Url::internal('app=toolbox&module=settings&controller=adminer');
      if(Request::i()->dbApp){
          $url2 = $url2->setQueryString(['dbApp'=>Request::i()->dbApp]);
      }
      $url2 = (string) $url2;
      foreach ($phpQuery->find('a') as $a) {
        $a = pq($a);
        $ref = $a->attr('href');
        if (mb_strpos($ref, '?server=') !== false) {
          $ref = str_replace('?server', $url2 . '&server', $ref);
        }
        $a->attr('href', $ref);
      }
      $return = $phpQuery->find('#' . 'ipscontent')->find('tempbody')->html();

      /* Swap back certain tags that confuse phpQuery */
      $return = preg_replace('/<(\/)?temp(html|head|body)(.*?)>/', '<$1$2$3>', $return);
      $return = str_replace('<hypertemp></hypertemp>', '<!DOCTYPE html>', $return);
      Application::addJs(['admin_click']);
      Application::addCss(['admin_adminer']);
      Output::i()->output = Theme::i()->getTemplate('adminer', 'toolbox', 'admin')->adminer($return);
    }
  }

  protected function adminer()
  {
    ob_start();

    require Application::getRootPath(
      'toolbox'
    ) . '/applications/toolbox/sources/Profiler/AdminerDb/db.php';
    $content = ob_get_contents();
    try {
      ob_end_clean();
    } catch (Exception $e) {
    }
    Output::i()->sendOutput($content);
  }
}
