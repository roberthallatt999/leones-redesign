<div class="alert inline success">
	<h3>Done!</h3>
	<p>
		<?=sprintf(lang('replaced_x_with_y'), htmlspecialchars($feedback['keywords']), htmlspecialchars($feedback['replacement']))?>
		<?php if ($feedback['total_entries'] == 1): ?>
			<?=lang('in_1_entry')?>.
		<?php else: ?>
			<?=sprintf(lang('in_n_entries'), $feedback['total_entries'])?>.
		<?php endif; ?>
	</p>
</div>