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
    <label for="<?php echo $param_from ?>"><strong><?php echo $label ?></strong> from</label>
    <input name="<?php echo $param_from ?>" id="<?php echo $param_from ?>" type="number" class="" value="<?php echo $val_from ?>">
    <label for="<?php echo $param_to ?>">to</label>
    <input name="<?php echo $param_to ?>" id="<?php echo $param_to ?>" type="number" class="" value="<?php echo $val_to ?>">
    <span style="margin-bottom: 50px; float: left;"></span>
</div>

