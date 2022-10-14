<?php if ($total_entries = count($preview)): ?>

<?=form_open($form_action, 'id="low-previewed-entries"', array('encoded_preview' => $encoded_preview))?>

	<div class="low-replace">

		<fieldset class="low-inline-form">
			<input type="text" id="low-replacement" name="replacement" autocomplete="off" placeholder="Replacement" />
			<input type="submit" class="btn" value="<?=lang('replace_selected')?>">
		</fieldset>

		<h2><?=lang('matching_entries_for')?> “<?=$keywords?>”: <?=$total_entries?></h2>

		<section class="item-wrap log">

			<?php if ($total_entries > 1): ?>
				<label class="ctrl-all"><input class="low-select-all" type="checkbox"> <span><?=lang('select_all')?></span></label>
			<?php endif; ?>

			<?php foreach($preview as $row): ?>

				<div class="item">
					<ul class="toolbar">
						<li class="edit"><a href="<?=$row['edit_entry_url']?>"></a></li>
					</ul>
					<h3>
						<input type="checkbox" name="entries[<?=$row['channel_id']?>][]" value="<?=$row['entry_id']?>" />
						<?=htmlspecialchars($channels[$row['channel_id']]['channel_title'])?>:
						<b><?=htmlspecialchars($row['title'])?></b>
					</h3>
					<div class="message">
						<dl>
							<?php foreach ($row['matches'] as $field_id => $matches): ?>
								<dt><?=$channels[$row['channel_id']]['fields'][$field_id]?>:</dt>
								<?php foreach ($matches as $match): ?>
									<dd>&hellip;<?=$match?>&hellip;</dd>
								<?php endforeach; ?>
							<?php endforeach; ?>
						</dl>
					</div>
				</div>

			<?php endforeach; ?>
		</section>

	</div>

</form>

<?php else: ?>

	<div class="empty no-results">
		<p><?=lang('no_matching_entries_found')?></p>
	</div>

<?php endif; ?>