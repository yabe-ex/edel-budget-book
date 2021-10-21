<?php
/*
Plugin Name: Edel Budget Book
Plugin URI: https://edel-hearts.com/edel-budget-book/?member=1
Description: カレンダーに日々の収支を入力して、収支表やグラフを作成します。
Version: 1.0
Author: yabea
Author URI: https://edel-hearts.com/about-us/
License: GPL2

    Copyright 2021 yabea (email : info@edel-hearts.com)
 
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
     published by the Free Software Foundation.
 
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
 
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit();

define('EBB_URL', plugins_url('', __FILE__));
define('EBB_PATH', dirname(__FILE__));

new EdelBudgetBook();

class EdelBudgetBook {
    public function __construct(){

        require_once( EBB_PATH . '/inc/class-load.php' );
        require_once( EBB_PATH . '/inc/class-admin.php' );
        require_once( EBB_PATH . '/inc/class-control.php' );

        $admin = new EdelBudgetBookAdmin();
        
        register_activation_hook(__FILE__, array( $admin, 'db_activation' ) );
        add_action( 'admin_menu', array( $admin, 'plugin_setup_menu' ) );
        add_shortcode( 'ebb', array($admin, 'show_ebb') );
    }
}