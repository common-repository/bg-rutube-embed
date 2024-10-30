<?php
/**
 * Страница настроек плагина. Версия 1.6.2
 */
add_action('admin_menu', 'add_bg_rutube_options_page');
function add_bg_rutube_options_page(){
	add_options_page( __('Bg RuTube Embed Settings', 'bg-rutube-embed'), 'Bg RuTube Embed', 'manage_options', 'bg_rutube_slug', 'bg_rutube_options_page_output' );
}

function bg_rutube_options_page_output(){
	?>
	<div class="wrap">
		<h2><?php echo get_admin_page_title() ?></h2>

		<form action="options.php" method="POST">
			<?php
				settings_fields( 'bg_rutube_option_group' );     // скрытые защитные поля
				do_settings_sections( 'bg_rutube_page' ); // секции с настройками (опциями). У нас она всего одна 'section_id'
				submit_button();
			?>
		</form>
	</div>
	<?php
}

/**
 * Регистрируем настройки.
 * Настройки будут храниться в массиве, а не одна настройка = одна опция.
 */
add_action('admin_init', 'bg_rutube_plugin_settings');
function bg_rutube_plugin_settings(){
	// параметры: $option_group, $bg_rutube_options, $bg_rutube_sanitize_callback
	register_setting( 'bg_rutube_option_group', 'bg_rutube_options', 'bg_rutube_sanitize_callback' );

	// параметры: $id, $title, $callback, $page
	add_settings_section( 'bg_rutube_section_id', '', '', 'bg_rutube_page' );

	// параметры: $id, $title, $callback, $page, $section, $args
	add_settings_field('bg_rutube_field1', __('Videos on post pages only', 'bg-rutube-embed'), 'fill_bg_rutube_field1', 'bg_rutube_page', 'bg_rutube_section_id' );
	add_settings_field('bg_rutube_field2', __('Mode of start  video on page load', 'bg-rutube-embed'), 'fill_bg_rutube_field2', 'bg_rutube_page', 'bg_rutube_section_id' );
	add_settings_field('bg_rutube_field3', __('Caching response of the RuTube API', 'bg-rutube-embed'), 'fill_bg_rutube_field3', 'bg_rutube_page', 'bg_rutube_section_id' );
	add_settings_field('bg_rutube_field5', __('Paginate playlist', 'bg-rutube-embed'), 'fill_bg_rutube_field5', 'bg_rutube_page', 'bg_rutube_section_id' );
	add_settings_field('bg_rutube_field7', __('Max playlist length', 'bg-rutube-embed'), 'fill_bg_rutube_field7', 'bg_rutube_page', 'bg_rutube_section_id' );
}

## Заполняем опцию 1
function fill_bg_rutube_field1(){
	$val = get_option('bg_rutube_options');
	$singular = $val ? $val['singular'] : null;
	?>
	<label><input type="checkbox" id="singular" name="bg_rutube_options[singular]" value="1" <?php checked( 1, $singular ); ?> /> <?php _e( '(post, page, custom post type, attachment)', 'bg-rutube-embed'); ?></label>
	<?php
}

## Заполняем опцию 2
function fill_bg_rutube_field2(){
	$val = get_option('bg_rutube_options');
	$startmode = $val ? $val['startmode'] : 'preview';
	?>
	<label>
    <select id="startmode" name="bg_rutube_options[startmode]">
        <option value='preview' <?php selected( $startmode, 'preview' ); ?>><?php _e( 'Preview image', 'bg-rutube-embed'); ?></option>
        <option value='load' <?php selected( $startmode, 'load' ); ?>><?php _e( 'Load movie', 'bg-rutube-embed'); ?></option>
        <option value='play' <?php selected( $startmode, 'play' ); ?>><?php _e( 'Play movie', 'bg-rutube-embed'); ?></option>
    </select> <?php _e( '"Preview image" - preview image, "Load movie" - load video into the frame, "Play movie" - load video into the frame and play.', 'bg-rutube-embed'); ?></label>


	<?php
}

## Заполняем опцию 3
function fill_bg_rutube_field3(){
	$val = get_option('bg_rutube_options');
	$transient = $val ? $val['transient'] : null;
	?>
	<label><input type="checkbox" id="transient" name="bg_rutube_options[transient]" value="1" <?php checked( 1, $transient ); ?> /> <?php _e( '(speeds up page opening, cache lifetime is 1 hour)', 'bg-rutube-embed'); ?></label>
	<?php
}

## Заполняем опцию 5
function fill_bg_rutube_field5(){
	$val = get_option('bg_rutube_options');
	$perpage = ($val && !empty($val['perpage'])) ? $val['perpage'] : 0;
	?>
	<label><input type="number" id="perpage" name="bg_rutube_options[perpage]" value="<?php echo $perpage; ?>" min="0" style="width:5em;" /> <?php _e( 'per page<br>(0 - don\'t paginate)', 'bg-rutube-embed'); ?></label>
	<?php
}

## Заполняем опцию 7
function fill_bg_rutube_field7(){
	$val = get_option('bg_rutube_options');
	$limit = ($val && !empty($val['limit'])) ? $val['limit'] : 0;
	?>
	<label><input type="number" id="limit" name="bg_rutube_options[limit]" value="<?php echo $limit; ?>" min="0" style="width:5em;" /> <?php _e( '(0 - not limited)', 'bg-rutube-embed'); ?></label>
	<?php
}

## Очистка данных
function bg_rutube_sanitize_callback( $options ){
	// очищаем
	foreach( $options as $name => & $val ){
		if ($name == 'startmode') {
			if (!in_array ($mode, ['preview', 'load', 'play'])) $mode = 'preview';
		} else $val = intval( $val );
	}
	return $options;
}