<?php
/**
 * @var string $name
 * @var array $options
 * */
?>
<select name="<?php echo $name ?>" class="">
    <?php foreach( $options as $option ) : ?>
        <option value="<?php echo $option['value'] ?>"><?php echo $option['value'] ?></option>
    <?php endforeach; ?>
</select>
