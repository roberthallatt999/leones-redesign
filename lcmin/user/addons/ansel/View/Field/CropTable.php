<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

$isEE6 = false;

if (defined('APP_VER') &&
	version_compare(APP_VER, '6.0.0-b.1', '>=')
) {
	$isEE6 = true;
}

?>

<div>
	<div class="ansel-bg-overlay"></div>
	<table class="ansel-crop-table">
		<tbody>
			<tr class="ansel-crop-table__row">
				<td class="ansel-crop-table__cell">
					<img src="" alt="" class="ansel-crop-table__img js-ansel-crop-image">
					<ul class="toolbar ansel-tool-bar">
						<li class="remove js-cancel-crop">
							<?php
								$cancelButtonWrapperClasses = 'ansel-tool-bar__button-icon-wrapper ansel-tool-bar__button-icon-wrapper--cancel';
								if ($isEE6) {
									$cancelButtonWrapperClasses .= ' ansel-tool-bar__button-icon-wrapper--cancel-ee-6';
								}
							?>
							<a class="ansel-tool-bar__anchor ansel-tool-bar__anchor--cancel">
								<span class="<?=$cancelButtonWrapperClasses?>">
									<?php $this->embed('ansel:Field/Icons/Close.svg'); ?>
								</span>
							</a>
						</li>
						<li class="approve js-approve-crop">
							<?php
								$approveButtonWrapperClasses = 'ansel-tool-bar__button-icon-wrapper ansel-tool-bar__button-icon-wrapper--approve';
								if ($isEE6) {
									$approveButtonWrapperClasses .= ' ansel-tool-bar__button-icon-wrapper--approve-ee-6';
								}
							?>
							<a class="ansel-tool-bar__anchor ansel-tool-bar__anchor--approve">
								<span class="<?=$approveButtonWrapperClasses?>">
									<?php $this->embed('ansel:Field/Icons/Checkmark.svg'); ?>
								</span>
							</a>
						</li>
					</ul>
				</td>
			</tr>
		</tbody>
	</table>
</div>
