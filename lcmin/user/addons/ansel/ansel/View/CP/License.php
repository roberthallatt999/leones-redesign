<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

/** @var BuzzingPixel\Ansel\Service\GlobalSettings $globalSettings */
/** @var EllisLab\ExpressionEngine\Service\URL\URLFactory $cpUrl */
/** @var string $licenseText */

$splitLeft = 'w-6';
$splitRight = 'w-10';

?>

<div class="box">
	<? /* Page title */ ?>
	<h1><?=lang('ansel_license')?></h1>

	<? /* Start the settings form */ ?>
	<?=form_open(
		$cpUrl->make('addons/settings/ansel', array(
			'controller' => 'License'
		)),
		array(
			'class' => 'settings'
		)
	)?>

		<? /* Get inline CP alerts */ ?>
		<?=ee('CP/Alert')->getAllInlines()?>

		<fieldset class="col-group">
			<div class="setting-txt col <?=$splitLeft?>">
				<h3><?=lang('license_agreement')?></h3>
			</div>
			<div class="setting-field col <?=$splitRight?> last">
				<div class="ansel-scroll-wrap">
					<?=$licenseText?>
				</div>
			</div>
		</fieldset>

		<fieldset class="col-group last">
			<div class="setting-txt col <?=$splitLeft?>">
				<h3><?=lang('your_license_key')?></h3>
			</div>
			<div class="setting-field col <?=$splitRight?> last">
				<input
					type="text"
					name="license_key"
					value="<?=$globalSettings->license_key?>"
				>
			</div>
		</fieldset>

		<fieldset class="form-ctrls">
			<input
				type="submit"
				value="<?=lang('update')?>"
				class="btn"
				data-submit-text="<?=lang('update')?>"
				data-work-text="<?=lang('updating')?>"
			>
		</fieldset>

	<?=form_close()?><? /* Close settings form */ ?>
</div>
