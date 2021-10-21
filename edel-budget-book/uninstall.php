<?php

if (!defined('WP_UNINSTALL_PLUGIN')) die;

delete_option('ebb-public-select');
delete_option('ebb-basic-color');
delete_option('ebb-type-lists');
delete_option('ebb-logout-message');
delete_option('ebb-disable-message');

global $wpdb;
$table = $wpdb->prefix.'ebb_main';
$sql = "DROP TABLE IF EXISTS ${table}";
$wpdb->query($sql);
