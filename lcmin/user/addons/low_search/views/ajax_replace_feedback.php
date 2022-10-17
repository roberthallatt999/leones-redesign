<?php
$message = sprintf(lang('replaced_x_with_y'), htmlspecialchars($feedback['keywords']), htmlspecialchars($feedback['replacement']));
if ($feedback['total_entries'] == 1) {
    $message .= lang('in_1_entry');
} else {
    $message .= sprintf(lang('in_n_entries'), $feedback['total_entries']);
}
?>
<?php if (version_compare(APP_VER, '6.0', '>=')) : ?>
    <div class="app-notice-wrap">
    <?php
        echo ee('CP/Alert')->makeInline('shared-form')
            ->asSuccess()
            ->withTitle(lang('success'))
            ->addToBody($message)
            ->render();
    ?>
    </div>
<?php else : ?>
    <div class="alert inline success">
        <h3>Done!</h3>
        <p>
            <?=$message?>
        </p>
    </div>
<?php endif;?>
