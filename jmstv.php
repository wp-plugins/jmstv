<?php
/*
Plugin Name: JMStV
Description: Zeitliche Steuerung der "Sendezeit" eines Blogs. Wahlweise pauschal für alle Blogseiten oder nur für ausgewählte Artikel.
Author: Sergej M&uuml;ller
Author URI: http://www.wpSEO.de
Version: 0.2
*/


if (!function_exists ('is_admin')) {
header('Status: 403 Forbidden');
header('HTTP/1.1 403 Forbidden');
exit();
}
class JMStV {
function JMStV() {
if ((defined('DOING_AJAX') && DOING_AJAX) || (defined('DOING_CRON') && DOING_CRON) || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)) {
return;
}
$this->base_name = plugin_basename(__FILE__);
if (is_admin()) {
add_action(
'admin_init',
array(
$this,
'add_sources'
)
);
add_action(
'admin_menu',
array(
$this,
'init_menu'
)
);
add_action(
'activate_' .$this->base_name,
array(
$this,
'init_options'
)
);
add_action(
'admin_notices',
array(
$this,
'show_notice'
)
);
add_filter(
'plugin_row_meta',
array(
$this,
'init_rows'
),
10,
2
);
} else {
add_action(
'template_redirect',
array(
$this,
'redirect_page'
)
);
}
}
function init_rows($links, $file) {
if ($this->base_name == $file) {
return array_merge(
$links,
array(
sprintf(
'<a href="options-general.php?page=%s">%s</a>',
$this->base_name,
__('Settings')
)
)
);
}
return $links;
}
function init_options() {
add_option(
'jmstv',
array(
'redirect_from' => 6,
'redirect_to'=> 20
),
'',
'no'
);
}
function get_option($field) {
if (!$options = wp_cache_get('jmstv')) {
$options = get_option('jmstv');
wp_cache_set(
'jmstv',
$options
);
}
return @$options[$field];
}
function update_option($field, $value) {
$this->update_options(
array(
$field => $value
)
);
}
function update_options($data) {
$options = array_merge(
(array)get_option('jmstv'),
$data
);
update_option(
'jmstv',
$options
);
wp_cache_set(
'jmstv',
$options
);
}
function init_menu() {
$page = add_options_page(
'JMStV',
'JMStV',
'manage_options',
__FILE__,
array(
$this,
'show_menu'
)
);
add_action(
'admin_print_scripts-' . $page,
array(
$this,
'add_script'
)
);
add_action(
'admin_print_styles-' . $page,
array(
$this,
'add_style'
)
);
}
function add_sources() {
$data = get_plugin_data(__FILE__);
wp_register_script(
'jmstv_script',
plugins_url('jmstv/js/script.js'),
array('jquery'),
$data['Version']
);
wp_register_style(
'jmstv_style',
plugins_url('jmstv/css/style.css'),
array(),
$data['Version']
);
}
function add_script() {
wp_enqueue_script('jmstv_script');
}
function add_style() {
wp_enqueue_style('jmstv_style');
}
function is_min_wp($version) {
return version_compare(
$GLOBALS['wp_version'],
$version. 'alpha',
'>='
);
}
function show_notice() {
if ($this->is_min_wp('2.8')) {
return;
}
echo '<div class="error"><p><strong>JMStV</strong> benötigt WordPress 2.8 oder neuer.</p></div>';
}
function redirect_page() {
if (!$this->get_option('redirect_enable')) {
return;
}
if ($this->get_option('redirect_admin') && current_user_can('administrator')) {
return;
}
if (is_feed() || is_trackback()) {
return;
}
$current = $GLOBALS['wp_query']->get_queried_object_id();
$redirect = $this->get_option('redirect_id');
if (!empty($current) && $current == $redirect) {
return;
}
if (!($page = get_permalink($redirect))) {
return;
}
if ($this->get_option('redirect_custom')) {
if (empty($current)) {
return;
}
$values = get_post_custom_values('jmstv', $current);
$value = @$values[0];
if (empty($value)) {
return;
}
}
@date_default_timezone_set('Europe/Berlin');
if (date('H') >= $this->get_option('redirect_from') && date('H') < $this->get_option('redirect_to')) {
wp_redirect($page, 307);
exit;
}
}
function show_menu() {
if (!empty($_POST)) {
check_admin_referer('jmstv');
$options = array(
'redirect_enable'=> (int)@$_POST['jmstv_redirect_enable'],
'redirect_from'=> (int)@$_POST['jmstv_redirect_from'],
'redirect_to'=> (int)@$_POST['jmstv_redirect_to'],
'redirect_id'=> (int)@$_POST['jmstv_redirect_id'],
'redirect_admin'=> (int)@$_POST['jmstv_redirect_admin'],
'redirect_custom' => (int)@$_POST['jmstv_redirect_custom']
);
if (empty($options['redirect_id']) || !get_permalink($options['redirect_id'])) {
$options['redirect_enable'] = 0;
}
$this->update_options($options); ?>
<div id="message" class="updated fade">
<p>
<strong>
<?php _e('Settings saved.') ?>
</strong>
</p>
</div>
<?php } ?>
<div class="wrap">
<div id="icon-options-general" class="icon32"></div>
<h2>
JMStV
</h2>
<form method="post" action="">
<?php wp_nonce_field('jmstv') ?>
<div id="poststuff">
<div class="postbox">
<h3>
<?php _e('Settings') ?>
</h3>
<div class="inside">
<ul>
<li>
<div>
<input type="checkbox" name="jmstv_redirect_enable" id="jmstv_redirect_enable" value="1" <?php checked($this->get_option('redirect_enable'), 1) ?> />
<label for="jmstv_redirect_enable">
Stilllegung der "Sendezeit"
</label>
</div>
<ul <?php echo ($this->get_option('redirect_enable') ? '' : 'class="inact"') ?>>
<li>
Uhrzeit (von / bis)
<br />
<select name="jmstv_redirect_from">
<?php for ($a = 1; $a <= 24; $a ++) { ?>
<option <?php selected($this->get_option('redirect_from'), $a) ?>><?php echo $a ?></option>
<?php } ?>
</select> <select name="jmstv_redirect_to">
<?php for ($a = 1; $a <= 24; $a ++) { ?>
<option <?php selected($this->get_option('redirect_to'), $a) ?>><?php echo $a ?></option>
<?php } ?>
</select>
</li>
<li>
ID der Umleitungsseite (<a href="<?php echo admin_url('edit.php?post_type=page') ?>">Finden</a>)
<br />
<input type="text" name="jmstv_redirect_id" id="jmstv_redirect_id" value="<?php echo $this->get_option('redirect_id') ?>" class="regular-text" />
</li>
<li>
<input type="checkbox" name="jmstv_redirect_admin" id="jmstv_redirect_admin" value="1" <?php checked($this->get_option('redirect_admin'), 1) ?> />
<label for="jmstv_redirect_admin">
Betrifft nicht die Blog-Administratoren
</label>
</li>
<li>
<input type="checkbox" name="jmstv_redirect_custom" id="jmstv_redirect_custom" value="1" <?php checked($this->get_option('redirect_custom'), 1) ?> />
<label for="jmstv_redirect_custom">
Nur auf markierte Seiten anwenden
<br />
<em>Benutzerdefiniertes Feld <strong>jmstv</strong> mit dem Wert <strong>1</strong></em>
</label>
</li>
</ul>
</li>
</ul>
<p>
<input type="submit" name="jmstv_submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
</p>
</div>
</div>
</div>
</form>
</div>
<?php }
}
new JMStV();