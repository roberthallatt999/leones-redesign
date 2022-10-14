<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

/** @var \BuzzingPixel\Ansel\Model\FieldSettings $fieldSettings */
/** @var \BuzzingPixel\Ansel\Record\Image $row */

$rowId = uniqid();

// Check if image is going to have neighbors
$imgHasNeighbors = $fieldSettings->show_title || $fieldSettings->show_caption;

if (! isset($row)) {
	$row = ee('ansel:Noop');
}

?>

<tr class="ansel-table__row js-ansel-row" data-row-id="<?=$rowId?>">
	<td class="ansel-table__column ansel-table__column--handle js-ansel-sort-handle">
		<span class="ico reorder ansel-table__reorder-icon"></span>
	</td>
	<?php
	$imgClasses = 'ansel-table__column';

	if ($imgHasNeighbors) {
		$imgClasses .= ' ansel-table__column--image-has-neighbors';
	}
	?>
	<td class="<?=$imgClasses?>">
		<div class="ansel-table__image-holder js-ansel-image-holder">
			<div class="ansel-table__image-holder-inner js-ansel-image-holder-inner">
				<img
					<?php if ($row->_file_location) : ?>
						<?php
						$type = pathinfo($row->_file_location, PATHINFO_EXTENSION);
						$contents = file_get_contents($row->_file_location);
						$base64 = "data:image/{$type};base64,";
						$base64 .= base64_encode($contents);
						?>
						src="<?=$base64?>"
					<?php else : ?>
						<?php if ($row->getOriginalUrl() === '') : ?>
							data-source-file-missing="true"
							src="<?=$row->getThumbUrl()?>"
						<?php else : ?>
							src="<?=$row->getOriginalUrl()?>"
						<?php endif; ?>
					<?php endif; ?>
					alt=""
					class="js-ansel-row-image"
					style="display: none"
				>
			</div>
			<ul class="toolbar ansel-image-toolbar">
				<li class="ansel-image-toolbar__item">
					<a title="Crop" class="ansel-image-toolbar__button ansel-image-toolbar__button--crop"></a>
				</li>
			</ul>
		</div>
		<?php $this->embed('ansel:Field/RowHiddenInputs', array(
			'rowId' => $rowId,
			'row' => $row
		)); ?>
	</td>
	<?php if ($fieldSettings->show_title) : ?>
		<td class="ansel-table__column ansel-table__column--input">
			<label>
				<input
					type="text"
					name="<?=$fieldSettings->field_name?>[ansel_row_id_<?=$rowId?>][title]"
					maxlength="255"
					value="<?=$row->title?>"
					class="js-ansel-input"
				>
			</label>
		</td>
	<?php endif; ?>
	<?php if ($fieldSettings->show_caption) : ?>
		<td class="ansel-table__column ansel-table__column--input">
			<label>
				<input
					type="text"
					name="<?=$fieldSettings->field_name?>[ansel_row_id_<?=$rowId?>][caption]"
					maxlength="255"
					value="<?=$row->caption?>"
					class="js-ansel-input"
				>
			</label>
		</td>
	<?php endif; ?>
	<?php if ($fieldSettings->show_cover) : ?>
		<td class="ansel-table__column ansel-table__column--cover">
			<label>
				<input
					type="checkbox"
					name="<?=$fieldSettings->field_name?>[ansel_row_id_<?=$rowId?>][cover]"
					value="true"
					class="ansel-table__checkbox js-ansel-input js-ansel-input-cover"
					<?php if ($row->cover) : ?>
					checked
					<?php endif; ?>
				>
			</label>
		</td>
	<?php endif; ?>
	<td class="ansel-table__column ansel-table__column--delete">
		<ul class="toolbar ansel-table__column-toolbar">
			<li class="remove">
				<a href="#" title="remove row" class="js-ansel-remove-row"></a>
			</li>
		</ul>
	</td>
</tr>
