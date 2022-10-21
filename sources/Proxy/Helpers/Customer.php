<?php

/**
 * @brief       IPSDataStore Standard
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  toolbox\Proxy
 * @since       -storm_since_version-
 * @version     -storm_version-
 */

namespace IPS\toolbox\Proxy\Helpers;

use Laminas\Code\Generator\DocBlock\Tag\ReturnTag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\Exception\InvalidArgumentException;
use Laminas\Code\Generator\MethodGenerator;

use function defined;
use function header;

if ( ! defined('\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _Customer implements HelpersAbstract
{
	/**
	 * @inheritdoc
	 */
	public function process($class, &$classDoc, &$classExtends, &$body)
	{
		$methodDocBlock = new DocBlockGenerator(
			'',
			null, [
				new ReturnTag('\\' . \IPS\nexus\Customer::class)
			]
		);
		try {
			$body[] = MethodGenerator::fromArray(
				[
					'name'       => 'loggedIn',
					'parameters' => [],
					'body'       => 'return parent::loggedIn();',
					'docblock'   => $methodDocBlock,
					'static'     => true,
				]
			);
		} catch (InvalidArgumentException ) {}
	}
}
