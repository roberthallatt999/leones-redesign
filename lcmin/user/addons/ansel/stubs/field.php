{<?=$field_name?>}
<?php foreach ($fields as $field) : ?>
    <?php if($field['is_tag_pair']): ?>

    {<?=$field['field_name']; ?>}
    <?php foreach ($field['fields'] as $pair) : ?>

        {<?=$pair['field_name']; ?>}
    <?php endforeach; ?>

    {/<?=$field['field_name']; ?>}
    <?php else: ?>

    {<?=$field['field_name']; ?>}
    <?php endif; ?>
<?php endforeach; ?>

{/<?=$field_name?>}
