<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

/** @var \BuzzingPixel\Ansel\Model\FieldSettings $fieldSettings */
/** @var \BuzzingPixel\Ansel\Record\Image $row */

$isEE6 = false;

if (defined('APP_VER') &&
	version_compare(APP_VER, '6.0.0-b.1', '>=')
) {
	$isEE6 = true;
}

$rowId = uniqid();

// Check if image is going to have neighbors
$imgHasNeighbors = $fieldSettings->show_title || $fieldSettings->show_caption;

if (! isset($row)) {
	$row = ee('ansel:Noop');
}

?>

<tr class="ansel-table__row js-ansel-row" data-row-id="<?=$rowId?>">
	<td class="ansel-table__column ansel-table__column--handle js-ansel-sort-handle">
		<span class="ansel-table__reorder-icon">
			<span class="ansel-table__reorder-icon-wrapper">
				<?php $this->embed('ansel:Field/Icons/Reorder.svg'); ?>
			</span>
		</span>
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
			<?php
				$cropButtonClasses = 'ansel-image-toolbar__button ansel-image-toolbar__button--crop';

				if ($isEE6) {
					$cropButtonClasses .= ' ansel-image-toolbar__button--is-ee-6';
				}
			?>
			<ul class="ansel-image-toolbar">
				<li class="ansel-image-toolbar__item">
					<a
						title="Crop"
						class="<?=$cropButtonClasses?>"
					>
						<span class="ansel-image-toolbar__button-icon-wrapper ansel-image-toolbar__button-icon-wrapper--crop">
							<?php $this->embed('ansel:Field/Icons/Crop.svg'); ?>
						</span>
					</a>
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
			<label style="width: 100%">
				<input
					type="text"
					name="<?=$fieldSettings->field_name?>[ansel_row_id_<?=$rowId?>][title]"
					maxlength="255"
					value="<?=htmlentities((string) $row->title)?>"
					class="js-ansel-input"
				>
			</label>
		</td>
	<?php endif; ?>
	<?php if ($fieldSettings->show_caption) : ?>
		<td class="ansel-table__column ansel-table__column--input">
			<label style="width: 100%">
				<input
					type="text"
					name="<?=$fieldSettings->field_name?>[ansel_row_id_<?=$rowId?>][caption]"
					maxlength="255"
					value="<?=htmlentities((string) $row->caption)?>"
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
		<ul class="ansel-table__column-toolbar">
			<li class="ansel-table__column-remove">
				<a
					href="#"
					title="remove row"
					class="ansel-table__column-remove-anchor js-ansel-remove-row"
				>
					<span class="ansel-table__column-remove-icon-wrapper">
						<?php $this->embed('ansel:Field/Icons/Close.svg'); ?>
					</span>
				</a>
			</li>
		</ul>
	</td>
</tr>
