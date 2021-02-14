<?php
/**
 * @var string $name
 * @var array $options
 * */
?>
<select name="<?= $name ?>" class="">
    <?php foreach( $options as $option ) : ?>
        <option value="<?= $option['value'] ?>"><?= $option['value'] ?></option>
    <?php endforeach; ?>
</select>
