<?php
/**
 * @var $option_group
 * */
?>

<div id="pcm-settings" class="pcm-settings">
    <form action='options.php' method='post'>

        <h1>Posts Columns Manager Settings</h1>

        <p style="font-size: 16px;">What columns do you want to add?</p>

        <?php
        settings_fields( $option_group );
        do_settings_sections( $option_group );
        submit_button();
        ?>

    </form>
</div>
