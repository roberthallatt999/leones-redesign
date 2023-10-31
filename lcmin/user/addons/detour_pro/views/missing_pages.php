<div class="panel box ee<?=$ee_ver?>">
    <div class="tbl-ctrls">
        <div class="panel-heading">
        <?php echo form_open($search_url); ?>
            <fieldset class="tbl-search right">
                <input placeholder="type phrase..." type="text" name="search" value="<?= $search_query ?>" style="float: left; margin-right: 5px;">
                <button type="submit" class="btn action submit">search</button>
            </fieldset>
        </form>
        <h1><?=lang('nav_missing_page_tracker')?></h1>
    </div>


<?php
echo '<div class="settings panel-body">';
echo '<div class="tbl-wrap">', "\n";



$searchParam = '';
$searchValue = ee()->input->get('search');

if (!empty($searchValue)) {
    $searchParam = '&search=' . $searchValue;
}

$headingItems[] = array(
    'data'  => '<a href="' . $base_url . '&sort=original_url&sort_dir=' . $sort_dir['original_url'] . $searchParam . '">' . ee()->lang->line('title_url') . '</a>',
    'style' => 'width:70%;',
    'class' => ($sort == 'original_url' ? 'sorting_' . $sort_dir['current'] : ''),
);
$headingItems[] = array(
    'data'  => '<a href="' . $base_url . '&sort=hits&sort_dir=' . $sort_dir['hits'] . $searchParam . '">' . ee()->lang->line('title_hits') . '</a>',
    'style' => 'width:20%;',
    'class' => ($sort == 'hits' ? 'sorting_' . $sort_dir['current'] : ''),
);
$headingItems[] = array(
    'data'  => '<a href="' . $base_url . '&sort=hit_date&sort_dir=' . $sort_dir['hit_date'] . $searchParam . '">' . ee()->lang->line('title_hit_date') . '</a>',
    'style' => 'width:20%;',
    'class' => ($sort == 'hit_date' ? 'sorting_' . $sort_dir['current'] : ''),
);
$headingItems[] = array(
    'data'  => '<a href="' . $base_url . '&sort=detour_id&sort_dir=' . $sort_dir['detour_id'] . $searchParam . '">' . ee()->lang->line('title_detour') . '</a>',
    'style' => 'width:10%;',
    'class' => ($sort == 'title_detour' ? 'sorting_' . $sort_dir['current'] : ''),
);


ee()->table->set_template('cp_pad_table_template');
ee()->table->set_heading($headingItems);

$hasRows = false;
if (is_array($current_rows) && count($current_rows) == 0) {
    ee()->table->add_row(
        array('data' => ee()->lang->line('dir_no_404s'), 'colspan' => 4)
    );
} else {
    $hasRows = true;
    foreach ($current_rows as $row) {
		$rowItems   = array();
        $rowItems[] = $row['original_url'];
        $rowItems[] = $row['hits'];
		$rowItems[] = $row['hit_date'];
        $rowItems[] = !empty($row['detour_id']) ? '<a href="' . $row['edit_detour_link'] . '">' . lang('label_edit_detour') . '</a>' : '<a href="' . $row['add_detour_link'] . '">' . lang('label_add_detour') . '</a>';

        ee()->table->add_row($rowItems);
    }
}

if (isset($pagination) && !empty($pagination)) {
    ee()->table->add_row(
        array('data' => $pagination, 'colspan' => 4)
    );
}

echo ee()->table->generate();
ee()->table->clear();

echo '</div>', "\n";
echo '</div>';
?>
    </div>
    <br />
</div>
