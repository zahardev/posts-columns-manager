<?php
/**
 * @var Settings_Tab[] $tabs
 * @var Settings_Tab $current_tab
 * @var string $page_slug
 * */

use PCM\Entities\Settings_Tab;
?>

<div id="pcm-settings" class="pcm-settings">
    <form action='options.php' method='post'>

        <h1>Posts Columns Manager Settings</h1>

        <ul class="pcm-tabs">
	        <?php foreach ( $tabs as $tab ) : ?>
                <li class="pcm-tab <?php echo ( $tab->id === $current_tab->id ) ? 'active' : '' ?>">
                    <a href="<?php echo esc_attr( $tab->get_url() ) ?>"><?php echo $tab->title ?></a>
                </li>
	        <?php endforeach; ?>
        </ul>

        <h3><?php echo $current_tab->title; ?></h3>

        <p style="font-size: 16px;"><?php echo $current_tab->description; ?></p>

        <?php
        settings_fields( $page_slug );
        do_settings_sections( $page_slug );
        do_action( 'pcm_tab_settings', $current_tab );
        submit_button();
        ?>

    </form>
</div>
