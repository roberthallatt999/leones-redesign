<div class="low-reorder-search-fields" data-params="<?=htmlspecialchars($params, ENT_QUOTES)?>">
    <div>
        <select name="lrsearch[fields][]">
            <option value="">--</option>
            <?php foreach ($choices as $key => $val) : ?>
                <?php if (is_array($val)) : ?>
                    <optgroup label="<?=htmlspecialchars($key)?>">
                        <?php foreach ($val as $k => $v) : ?>
                            <option value="<?=$k?>"><?=htmlspecialchars($v)?></option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php else : ?>
                    <option value="<?=$key?>"><?=htmlspecialchars($val)?> – {<?=$key?>}</option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select> = <input type="text" name="lrsearch[values][]">
        <button type="button" class="remove"><?=lang('remove')?></button>
    </div>
    <button type="button" class="add"><?=lang('add_search_filter')?></button>
</div>
