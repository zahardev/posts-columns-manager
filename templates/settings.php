<?php
/**
 * @var Settings_Tab[] $tabs
 * @var string $current_tab
 * @var string $page_slug
 * */

use PCM\Entities\Settings_Tab;
?>

<div id="pcm-settings" class="pcm-settings">
    <form action='options.php' method='post'>

        <h1>Posts Columns Manager Settings</h1>

        <ul class="pcm-tabs">
	        <?php foreach ( $tabs as $tab ) : ?>
                <li class="pcm-tab <?php echo ( $tab->id === $current_tab ) ? 'active' : '' ?>">
                    <a href="<?php echo esc_attr( $tab->get_url() ) ?>"><?php echo $tab->title ?></a>
                </li>
	        <?php endforeach; ?>
        </ul>

        <p style="font-size: 16px;">Please choose which columns you want to add. </p>

        <?php
        settings_fields( $page_slug );
        do_settings_sections( $page_slug );
        submit_button();
        ?>

    </form>
</div>
