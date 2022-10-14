<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

/** @var \EllisLab\ExpressionEngine\Library\Data\Collection $updatesFeed */

?>

<div class="box">
	<? /* Page title */ ?>
	<h1><?=lang('updates')?></h1>

	<? /* Updates loop */ ?>
	<div class="ansel-updates">
		<?php foreach ($updatesFeed as $update) : ?>
			<?php /** @var \BuzzingPixel\Ansel\Model\UpdateFeedItem $update */ ?>
			<div class="ansel-updates__item">
				<div class="ansel-updates__title-area">
					<?php if ($update->new) : ?>
						<a
							href="<?=$update->downloadUrl?>"
							target="_blank"
							class="btn ansel-updates__download"
						>
							Download
						</a>
					<?php endif; ?>
					<div class="ansel-updates__title"><?=$update->version?></div>
					<div class="ansel-updates__released">
						Released <?=$update->date->format('n/j/Y')?>
					</div>
					<span
						<?php // @codingStandardsIgnoreStart ?>
						class="ansel-updates__status<?php if ($update->new): ?> ansel-updates__status--new<?php endif; ?>"
						<?php // @codingStandardsIgnoreEnd ?>
					>
						<?php if ($update->new) : ?>
							new
						<?php else : ?>
							installed
						<?php endif; ?>
					</span>
				</div>
				<div class="ansel-updates__notes">
					<?=$update->getNotesMarkdown()?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>
