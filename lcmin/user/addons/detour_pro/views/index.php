<div class="panel box ee<?=$ee_ver?>">
    <div class="tbl-ctrls">
    <div class="panel-heading">
        <?php echo form_open($search_url); ?>
            <fieldset class="tbl-search right">
                <input placeholder="type phrase..." type="text" name="search" value="" style="float: left; margin-right: 5px;">
                <button type="submit" class="btn action submit">search</button>
            </fieldset>
        </form>
        <h1>Dashboard</h1>
    </div>

<?php
echo form_open($delete_action_url, array('class' => 'settings panel-body'));

echo '<div class="tbl-wrap">', "\n";

$searchParam = '';
$searchValue = ee()->input->get('search');

if (!empty($searchValue)) {
    $searchParam = '&search=' . $searchValue;
}

$headingItems[] = array(
    'data'  => '<a href="' . $base_url . '&sort=original_url&sort_dir=' . $sort_dir['original_url'] . $searchParam . '">' . ee()->lang->line('title_url') . '</a>',
    'style' => 'width:33%;',
    'class' => ($sort == 'original_url' ? 'sorting_' . $sort_dir['current'] : ''),
);
$headingItems[] = array(
    'data'  => '<a href="' . $base_url . '&sort=new_url&sort_dir=' . $sort_dir['new_url'] . $searchParam . '">' . ee()->lang->line('title_redirect') . '</a>',
    'style' => 'width:33%;',
    'class' => ($sort == 'new_url' ? 'sorting_' . $sort_dir['current'] : ''),
);
$headingItems[] = array(
    'data'  => '<a href="' . $base_url . '&sort=detour_method&sort_dir=' . $sort_dir['detour_method'] . $searchParam . '">' . ee()->lang->line('title_method') . '</a>',
    'style' => 'width:5%;',
    'class' => ($sort == 'detour_method' ? 'sorting_' . $sort_dir['current'] : ''),
);
$headingItems[] = array(
    'data'  => '<a href="' . $base_url . '&sort=start_date&sort_dir=' . $sort_dir['start_date'] . $searchParam . '">' . ee()->lang->line('title_start') . '</a>',
    'style' => 'width:10%;',
    'class' => ($sort == 'start_date' ? 'sorting_' . $sort_dir['current'] : ''),
);
$headingItems[] = array(
    'data'  => '<a href="' . $base_url . '&sort=end_date&sort_dir=' . $sort_dir['end_date'] . $searchParam . '">' . ee()->lang->line('title_end') . '</a>',
    'style' => 'width:10%;',
    'class' => ($sort == 'end_date' ? 'sorting_' . $sort_dir['current'] : ''),
);

if ($display_hits) {
    $headingItems[] = array(
        'data'  => ee()->lang->line('title_hits'),
        'style' => 'width:4%;',
    );
}

$headingItems[] = array(
    'data' => 'Delete',
);

ee()->table->set_template('cp_pad_table_template');
ee()->table->set_heading($headingItems);

$hasDetours = false;
if (is_array($current_detours) && count($current_detours) == 0) {
    ee()->table->add_row(
        array('data' => ee()->lang->line('dir_no_detours'), 'colspan' => 6)
    );
} else {
    $hasDetours = true;
    foreach ($current_detours as $detour) {
        $rowItems   = array();
        $rowItems[] = '<a href="' . $detour['update_link'] . '">' . $detour['original_url'] . '</a>';
        $rowItems[] = $detour['new_url'];
        $rowItems[] = '<strong>' . $detour['detour_method'] . '</strong>';
        $rowItems[] = $detour['start_date'];
        $rowItems[] = $detour['end_date'];
        if ($display_hits) {
            $rowItems[] = $detour['hits'];
        }
        $rowItems[] = '<input type="checkbox" name="detour_delete[]" value="' . $detour['detour_id'] . '" />';

        ee()->table->add_row($rowItems);
    }
}

if (isset($pagination) && !empty($pagination)) {
    ee()->table->add_row(
        array('data' => $pagination, 'colspan' => 6)
    );
}

echo ee()->table->generate();
ee()->table->clear();

echo '</div>', "\n";

echo '<div class="panel-footer">';

if ($hasDetours) {
    echo form_submit(array('name' => 'submit', 'value' => ee()->lang->line('btn_delete_detours'), 'class' => 'btn btn-right submit action'));
}

echo '<a href="', $add_detour_link, '" class="btn submit">', ee()->lang->line('label_add_detour'), '</a>';

echo '</div>';

echo form_close();
?>
    </div>
    <br />
</div>
