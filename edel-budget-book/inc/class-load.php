<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit();

new EdelBudgetBookLoad();

class EdelBudgetBookLoad {
    function my_enqueue() {
        $now = date_i18n( 'Ymdhis' );
        wp_enqueue_style( 'ebb-iziModal', EBB_URL . '/css/iziModal.min.css', array());
        wp_enqueue_style( 'ebb-style',  EBB_URL . '/css/style.css', array(), "v1.0");
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'ebb-iziModal', EBB_URL . '/js/iziModal.min.js', array(), '1.5.1');     
        wp_enqueue_script( 'ebb-common', EBB_URL . '/js/common.js', array(), "v1.0");
        wp_enqueue_script( 'ebb-calender', EBB_URL . '/js/calendar.js', array(), "v1.0");
        wp_enqueue_script( 'd3.v5', EBB_URL . '/js/d3.v5.js', array(), "v5");
    }

    function my_enqueue_do() {
        add_action( 'wp_enqueue_scripts', array($this, 'my_enqueue') );
    }
}