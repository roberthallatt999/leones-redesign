<?php
// URI sniffer
$uri = array(
    'ee'  => 'Expression Engine: ee()->uri->uri_string',
    'php' => 'PHP: $_SERVER[\'REQUEST_URI\']',
);

// Default redirect method
$redirects = array(
    '301' => '301 (Permanent)',
    '302' => '302 (Temporary)',
);
?>
<div class="panel box ee<?=$ee_ver?>">
    <h1 class="panel-heading">Settings</h1>

    <?php echo form_open($action_url, array('class' => 'panel-body settings')); ?>

    <fieldset class="col-group">
        <div class="setting-txt col w-8">
            <h3><?php echo ee()->lang->line('label_setting_url_detect'); ?></h3>
            <em><?php echo ee()->lang->line('subtext_setting_url_detect'); ?></em>
        </div>
        <div class="setting-field col w-8 last">
            <?php echo form_dropdown('url_detect', $uri, $settings->url_detect); ?>
        </div>
    </fieldset>

    <fieldset class="col-group">
        <div class="setting-txt col w-8">
            <h3><?php echo ee()->lang->line('label_setting_default_method'); ?></h3>
            <em><?php echo ee()->lang->line('subtext_setting_default_method'); ?></em>
        </div>
        <div class="setting-field col w-8 last">
            <?php echo form_dropdown('default_method', $redirects, $settings->default_method); ?>
        </div>
    </fieldset>

    <fieldset class="col-group">
        <div class="setting-txt col w-8">
            <h3><?php echo ee()->lang->line('label_setting_hit_counter'); ?></h3>
            <em><?php echo ee()->lang->line('subtext_setting_hit_counter'); ?></em>
        </div>
        <div class="setting-field col w-8 last">
            <label><?php echo form_checkbox('hit_counter', 'y', $settings->hit_counter); ?> <?php echo ee()->lang->line('label_setting_hit_counter'); ?></label>
        </div>
    </fieldset>

    <fieldset class="col-group">
        <div class="setting-txt col w-8">
            <h3><?php echo ee()->lang->line('label_setting_allow_trailing_slash'); ?></h3>
            <em><?php echo ee()->lang->line('subtext_setting_allow_trailing_slash'); ?></em>
        </div>
        <div class="setting-field col w-8 last">
            <label><?php echo form_checkbox('allow_trailing_slash', '1', $settings->allow_trailing_slash); ?> <?php echo ee()->lang->line('label_setting_allow_trailing_slash'); ?></label><br />
            <br /><div class="settings-note"><?php echo ee()->lang->line('notice_allow_trailing_slash'); ?></div>
        </div>
    </fieldset>

    <?php echo form_submit(array('name' => 'submit', 'value' => ee()->lang->line('btn_save_settings'), 'class' => 'btn submit action')); ?>

    <?php echo form_close(); ?>
</div>
