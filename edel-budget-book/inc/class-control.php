<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit();

new EdelBudgetBookControl();

class EdelBudgetBookControl {

    function __construct() {
        add_action( 'wp_footer', array( $this, 'add_ajaxurl') );
        
        add_action( 'wp_ajax_ebb_ajax_do', array( $this, 'ebb_ajax_do') );
        add_action( 'wp_ajax_nopriv_ebb_ajax_do', array( $this, 'ebb_ajax_do') );
        add_action( 'wp_ajax_ebb_ajax_in', array( $this, 'ebb_ajax_in') );
        add_action( 'wp_ajax_nopriv_ebb_ajax_in', array( $this, 'ebb_ajax_in') );
        add_action( 'wp_ajax_ebb_ajax_rm', array( $this, 'ebb_ajax_rm') );
        add_action( 'wp_ajax_nopriv_ebb_ajax_rm', array( $this, 'ebb_ajax_rm') );
        add_action( 'wp_ajax_ebb_ajax_pi', array( $this, 'ebb_ajax_pi') );
        add_action( 'wp_ajax_nopriv_ebb_ajax_pi', array( $this, 'ebb_ajax_pi') );
        add_action( 'wp_ajax_ebb_ajax_ar', array( $this, 'ebb_ajax_ar') );
        add_action( 'wp_ajax_nopriv_ebb_ajax_ar', array( $this, 'ebb_ajax_ar') );
        add_action( 'wp_ajax_ebb_ajax_ud', array( $this, 'ebb_ajax_ud') );
        add_action( 'wp_ajax_nopriv_ebb_ajax_ud', array( $this, 'ebb_ajax_ud') );
    }

    function add_ajaxurl() {
        ?>
            <script>
                var ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
            </script>
        <?php
    }

    function ebb_ajax_do(){
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        global $wpdb;
        $table = $wpdb->prefix . "ebb_main";

        $user = wp_get_current_user();
        $my_member_id = $user->ID;;
        $date      = sanitize_option('date_format', $_POST['date']);
        $type      = intval($_POST['type']);
        $member_id = intval($_POST['member_id']);

        if( ! $member_id ) {
            echo 0;
            wp_die();
        }

        if($type) {
            $result = $wpdb->get_results("SELECT day,sum(inv) as sum_inv ,sum(ret) as sum_ret FROM $table WHERE member_id = $member_id AND day LIKE '{$date}%' group by day");
            echo json_encode($result);
            wp_die();
        } else {
            $type_line = get_option('ebb-type-lists', '');
            $types = explode(",", $type_line);
    
            foreach($types as $line) {
                $l = explode(":", $line);
                $type_list[trim($l[0])] = $l[1]; 
            }

            $result = $wpdb->get_results("SELECT * FROM $table WHERE member_id = $member_id AND day LIKE '{$date}'");

            $inv_total = 0;
            $ret_total = 0;
            $html = "<table class='ebb-selected-day'>";
            $html .= "<th style='border-right: hidden; text-align: center; vertical-align:middle!important; background-color: #ddd;'>ジャンル</th><th style='border-right: hidden; border-left: hidden; text-align: right;　vertical-align:middle!important; background-color: #ddd;'>投資金額</th><th style='border-right: hidden; border-left: hidden; text-align: right;　vertical-align:middle!important; background-color: #ddd;'>回収金額</th><th style='border-left: hidden; text-align: right; background-color: #ddd;'>収支</th>";
            foreach($result as $r){
                @$type = @$type_list[$r->type];
                $plus = $r->ret - $r->inv;
                $inv_total += $r->inv;
                $ret_total += $r->ret;
                $text = $r->text;
                $text_line = "";
                if($text) {
                   $text_line = "<p style='margin: 0; padding: 5px; text-align: left; border-top: hidden;' class='ebb-text'>${text}</p>";
                }
                $inv  = number_format($r->inv);
                $ret  = number_format($r->ret);
                $plus = number_format($plus);
                $html .= "<tr><td id='genre_{$r->id}' data-value={$r->type} style='border-right: hidden; border-bottom: hidden; text-align: center;'>{$type}</td><td id='inv_{$r->id}' data-value={$r->inv} style='border-left: hidden; border-right: hidden; border-bottom: hidden';>{$inv}</td><td id='ret_{$r->id}' data-value={$r->ret} style='border-left: hidden; border-right: hidden; border-bottom: hidden;'>{$ret}</td><td style='border-left: hidden; border-bottom: hidden;'>${plus}</td></tr>";
                $html .= "<tr><td colspan='4' style='background-color: #f0f0f0; text-align: left; width: 100%; white-space: normal!important;' class='ebb-memo-line ebb-hidden' id='ebb-text_{$r->id}'>{$text_line}";
                if($my_member_id == $member_id) {
                    $html .= "<p style='margin: 0; padding: 5px; text-align: right;'><a href='javascript:void(0)' class='ebb-edit' data-edit={$r->id}>編集</a><a href='javascript:void(0)' class='ebb-delete' data-delete={$r->id}>削除</a></p></td></tr>";
                }
            }
            $plus_total = $ret_total - $inv_total;
            $inv_total = number_format($inv_total);
            $ret_total = number_format($ret_total);
            $plus_total = number_format($plus_total);
            $html .= "<tr><td style='background-color: #CAE7F2; border-right: hidden; text-align: center;'>合計</td><td style='background-color: #CAE7F2; border-left: hidden; border-right: hidden;'>{$inv_total}</td><td style='background-color: #CAE7F2; border-left: hidden; border-right: hidden;'>{$ret_total}</td><td style='background-color: #CAE7F2; border-left: hidden;'>{$plus_total}</td></tr></table>";

            echo json_encode($html);
            wp_die();
        }
    }

    function ebb_ajax_in(){
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        global $wpdb;
        $table = $wpdb->prefix . "ebb_main";

        $user = wp_get_current_user();
        $member_id = $user->ID;
        $gen   = $this->sanitize_my_text($_POST['gen']);
        $inv   = intval($_POST['inv']);
        $ret   = intval($_POST['ret']);
        $text  = sanitize_textarea_field($_POST['text']);
        $day   = sanitize_option('date_format', $_POST['day']);
        $id    = intval($_POST['id']);

        if($id == 0) {
            $result = $wpdb->insert(
                $table,
                array(
                    'member_id' => $member_id,
                    'day'       => $day,
                    'type'      => trim($gen),
                    'text'      => $text,
                    'inv'       => $inv,
                    'ret'       => $ret,
                )
            );
            echo json_encode($result);
        } else {
            $result = $wpdb->update(
                $table,
                array(
                    'member_id' => $member_id,
                    'day'       => $day,
                    'type'      => trim($gen),
                    'text'      => $text,
                    'inv'       => $inv,
                    'ret'       => $ret,
                ),
                array(
                    'id'        => $id
                )
            );
            echo json_encode($result);
        }
        wp_die();
    }

    function ebb_ajax_rm(){
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        global $wpdb;
        $table = $wpdb->prefix . "ebb_main";

        $user = wp_get_current_user();
        $member_id = $user->ID;

        $id  = intval($_POST['id']);

        $result = $wpdb->delete(
             $table,
             array(
                'id' => $id,
                'member_id' => $member_id
            )
        );
        echo json_encode($result);
        wp_die();
    }

    function ebb_ajax_pi() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        global $wpdb;
        $table = $wpdb->prefix . "ebb_main";

        $select_month = sanitize_option('date_format', $_POST['date']);
        $search_type  = intval($_POST['type']);
        $search_ret   = $this->sanitize_my_text($_POST['ret']);
        $member_id    = intval($_POST['member_id']);

        if( ! $member_id ) {
            echo 0;
            wp_die();
        }

        if($search_type == "0") {
            $where_date = " AND day LIKE '{$select_month}%' ";
        } else if($search_type == "1") {
            $where_date = " AND day >= '{$select_month}' ";
        } else {
            $where_date = " ";
        }

        if($search_ret == "tot") {
            $sql = "SELECT type,sum(ret) - sum(inv) as value FROM $table WHERE member_id = $member_id $where_date group by type";
        } else if($search_ret == "inv") {
            $sql = "SELECT type,sum(inv) as value FROM $table WHERE member_id = $member_id $where_date group by type";
        } else {
            $sql = "SELECT type,sum(ret) as value FROM $table WHERE member_id = $member_id $where_date group by type";
        }
        
        $result = $wpdb->get_results($sql);
        echo json_encode($result);
        wp_die();
    }

    function ebb_ajax_ar() {

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        global $wpdb;
        $table = $wpdb->prefix . "ebb_main";

        $search_span = $this->sanitize_my_text($_POST['span']);
        $search_ret  = $this->sanitize_my_text($_POST['ret']);
        $member_id   = intval($_POST['member_id']);

        if( ! $member_id ) {
            echo 0;
            wp_die();
        }

        $today = date_i18n('Y-m-d');

        $sql = "SELECT day FROM $table WHERE member_id = $member_id order by day limit 1";
        $result = $wpdb->get_results($sql);
        $first_data = $result[0]->day;

        if( ! $result ) {//データなし
            echo 0;
            wp_die();
        }

        if($search_ret == "tot") {
            $search_result = "ifnull(sum(ret) - sum(inv),'0') as value";
        } else if($search_ret == "inv") {
            $search_result = "ifnull(sum(inv),'0') as value";
        } else {
            $search_result = "ifnull(sum(ret),'0') as value";
        }
        $flag = 0;
        if($search_span == "daily") {
            $sql = "SELECT day as date, {$search_result} FROM $table WHERE member_id = $member_id group by day";
        } else if($search_span == "weekly") {
            $sunday = $this::get_beginning_week_date($first_data);
    
            $next_day = $sunday;
            $sql = ""; 
            while($next_day < $today) {
                $begin = $next_day;
                $next_day = date("Y-m-d",strtotime($next_day . "+7 day"));
                $end = $next_day;

                $sql_base = "(SELECT '{$begin}' as date, {$search_result} FROM $table WHERE member_id = $member_id ";
                $select_week = "AND day BETWEEN '{$begin} 00:00:00' AND '{$end} 00:00:00')";

                $sql .= $sql_base . $select_week . " UNION ";
            }
            $sql = rtrim($sql, " UNION ");
        
        } else {
            $first_month = mb_substr($first_data, 0, 7);
            //$first_month = "2021-01";
            $this_month = date_i18n('Y-m');
            $next_month = $first_month;
            $sql = "";
            while($next_month <= $this_month) {
                $target_month = $next_month;
                $next_month = date("Y-m",strtotime($next_month . "+1 month"));
                $sql_base = "(SELECT '{$target_month}-01' as date, {$search_result} FROM $table WHERE member_id = $member_id ";
                $select_week = "AND day LIKE '{$target_month}%')";
                $sql .= $sql_base . $select_week . " UNION ";
            }
            $sql = rtrim($sql, " UNION ");
            $result =  1;
        }

        $result = $wpdb->get_results($sql);
        echo json_encode($result);
        wp_die();
    }

    function ebb_ajax_ud(){
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        global $wpdb;
        $table = $wpdb->prefix . "ebb_main";

        $flag       = intval($_POST['flag']);
        $member_id  = intval($_POST['member_id']);
        
        if( ! $member_id ) {
            echo 0;
            wp_die();
        }

        if($flag == 1) {
            update_user_meta($member_id, "ebb-disable_user_flag", $flag);
        } else {
            delete_user_meta($member_id, "ebb-disable_user_flag");
        }

        echo json_encode(1);
        wp_die();
    }

    public function get_beginning_week_date($ymd) {
        $w = date("w",strtotime($ymd));
        $beginning_week_date =
            date('Y-m-d', strtotime("-{$w} day", strtotime($ymd)));
        return $beginning_week_date;
    }

    function sanitize_my_text($str) {
        return preg_match("/^[a-z]+$/", $str) ? $str : '';
    }
}