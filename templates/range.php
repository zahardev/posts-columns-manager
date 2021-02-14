<?php
/**
 * @var string $label
 * @var string $param_from
 * @var int|false $val_from
 * @var string $param_to
 * @var int|false $val_to
 * */
?>

<div class="pcm-range pcm-filter">
    <label for="<?= $param_from ?>"><strong><?= $label ?></strong> from</label>
    <input name="<?= $param_from ?>" id="<?= $param_from ?>" type="number" class="" value="<?= $val_from ?>">
    <label for="<?= $param_to ?>">to</label>
    <input name="<?= $param_to ?>" id="<?= $param_to ?>" type="number" class="" value="<?= $val_to ?>">
    <span style="margin-bottom: 50px; float: left;"></span>
</div>

