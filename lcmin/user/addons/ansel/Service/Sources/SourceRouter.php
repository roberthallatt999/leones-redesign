<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Service\Sources;

use BuzzingPixel\Ansel\Model\File as FileModel;
use BuzzingPixel\Ansel\Service\Sources\Ee as EESource;
use BuzzingPixel\Ansel\Service\Sources\Treasury as TreasurySource;
use BuzzingPixel\Ansel\Service\Sources\Assets as AssetsSource;

/**
 * Class SourceRouter
 *
 * @method string getFileChooserLink(mixed $identifier)
 * @method string uploadFile(mixed $identifier, string $filePath, string $subFolder = null, bool $insertTimestamp = false)
 * @method void deleteFile(mixed $identifier, string $fileName, string $subFolder = null)
 * @method FileModel addFile(mixed $identifier, string $filePath)
 * @method void removeFile(mixed $fileIdentifier)
 * @method string getSourceUrl(mixed $identifier)
 * @method string getFileUrl(mixed $fileIdentifier)
 * @method null|FileModel getFileModel(mixed $fileIdentifier)
 * @method string cacheFileLocallyById(mixed $fileIdentifier)
 * @method array getSourceModels(array $ids)
 * @method array getFileModels(array $ids)
 */
class SourceRouter
{
	/**
	 * @var string $source
	 */
	private $source = 'ee';

	/**
	 * @var EESource $eeSource
	 */
	private $eeSource;

	/**
	 * @var TreasurySource $treasurySource
	 */
	private $treasurySource;

	/**
	 * @var AssetsSource $assetsSource
	 */
	private $assetsSource;

	/**
	 * Constructor
	 *
	 * @param EESource $eeSource
	 * @param TreasurySource $treasurySource
	 * @param AssetsSource $assetsSource
	 */
	public function __construct(
		EESource $eeSource,
		$treasurySource,
		$assetsSource
	) {
		// Inject dependencies
		$this->eeSource = $eeSource;
		$this->treasurySource = $treasurySource;
		$this->assetsSource = $assetsSource;
	}

	/**
	 * Call magic method
	 *
	 * @param $name
	 * @param $args
	 * @return mixed
	 */
	public function __call($name, $args)
	{
		// Call the method on the source class and apply the arguments
		return call_user_func_array(
			array(
				$this->{"{$this->source}Source"},
				$name
			),
			$args
		);
	}

	/**
	 * Set the source to use
	 *
	 * @param string $source ee|assets|treasury
	 */
	public function setSource($source)
	{
		// These are the accepted values of $source
		$accepted = array(
			'ee',
			'treasury',
			'assets'
		);

		// Make sure the value is accepted
		if (! in_array($source, $accepted)) {
			return;
		}

		// Set the source
		$this->source = $source;
	}
}
