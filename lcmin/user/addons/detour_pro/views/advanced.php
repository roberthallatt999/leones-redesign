<?php
	// Category
	$categories = array(
		'cat_1' => 'Category 1',
		'cat_2' => 'Category 2'
	);
?>
<div class="box ee<?=$ee_ver?>">
	<h1><?php echo ee()->lang->line('label_'.(!empty($id) ? 'edit' : 'add').'_detour'); ?></h1>

	<?php echo form_open($action_url, array('class'=>'settings')); ?>

	<?php if($id) echo form_hidden('id', $id); ?>

	<fieldset class="col-group">
		<div class="setting-txt col w-8">
			<h3><?php echo ee()->lang->line('label_original_url'); ?></h3>
			<em><?php echo ee()->lang->line('subtext_original_url'); ?></em>
		</div>
		<div class="setting-field col w-8 last">
			<?php echo form_input(
				array(
					'name' => 'original_url',
					'id' => 'original_url',
					'value' => $original_url,
					'size' => '50'
				)); ?>
		</div>
	</fieldset>

	<fieldset class="col-group">
		<div class="setting-txt col w-8">
			<h3><?php echo ee()->lang->line('label_new_url'); ?></h3>
			<em><?php echo ee()->lang->line('subtext_new_url'); ?></em>
		</div>
		<div class="setting-field col w-8 last">
			<?php echo form_input(
				array(
					'name' => 'new_url',
					'id' => 'new_url',
					'value' => $new_url,
					'size' => '50'
				)); ?>
		</div>
<?php if(!$allow_trailing_slash) { ?>
		<div class="settings-note">
			Note: Trailing slashes will be removed from your Detours. To disable this, turn on the "Allow Trailing Slashes" setting.
		</div>
<?php } ?>
	</fieldset>

	<fieldset class="col-group">
		<div class="setting-txt col w-8">
			<h3><?php echo ee()->lang->line('label_detour_method'); ?></h3>
			<em><?php echo ee()->lang->line('subtext_detour_method'); ?></em>
		</div>
		<div class="setting-field col w-8 last">
			<?php echo form_dropdown('detour_method', $detour_methods, $detour_method); ?>
		</div>
	</fieldset>

	<fieldset class="col-group">
		<div class="setting-txt col w-8">
			<h3><?php echo ee()->lang->line('label_start_date'); ?></h3>
			<em><?php echo ee()->lang->line('subtext_start_date'); ?></em>
		</div>
		<div class="setting-field col w-8 last">
			<?php echo form_input(
				array(
					'name' => 'start_date',
					'id' => 'start_date',
					'value' => $start_date,
					'size' => '30',
					'class' => ($ee_ver == 2 ? 'datepicker' : ''),
					'rel' => ($ee_ver > 2 ? 'date-picker' : '')
				));
			?>
		</div>
	</fieldset>

<?php
	if($start_date)
	{
?>
	<fieldset class="col-group">
		<div class="setting-txt col w-8">
			<h3><?php echo ee()->lang->line('label_clear_start_date'); ?></h3>
			<em><?php echo ee()->lang->line('subtext_clear_start_date'); ?></em>
		</div>
		<div class="setting-field col w-8 last">
			<label><?php echo form_checkbox('clear_start_date', '1'); ?> <?php echo ee()->lang->line('label_clear_start_date'); ?></label>
		</div>
	</fieldset>
<?php
	}
?>

	<fieldset class="col-group">
		<div class="setting-txt col w-8">
			<h3><?php echo ee()->lang->line('label_end_date'); ?></h3>
			<em><?php echo ee()->lang->line('subtext_end_date'); ?></em>
		</div>
		<div class="setting-field col w-8 last">
			<?php echo form_input(
				array(
					'name' => 'end_date',
					'id' => 'end_date',
					'value' => $end_date,
					'size' => '30',
					'class' => ($ee_ver == 2 ? 'datepicker' : ''),
					'rel' => ($ee_ver > 2 ? 'date-picker' : '')
				));
			?>
		</div>
	</fieldset>

<?php
	if($end_date)
	{
?>
	<fieldset class="col-group">
		<div class="setting-txt col w-8">
			<h3><?php echo ee()->lang->line('label_clear_end_date'); ?></h3>
			<em><?php echo ee()->lang->line('subtext_clear_end_date'); ?></em>
		</div>
		<div class="setting-field col w-8 last">
			<label><?php echo form_checkbox('clear_end_date', '1'); ?> <?php echo ee()->lang->line('label_clear_end_date'); ?></label>
		</div>
	</fieldset>
<?php
	}
?>

	<?php echo form_submit(array('name' => 'submit', 'value' => ee()->lang->line('btn_save_detour'), 'class' => 'btn submit action')); ?>

	<?php echo form_close(); ?>
</div>