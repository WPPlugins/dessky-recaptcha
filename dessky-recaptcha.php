<?php
/*
* Plugin Name: Dessky reCAPTCHA
* Description: This is the lightweight plugin for website protection against spam comments and brute-force attacks.
* Version: 1.0
* Author: Dessky
* Author URI: http://dessky.com
* License: GPL3
* Text Domain: drcp
*/

function drcp_add_plugin_action_links($links) {
	return array_merge(array("settings" => "<a href=\"options-general.php?page=drcp-options\">".__("Settings", "drcp")."</a>"), $links);
}
add_filter("plugin_action_links_".plugin_basename(__FILE__), "drcp_add_plugin_action_links");

function drcp_options_page() {
	echo "<div class=\"wrap\">
	<h1>".__("Dessky reCAPTCHA Settings", "drcp")."</h1>
	<form method=\"post\" action=\"options.php\">";
	settings_fields("drcp_header_section");
	do_settings_sections("drcp-options");
	submit_button();
	echo "</form>
	</div>";
}

function drcp_menu() {
	add_submenu_page("options-general.php", "Dessky reCAPTCHA", "Dessky reCAPTCHA", "manage_options", "drcp-options", "drcp_options_page");
}
add_action("admin_menu", "drcp_menu");

function drcp_display_options() {
	add_settings_section("drcp_header_section", null, "drcp_display_content", "drcp-options");
	add_settings_field("drcp_site_key", __("Site Key", "drcp"), "drcp_display_site_key_element", "drcp-options", "drcp_header_section");
	add_settings_field("drcp_secret_key", __("Secret Key", "drcp"), "drcp_display_secret_key_element", "drcp-options", "drcp_header_section");
	add_settings_field("drcp_logged_users_comments_disable", null, "drcp_display_logged_users_comments_disable", "drcp-options", "drcp_header_section");
	add_settings_field("drcp_comment_form_disable", null, "drcp_display_comment_form_disable", "drcp-options", "drcp_header_section");
	add_settings_field("drcp_login_form_disable", null, "drcp_display_login_form_disable", "drcp-options", "drcp_header_section");
	add_settings_field("drcp_register_form_disable", null, "drcp_display_register_form_disable", "drcp-options", "drcp_header_section");
	add_settings_field("drcp_forgot_form_disable", null, "drcp_display_forgot_form_disable", "drcp-options", "drcp_header_section");
	
	register_setting("drcp_header_section", "drcp_site_key");
	register_setting("drcp_header_section", "drcp_secret_key");
	register_setting("drcp_header_section", "drcp_logged_users_comments_disable");
	register_setting("drcp_header_section", "drcp_comment_form_disable");
	register_setting("drcp_header_section", "drcp_login_form_disable");
	register_setting("drcp_header_section", "drcp_register_form_disable");
	register_setting("drcp_header_section", "drcp_forgot_form_disable");
}

function drcp_display_content() {
	echo __("<p class=\"description\">First you need to <a href=\"https://www.google.com/recaptcha/admin\" rel=\"external\" target=\"_blank\">get required keys from Google</a>, then save them below.</p>", "drcp");
}

function drcp_display_site_key_element() {
	echo "<input type=\"text\" name=\"drcp_site_key\" class=\"regular-text\" id=\"drcp_site_key\" value=\"".get_option("drcp_site_key")."\" />";
}

function drcp_display_secret_key_element() {
	echo "<input type=\"text\" name=\"drcp_secret_key\" class=\"regular-text\" id=\"drcp_secret_key\" value=\"".get_option("drcp_secret_key")."\" />";
}

function drcp_display_logged_users_comments_disable() {
	echo "<label for=\"drcp_logged_users_comments_disable\"><input type=\"checkbox\" name=\"drcp_logged_users_comments_disable\" id=\"drcp_logged_users_comments_disable\" value=\"1\" ".checked(1, get_option("drcp_logged_users_comments_disable"), false)." />".__("Disable reCAPTCHA in comment form for logged in users", "drcp")."</label>";
}

function drcp_display_comment_form_disable() {
	echo "<label for=\"drcp_comment_form_disable\"><input type=\"checkbox\" name=\"drcp_comment_form_disable\" id=\"drcp_comment_form_disable\" value=\"1\" ".checked(1, get_option("drcp_comment_form_disable"), false)." />".__("Disable reCAPTCHA for comments", "drcp")."</label>";
}

function drcp_display_login_form_disable() {
	echo "<label for=\"drcp_login_form_disable\"><input type=\"checkbox\" name=\"drcp_login_form_disable\" id=\"drcp_login_form_disable\" value=\"1\" ".checked(1, get_option("drcp_login_form_disable"), false)." />".__("Disable reCAPTCHA for login page", "drcp")."</label>";
}

function drcp_display_register_form_disable() {
	echo "<label for=\"drcp_register_form_disable\"><input type=\"checkbox\" name=\"drcp_register_form_disable\" id=\"drcp_register_form_disable\" value=\"1\" ".checked(1, get_option("drcp_register_form_disable"), false)." />".__("Disable reCAPTCHA for register page", "drcp")."</label>";
}

function drcp_display_forgot_form_disable() {
	echo "<label for=\"drcp_forgot_form_disable\"><input type=\"checkbox\" name=\"drcp_forgot_form_disable\" id=\"drcp_forgot_form_disable\" value=\"1\" ".checked(1, get_option("drcp_forgot_form_disable"), false)." />".__("Disable reCAPTCHA for forgot password page", "drcp")."</label>";
}

add_action("admin_init", "drcp_display_options");

function frontend_drcp_script() {
	wp_register_script("recaptcha", "https://www.google.com/recaptcha/api.js");
	wp_enqueue_script("recaptcha");
	$plugin_url = plugin_dir_url(__FILE__);
	wp_enqueue_style("style", $plugin_url."assets/css/dessky-recaptcha.css");
}
add_action("wp_enqueue_scripts", "frontend_drcp_script");
add_action("login_enqueue_scripts", "frontend_drcp_script");

function drcp_display() {
	echo "<div class=\"g-recaptcha\" data-sitekey=\"".get_option("drcp_site_key")."\"></div>";
}

function drcp_verify($input) {
	if (isset($_POST["g-recaptcha-response"])) {
		$recaptcha_response = sanitize_text_field($_POST["g-recaptcha-response"]);
		$recaptcha_secret = get_option("drcp_secret_key");
		$response = wp_remote_get("https://www.google.com/recaptcha/api/siteverify?secret=".$recaptcha_secret."&response=".$recaptcha_response);
		$response = json_decode($response["body"], true);
		if ($response["success"] == true) {
			return $input;
		} else {
			wp_die(__("<p><strong>ERROR</strong>: Google reCAPTCHA verification failed.</p>", "drcp")."</p>\n\n<p><a href=".wp_get_referer().">&laquo; Try Again</a>");
			return null;
		}
	} else {
		wp_die(__("<p><strong>ERROR</strong>: Google reCAPTCHA verification failed. Do you have JavaScript enabled?</p>", "drcp")."</p>\n\n<p><a href=".wp_get_referer().">&laquo; Go Back</a>");
		return null;
	}
}

function drcp_check() {
	
	$commentdata = null;
	$user = null;
	$errors = null;
	
	if (get_option("drcp_site_key") != "" && get_option("drcp_secret_key") != "") {
		
		if (get_option("drcp_comment_form_disable") != "1" && ((is_user_logged_in() && get_option("drcp_logged_users_comments_disable") != "1") || !is_user_logged_in())) {
			add_action("comment_form_after_fields", "drcp_display");
			add_action("comment_form_logged_in_after", "drcp_display");
			if ($_SERVER["REQUEST_METHOD"] == "POST") {
				do_action("preprocess_comment", $commentdata);
				add_action("preprocess_comment", "drcp_verify");
			}
		}
		
		if (get_option("drcp_login_form_disable") != "1") {
			add_action("login_form", "drcp_display" );
			if ($_SERVER["REQUEST_METHOD"] == "POST") {
				do_action("wp_authenticate_user", $user);
				add_action("wp_authenticate_user", "drcp_verify");
			}
			
		}
		
		if (get_option("drcp_register_form_disable") != "1") {
			add_action("register_form", "drcp_display");
			if ($_SERVER["REQUEST_METHOD"] == "POST") {
				do_action("registration_errors", $errors);
				add_action("registration_errors", "drcp_verify");
			}
		}
		
		if (get_option("drcp_forgot_form_disable") != "1") {
		add_action("lostpassword_form", "drcp_display");
			if ($_SERVER["REQUEST_METHOD"] == "POST") {
				do_action("lostpassword_post", $errors);
				add_action("lostpassword_post", "drcp_verify");
			}
		}
	}
}
add_action("init", "drcp_check");