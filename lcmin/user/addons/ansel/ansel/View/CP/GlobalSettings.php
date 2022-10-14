<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

/** @var BuzzingPixel\Ansel\Service\GlobalSettings $globalSettings */
/** @var EllisLab\ExpressionEngine\Service\URL\URLFactory $cpUrl */
/** @var array $excludedItems */
/** @var string $lastKey */

$splitLeft = 'w-7';
$splitRight = 'w-9';
?>

<div class="box">
	<? /* Page title */ ?>
	<h1><?=lang('global_settings')?></h1>

	<? /* Start the settings form */ ?>
	<?=form_open(
		$cpUrl->make('addons/settings/ansel', array(
			'controller' => 'GlobalSettings'
		)),
		array(
			'class' => 'settings'
		)
	)?>

		<? /* Get inline CP alerts */ ?>
		<?=ee('CP/Alert')->getAllInlines()?>

		<? /* Iterate through settings */ ?>
		<?php foreach ($globalSettings as $key => $setting) : ?>
			<? /* Check for excluded items */ ?>
			<?php
			if (in_array($key, $excludedItems)) {
				continue;
			}
			?>

			<?php //var_dump($setting); ?>

			<?php // @codingStandardsIgnoreStart ?>
			<fieldset class="col-group<?php if ($key === $lastKey) : ?> last<?php endif; ?>">
				<?php // @codingStandardsIgnoreEnd ?>
				<div class="setting-txt col <?=$splitLeft?>">
					<h3><?=lang("{$key}")?></h3>
					<em><?=lang("{$key}_explain")?></em>
				</div>
				<div class="setting-field col <?=$splitRight?> last">
					<?php if ($globalSettings->getType($key) === 'bool') : ?>
						<?php $this->embed('ee:_shared/form/field', array(
							'grid' => false,
							'field_name' => $key,
							'field' => array(
								'type' => 'yes_no',
								'value' => $setting
							)
						));?>
					<?php else : ?>
						<input
							<?php if ($globalSettings->getType($key) === 'int') : ?>
							type="number"
							<?php else : ?>
							type="text"
							<?php endif; ?>
							name="<?=$key?>"
							value="<?=$setting?>"
							<?php if ($globalSettings->getType($key) === 'int') : ?>
							min="0"
							<?php endif; ?>
							<?php if ($key === 'default_image_quality') : ?>
							max="100"
							<?php endif; ?>
							id="<?=$key?>"
						>
					<?php endif; ?>
				</div>
			</fieldset>
		<?php endforeach; ?><? /* End Iterate through settings */ ?>

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
