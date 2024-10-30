<?php
/*
Plugin Name: Contact Form 7 Tag-it field
Plugin URI: http://www.apprique.com/wordpress-plugins
Description: Add tag fields to the popular Contact Form 7 plugin.
Author: Apprique Ltd. 
Author URI: http://www.apprique.com
Version: 1.2
*/

/*  Below code is partially based on the work by Katz Web Services, Inc. 
    for their "Contact Form 7 Modules" plugin.
	
	Copyright 2013 Apprique Ltd. (email: wordpressplugins at apprique.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// this plugin needs to be initialized AFTER the Contact Form 7 plugin.
add_action('plugins_loaded', 'contact_form_7_tagit_fields', 10); 
function contact_form_7_tagit_fields() {
	global $pagenow;
	if(!function_exists('wpcf7_add_shortcode')) {
		if($pagenow != 'plugins.php') { return; }
		add_action('admin_notices', 'cftagitfieldserror');
		add_action('admin_enqueue_scripts', 'contact_form_7_tagit_fields_scripts');

		function cftagitfieldserror() {
			$out = '<div class="error" id="messages"><p>';
			if(file_exists(WP_PLUGIN_DIR.'/contact-form-7/wp-contact-form-7.php')) {
				$out .= 'The Contact Form 7 plugin is installed, but <strong>you must activate Contact Form 7</strong> below for the Tag-it Field plugin to work.';
			} else {
				$out .= 'The Contact Form 7 plugin must be installed for the Tag-it Field plugin to work. <a href="'.admin_url('plugin-install.php?tab=plugin-information&plugin=contact-form-7&from=plugins&TB_iframe=true&width=600&height=550').'" class="thickbox" title="Contact Form 7">Install Now.</a>';
			}
			$out .= '</p></div>';
			echo $out;
		}
	}
}

add_action( 'wpcf7_init', 'wpcf7_add_shortcode_tagit' );
function wpcf7_add_shortcode_tagit() {
	wpcf7_add_shortcode( array( 'tagit', 'tagit*' ),
		'wpcf7_tagit_shortcode_handler', true );
}



/**
 * Register with hook 'wp_enqueue_scripts', which can be used for front end CSS and JavaScript
 */
add_action( 'wp_enqueue_scripts', 'tagit_add_stylesheets' );

/**
 * Enqueue plugin style-file
 */
function tagit_add_stylesheets() {
	wp_register_style( 'jquery-ui', plugins_url( 'jquery-ui.css' , __FILE__ ) );
	wp_enqueue_style( 'jquery-ui' );
	wp_register_style( 'jquery-tag-it', plugins_url( 'jquery.tagit.css' , __FILE__ ) );
	wp_enqueue_style( 'jquery-tag-it' );
	wp_register_style( 'tag-it-ui', plugins_url( 'tagit.ui.css' , __FILE__ ) );
	wp_enqueue_style( 'tag-it-ui' );

}

// will only be loaded when Contact Form 7 plugin is not installed and only in Admin
function contact_form_7_tagit_fields_scripts() {
	wp_enqueue_script('thickbox');
}


/* Shortcode handler */
// MS: not sure what this does yet 
add_filter('wpcf7_form_elements', 'wpcf7_form_elements_return_false');
function wpcf7_form_elements_return_false($form) {
	$brform = preg_replace('/<p>(<input\stype="hidden"(?:.*?))<\/p>/isme', "'<div style=\'display:none;\'>'.\"\n\".str_replace('<br>', '', str_replace('<br />', '', stripslashes_deep('\\1'))).\"\n\".'</div>'", $form);
	return $brform;
}

/**
** A base module for [tagit], [tagit*]
**/

/* Shortcode handler */

function wpcf7_tagit_shortcode_handler( $tag ) {

	if ( ! is_array( $tag ) )
		return '';

	$type = $tag['type'];
	$name = $raw_name = $tag['name'];
	$options = (array) $tag['options'];
	$values = (array) $tag['values'];

	if ( empty( $name ) ) {
		return '';
	}

	// so we have a tagit element: load js later
	// we need jQuery UI
	wp_enqueue_script('jquery-ui-core');
	wp_enqueue_script('jquery-ui-widget');
	wp_enqueue_script('jquery-ui-autocomplete'); 
	// and of course the tag-it script
	wp_enqueue_script('tag-it',plugins_url( 'tag-it.js' , __FILE__ ),array('jquery', 'jquery-ui-core'),'1.0',true);

	
	$atts = '';
	$id_att = '';
	$class_att = '';
	$size_att = '';
	$maxlength_att = '';
	$tabindex_att = '';
	$title_att = '';

	$class_att .= ' wpcf7-tagit';

	if ( 'tagit*' == $type )
		$class_att .= ' wpcf7-validates-as-required';

	foreach ( $options as $option ) {
		if ( preg_match( '%^id:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$id_att = $matches[1];

		} elseif ( preg_match( '%^class:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$class_att .= ' ' . $matches[1];
		}
	}

	$value = (string) reset( $values );

	if ( $class_att )
		$atts .= ' class="' . trim( $class_att ) . '"';
	if ( $id_att ) {
		$id_att = trim( $id_att );
		$atts .= ' id="' . trim( $id_att ) . '"';
	}
	else 
	{ 
		// we need an ID for the script to work!
		$id_att = "tagit".rand(1000,9999);
		$atts .= ' id="' . $id_att . '"'; 
	}
	
	
	$html  = "
	<input type=\"hidden\" name=\"".$name."\" id=\"".trim( $id_att )."\" value=\"\"><ul id=\"".trim( $id_att )."field\"></ul>
	<script>
		jQuery(document).ready(function($) {
			$('#".trim( $id_att )."field').tagit({
			allowSpaces: true,
			removeConfirmation: true,
			singleField: true,
			singleFieldNode: $('#".trim( $id_att )."')
			});
		});
	</script>
	";
	
	/* old:
	$html  = "
	<input name=\"".$name."\" id=\"".trim( $id_att )."\" value=\"\">
	<script>
		jQuery(document).ready(function($) {
			$('#".trim( $id_att )."field').tagit({
			allowSpaces: true,
			removeConfirmation: true,
			singleField: true,
			singleFieldNode: $('#".trim( $id_att )."')
			});
		});
	</script>
	";
	*/
	

	return $html;
}

add_filter('wpcf7_hidden_field_value_example', 'wpcf7_tagit_field_add_query_arg');
function wpcf7_tagit_field_add_query_arg($value = '') {
	if(isset($_GET['category'])) {
		return $_GET['category'];
	}
	return $value;
}


/* Tag generator */

add_action( 'admin_init', 'wpcf7_add_tag_generator_tagit', 30 );

function wpcf7_add_tag_generator_tagit() {
	if(function_exists('wpcf7_add_tag_generator')) {
		wpcf7_add_tag_generator( 'tagit', __( 'Tag-it field', 'wpcf7' ), 'wpcf7-tg-pane-tagit', 'wpcf7_tg_pane_tagit' );
	}
}

function wpcf7_tg_pane_tagit() {
?>
<div id="wpcf7-tg-pane-tagit" class="hidden">
<form action="">

<table>
<tr><td><?php echo esc_html( __( 'Name', 'wpcf7' ) ); ?><br /><input type="text" name="name" class="tg-name oneline" /></td><td></td></tr>

<tr>
<td><code>id</code> (<?php echo esc_html( __( 'optional', 'wpcf7' ) ); ?>)<br />
<input type="text" name="id" class="idvalue oneline option" /></td>
</tr>

</table>

<div class="tg-tag"><?php echo esc_html( __( "Copy this code and paste it into the form left.", 'wpcf7' ) ); ?><br /><input type="text" name="tagit" class="tag" readonly="readonly" onfocus="this.select()" /></div>

<div class="tg-mail-tag"><?php echo esc_html( __( "And, put this code into the Mail fields below.", 'wpcf7' ) ); ?><br /><span class="arrow">&#11015;</span>&nbsp;<input type="text" class="mail-tag" readonly="readonly" onfocus="this.select()" /></div>
</form>
</div>
<?php
}
?>