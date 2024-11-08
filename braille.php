<?php
$use_remote = get_option("braille_use_remote");
if($use_remote) {
  require_once 'lib/LibLouis-Remoter.php';
}
else {
  require_once 'lib/php-liblouis.php';
}

/*
Plugin Name: Braille
Plugin URI: http://umd-mith.github.com/braille/
Description: This plugin allows you to incorporate SimBraille or BRL formatted text from English text.
Version: 0.0.6
Author: University of Maryland, Maryland Institute for Technology in the Humanities
Author URI: http://mith.umd.edu/
License: GNU
*/

/*
Copyright 2012  University of Maryland

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License, version 2, as published by the Free Software Foundation. This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details. You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Admin stuff starts here (set up plugin, add menu item, empty config page)

function braille_css() {
  #echo "<link type='text/css' rel='stylesheet' href='"
  #   . plugins_url( 'braille/css/braille.css' )
  #   . "' />\n"
  #   ;
  ?><style>
  .braille {
  font-family: Futura;
  background: #eeeeee;
  font-weight: bold;
  /* white-space: pre-line; */
  }
  div.braille {
    white-space: pre-line;
    width: 90%;
  }
  </style><?php
}

// Add a menu item for the plugin and test for authority
add_action( 'admin_menu', 'braille_admin_menu' );
add_action( 'admin_init', 'braille_save_settings' );
add_filter( 'the_content', 'braille_content' );
add_filter( 'the_title', 'braille_title' );
add_filter( 'the_excerpt', 'braille_content' );
add_filter( 'wp_title', 'braille_wp_title' );
add_filter( 'comment_text', 'braille_comment_text' );
add_filter( 'comment_excerpt', 'braille_comment_text' );
add_filter( 'the_author', 'braille_author' );
add_filter( 'widget_title', 'braille_widget_title' );

add_action( 'plugins_loaded', 'braille_plugins_loaded');

function braille_plugins_loaded() {
  if (function_exists( 'anthologize_register_format_option' ) && function_exists( 'anthologize_register_format')) {
    anthologize_register_format("BRL", __( "Braille", "braille" ), WP_PLUGIN_DIR . '/braille/templates/brl/output.php' );
    $htmlFontSizes = array('48pt'=>'48 pt', '36pt'=>'36 pt', '18pt'=>'18 pt', '14'=>'14 pt', '12'=>'12 pt');
    anthologize_register_format_option("BRL", 'font-size', __( 'Font Size', 'anthologize' ), 'dropdown', $htmlFontSizes, '14pt');
    anthologize_register_format_option( 'BRL', 'download', __('Download Braille?', 'braille'), 'checkbox', array('Download'=>'download'), 'download');
    anthologize_register_format_option( 'BRL', 'utf8', __('Convert BRL to SimBraille?', 'braille'), 'checkbox', array('UTF8' => 'utf8'), 'utf8');
  }
}

function braille_admin_menu() {
  add_options_page(
    'Braille Plugin Options',
    'Braille Plugin',
    'manage_options',
    'edu-umd-mith-braille',
    'braille_settings_page'
  );
}

/**
 * Processes saved settings.
 *
 * @since 0.0.6
 */
function braille_save_settings() {
  if ( ! current_user_can( 'manage_options' ) ) {
    return;
  }

  if ( empty( $_POST['update_braille_settings'] ) || 'Y' !== $_POST['update_braille_settings'] ) {
    return;
  }

  check_admin_referer( 'braille_save_settings' );

  update_option( 'braille_use_remote', ! empty( $_POST['braille_use_remote'] ) );
  update_option( 'braille_filter_content', ! empty( $_POST['braille_filter_content'] ) );
  update_option( 'braille_filter_title', ! empty( $_POST['braille_filter_title'] ) );
  update_option( 'braille_filter_wp_title', ! empty( $_POST['braille_filter_wp_title'] ) );
  update_option( 'braille_filter_comment', ! empty( $_POST['braille_filter_comment'] ) );
  update_option( 'braille_filter_widget_title', ! empty( $_POST['braille_filter_widget_title'] ) );
  update_option( 'braille_display_utf8', ! empty( $_POST['braille_display_utf8'] ) );

  $new_local_path = isset( $_POST['braille_local_path'] ) ? sanitize_file_name( wp_unslash( $_POST['braille_local_path'] ) ) : '';
  update_option( 'braille_local_path', $new_local_path );

  $new_remote_url = isset( $_POST['braille_remote_url'] ) ? sanitize_text_field( wp_unslash( $_POST['braille_remote_url'] ) ) : '';
  update_option( 'braille_remote_url', $new_remote_url );

  $redirect_to = add_query_arg(
    array(
      'page'    => 'edu-umd-mith-braille',
      'updated' => 1,
    ),
    admin_url( 'options-general.php' )
  );
  wp_safe_redirect( $redirect_to );
}

function braille_settings_page() {
  if ( !current_user_can( 'manage_options' ) )  {
    wp_die(
      __( 'You do not have sufficient permissions to access this page.' )
    );
  }

  // Read in current option settings

  $use_remote = get_option("braille_use_remote");
  $local_path = get_option("braille_local_path");
  $remote_url = get_option("braille_remote_url");
  $filter_content = get_option("braille_filter_content");
  $filter_title = get_option("braille_filter_title");
  $filter_wp_title = get_option("braille_filter_wp_title");
  $filter_comment = get_option("braille_filter_comment");
  $filter_widget_title = get_option("braille_filter_widget_title");

  $display_utf8 = get_option("braille_display_utf8");

  // Appearance of settings page
?>
  <div class="wrap">
    <h2><?php echo __( "Braille Plugin Options", "braille" ); ?></h2>
    <form method="POST" action="">
    <input type="hidden" name="update_braille_settings" value="Y">
      <p>
        <input type="checkbox" name="braille_use_remote" id="braille-use-remote" <?php checked( $use_remote ); ?> />
        <label for="braille-use-remote"><?php esc_html_e( 'Use remote LibLouis service', 'braille' ); ?></label>
      </p>
      <p>
        <input type="checkbox" name="braille_filter_content" id="braille-filter-content" <?php checked( $filter_content ); ?> />
        <label for="braille-filter-content"><?php esc_html_e( 'Translate page and post content', 'braille' ); ?></label>
      </p>
      <p>
        <input type="checkbox" name="braille_filter_comment" id="braille-filter-comment" <?php checked( $filter_comment ); ?> />
        <label for="braille-filter-comment"><?php esc_html_e( 'Translate comment content', 'braille' ); ?></label>
      </p>
      <p>
        <input type="checkbox" name="braille_filter_title" id="braille-filter-title" <?php checked( $filter_title ); ?> />
        <label for="braille-filter-title"><?php esc_html_e( 'Translate page and post titles', 'braille' ); ?></label>
      </p>
      <p>
        <input type="checkbox" name="braille_filter_wp_title" id="braille-filter-wp-title" <?php checked( $filter_wp_title ); ?> />
        <label for="braille-filter-wp-title"><?php esc_html_e( 'Translate the browser tab title (HTML title element)', 'braille' ); ?></label>
      </p>
      <p>
        <input type="checkbox" name="braille_filter_widget_title" id="braille-filter-widget-title" <?php checked( $filter_widget_title ); ?> />
        <label for="braille-filter-widget-title"><?php esc_html_e( 'Translate widget titles', 'braille' ); ?></label>
      </p>
      <p>
        <input type="checkbox" name="braille_display_utf8" id="braille-display-utf8" <?php checked( $display_utf8 ); ?> />
        <label for="braille-display-utf8"><?php esc_html_e( 'Translate BRL to SimBraille for display', 'braille' ); ?></label>
      </p>
      <p>
        <label for="braille-local-path"><?php esc_html_e( 'Path to local file2brl program', 'braille' ); ?></label>
        <input type="text" name="braille_local_path" id="braille-local-path" value="<?php echo esc_attr( $local_path ); ?>" />
      </p>
      <p>
        <label for="braille-remote-url"><?php esc_html_e( 'URL for remote LibLouis service', 'braille' ); ?></label>
        <input type="text" name="braille_remote_url" id="braille-remote-url" value="<?php echo esc_attr( $remote_url ); ?>" />
      </p>
      <hr />
      <p class="submit">
        <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'braille' ); ?>" />
      </p>

      <?php wp_nonce_field( 'braille_save_settings' ); ?>
    <form>
  </div>
<?php
}

// End admin code, begin main plugin function code
// Take text wrapped in [braille] shortcode and reprint it somewhere on screen


function ascii_to_unicode($text) {
  $base = 10240;
  $ascii2unicode = array(
    ' ' => 0,
    'a' => 1, 'b' => 3, 'c' => 9, 'd' => 25, 'e' => 17, 'f' => 11,
    'A' => 1, 'B' => 3, 'C' => 9, 'D' => 25, 'E' => 17, 'F' => 11,
    'g' => 27, 'h' => 19, 'i' => 10, 'j' => 26,
    'G' => 27, 'H' => 19, 'I' => 10, 'J' => 26,

    'k' => 5, 'l' => 7, 'm' => 13, 'n' => 29, 'o' => 21, 'p' => 15,
    'K' => 5, 'L' => 7, 'M' => 13, 'N' => 29, 'O' => 21, 'P' => 15,
    'q' => 31, 'r' => 23, 's' => 14, 't' => 30,
    'Q' => 31, 'R' => 23, 'S' => 14, 'T' => 30,

    'u' => 37, 'v' => 39, 'x' => 45, 'y' => 61, 'z' => 53,
    'U' => 37, 'V' => 39, 'X' => 45, 'Y' => 61, 'Z' => 53,
    '&' => 47, '=' => 63, '(' => 55, '!' => 46, ')' => 62,

    '*' => 33, '<' => 35, '%' => 41, '?' => 57, ':' => 49,
    '$' => 43, ']' => 59, '}' => 59, '\\' => 51, '{' => 42,
    'W' => 58, 'w' => 58,

    '1' => 2, '2' => 6, '3' => 18, '4' => 50, '5' => 34, '6' => 22,
    '7' => 54, '8' => 38, '9' => 20, '0' => 52,

    '/' => 12, '+' => 44, '#' => 60, '>' => 28, "'" => 4, '-' => 36,

    '@' => 8, '^' => 24,  '_' => 56, '"' => 16, '.' => 40, ';' => 48,
    ',' => 32,
    '|' => 51,
    '~' => 24,
  );
  $out = "";
  $n = strlen($text);
  for($i = 0; $i < $n; $i++) {
    $ch = $text[$i];
    if(array_key_exists($ch, $ascii2unicode)) {
      $out .= sprintf("&#%d;", $base + $ascii2unicode[$ch]);
    }
    else {
      $out .= $ch;
    }
  }
  return $out;
}

function convert_braille( $atts, $content, $display_utf8 ) {
  // Wrap in CSS class to style
  if($content == "") { return $content; }
  $use_remote = get_option("braille_use_remote");
  $local_path = get_option("braille_local_path");
  $remote_url = get_option("braille_remote_url");

  if($use_remote) {
    $acontent = returnBrailleForString($content, $remote_url);
  }
  else {
    # TODO: add call to local library
    return "Local translation is not supported at the moment.";
  }
  if($display_utf8) {
    $bcontent = ascii_to_unicode($acontent);
  }
  else {
    $bcontent = htmlspecialchars($acontent);
  }
  return $bcontent;
}

function get_braille( $atts, $content = null ) {
  if($content == "") { return $content; }
  return convert_braille( $atts, $content, get_option("braille_display_utf8") );
}

function braille_embedded( $atts, $content = null ) {
  $content = do_shortcode(wpautop( trim( $content ) ) );
  return get_braille( $atts, $content );
}

function braille_content($content) {
  if(!get_option("braille_filter_content")) {
    return $content;
  }
  return "<div class='braille'>" . get_braille(0, $content) . "</div>";
}

function braille_author($content) {
  if(!get_option("braille_filter_content")) {
    return $content;
  }
  return "<span class='braille'>" . get_braille(0, $content) . "</span>";
}

function braille_title($content) {
  if(!get_option("braille_filter_title")) {
    return $content;
  }
  return "<span class='braille'>" . get_braille(0, $content) . "</span>";
}

function braille_widget_title($content) {
  if(!get_option("braille_filter_widget_title")) {
    return $content;
  }
  return "<span class='braille'>" . get_braille(0, $content) . "</span>";
}

function braille_comment_text($content) {
  if(!get_option("braille_filter_comment")) {
    return $content;
  }
  return "<div class='braille'>" . get_braille(0, $content) . "</div>";
}

function braille_wp_title($content) {
  if(!get_option("braille_filter_wp_title")) {
    return $content;
  }
  return get_braille(0, $content);
}

// Allow [braille][/braille] shortcode
add_shortcode( 'braille', 'braille_embedded' );

// Add the CSS file to the header when the page loads
add_action( 'wp_head', 'braille_css' );

// TinyMCE button, or only as extra? (see PullQuote plugin as example)
// Allow uninstall

?>
