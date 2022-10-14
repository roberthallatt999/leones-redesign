<?php

// @codingStandardsIgnoreStart

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

/** @var \BuzzingPixel\Ansel\Model\FieldSettings $fieldSettings */
/** @var array $langArray */
/** @var array $fieldSettingsArray */
/** @var string $uploadKey */
/** @var string $uploadUrl */
/** @var string $fileChooserLink */
/** @var \EllisLab\ExpressionEngine\Service\Model\Collection $rows */

// Check if image is going to have neighbors
$imgHasNeighbors = $fieldSettings->show_title || $fieldSettings->show_caption;

?>

<div
	class="ansel-field<?php if (in_array($fieldSettings->type, array('grid', 'blocks'))) : ?> js-ansel-grid-field<?php else : ?> js-ansel-field<?php endif; ?>"
	data-field-settings='<?=json_encode($fieldSettingsArray)?>'
	data-lang='<?=json_encode($langArray)?>'
>
	<input
		type="hidden"
		name="<?=$fieldSettings->field_name?>[placeholder]"
		value="placeholder"
		class="js-ansel-field-input-placeholder"
	>

	<div
		class="ansel-field__dropzone ansel-dropzone dropzone js-ansel-dropzone js-ansel-hide-max"
		data-upload-key="<?=$uploadKey?>"
		data-upload-url="<?=$uploadUrl?>"
	></div>

	<div class="ansel-field__choose-from-file-manager js-ansel-hide-max">
		<?=$fileChooserLink?>
	</div>

	<div class="js-ansel-messages"></div>

	<div class="ansel-field__table-wrap">
		<table class="ansel-field__table ansel-table js-ansel-table<?php if (! $rows->count()) : ?> js-hide<?php endif; ?>">
			<thead class="ansel-table__heading">
				<tr class="ansel-table__row">
					<th class="ansel-table__heading-column ansel-table__heading-column--handle"></th>
					<?php
					$imgClasses = 'ansel-table__heading-column';

					if ($imgHasNeighbors) {
						$imgClasses .= ' ansel-table__heading-column--image-has-neighbors';
					}
					?>
					<th class="<?=$imgClasses?>"><?=lang('image')?></th>
					<?php if ($fieldSettings->show_title) : ?>
						<th class="ansel-table__heading-column ansel-table__heading-column--input">
							<?=$fieldSettings->title_label ?: lang('title')?>
						</th>
					<?php endif; ?>
					<?php if ($fieldSettings->show_caption) : ?>
						<th class="ansel-table__heading-column ansel-table__heading-column--input">
							<?=$fieldSettings->caption_label ?: lang('caption')?>
						</th>
					<?php endif; ?>
					<?php if ($fieldSettings->show_cover) : ?>
						<th class="ansel-table__heading-column ansel-table__heading-column--cover">
							<?=$fieldSettings->cover_label ?: lang('cover')?>
						</th>
					<?php endif; ?>
					<th class="ansel-table__heading-column ansel-table__heading-column--delete"></th>
				</tr>
			</thead>
			<tbody class="ansel-table__body js-ansel-body">
				<?php foreach ($rows as $row) : ?>
					<?php $this->embed('ansel:Field/Row', array(
						'row' => $row
					)); ?>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<script type="text/template" class="js-ansel-template__row">
		<?php $this->embed('ansel:Field/Row'); ?>
	</script>

	<script type="text/template" class="js-ansel-template__crop-table">
		<?php $this->embed('ansel:Field/CropTable'); ?>
	</script>

</div>
