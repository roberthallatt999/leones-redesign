<div class="box home-layout low-search-home">
	<h1><?=lang('low_search_module_name')?></h1>
	<div class="info">
		<p>
			<?=lang('low_search_module_description')?>
			&mdash; v<?=$version?>
		</p>

		<?php if ($member_group == 1): ?>
		<ul class="arrow-list">
			<!-- <li>
				<a href="http://gotolow.com/search">Documentation</a>
			</li> -->
			<li>
				<span>Open Search URL:</span>
				<code onclick="prompt('<?=lang('build_index_url')?>', this.innerText);"><?=$search_url?></code>
			</li>
			<?php if ($settings['license_key']): ?>
				<li>
					<span><?=lang('build_index_url')?>:</span>
					<code onclick="prompt('<?=lang('build_index_url')?>', this.innerText);"><?=$build_url?></code>
				</li>
			<?php endif; ?>
		</ul>
		<?php endif; ?>
	</div>
</div>
