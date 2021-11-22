<?php
/**
 * @var string $setting_name
 * @var bool $is_checked
 * @var bool $is_supported
 * */
?>
<input type="checkbox" name="<?php echo $setting_name ?>" value="1" <?php checked( $is_checked, 1 ); ?> <?php if ( ! $is_supported ): ?>disabled<?php endif; ?> />
