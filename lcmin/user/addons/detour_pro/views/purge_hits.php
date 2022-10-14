<div class="panel box ee<?=$ee_ver?>">
    <h1 class="panel-heading">Purge Hit Counter</h1>

    <div class="panel-body txt-wrap">
        <h3>You currently have <?php echo number_format($total_detour_hits); ?> rows in your Detour Hits table.</h3>

        <p>Detour Pro tracks hits on a granular level by recording the Detour and the date of execution. This can create many rows in the database. You may purge the hit data by clicking the button below. You may also disable the hit counter under the settings. As of Detour Pro 1.5, the hit counter is turned off by default.</p>

        <p class="notice">All Detour Pro Hit Counter data will be deleted!</p>

    <?php
    echo form_open($action_url);

    echo form_submit(array('name' => 'submit', 'value' => ee()->lang->line('btn_purge_detours'), 'class' => 'btn submit action'));
    echo form_close();
    ?>
    </div>
</div>
