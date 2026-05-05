{% for row in <?= $field_name ?> %}
<?php foreach ($fields as $field) : ?>
    <?php if($field['is_tag_pair']): ?>

    {% for pair in row.<?= $field['field_name']; ?> %}
    <?php foreach ($field['fields'] as $pairName => $pair) : ?>

        {{ pair.<?= $pair['field_name']; ?> }}
    <?php endforeach; ?>

    {% endfor %}
    <?php else: ?>

    {{ row.<?= $field['field_name']; ?> }}
    <?php endif; ?>
<?php endforeach; ?>

{% endfor %}
