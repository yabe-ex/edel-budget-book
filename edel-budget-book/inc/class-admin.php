<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit();

new EdelBudgetBookAdmin();

class EdelBudgetBookAdmin {
    public function __construct(){
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue'));

        $type_line = get_option('ebb-type-lists', '');
        if(!$type_line) {
            $type_default = <<< _TYPE_
slot:スロット,
baccarat:バカラ,
roulette:ルーレット,
blackjack:ブラックジャック
_TYPE_;
            update_option('ebb-type-lists', $type_default);
        }
    }

    function admin_enqueue() {
        wp_enqueue_script( 'admin-edel-jscolor', EBB_URL . '/js/jscolor.js', array(), "2.4.5");
    }

    function plugin_setup_menu() {
        add_submenu_page(
            'options-general.php',
            'EdelBudgetBook',
            'Edel Budget Book',
            'manage_options',
            'edel-budget-book',
            array($this, 'main_page'),
            11
        );
    }

    function main_page() {
        if( array_key_exists('submit_scripts_update', $_POST) && check_admin_referer('edel-budget-book', '_wpnonce') ) {
            update_option('ebb-type-lists', sanitize_textarea_field($_POST['type-lists']));
            update_option('ebb-logout-message', sanitize_textarea_field($_POST['logout-message']));
            update_option('ebb-disable-message', sanitize_textarea_field($_POST['disable-message']));
            if($_POST['basic-color']) {
                update_option('ebb-basic-color', '#' . sanitize_text_field($_POST['basic-color']));
            }
        }
        $basic_color = get_option('ebb-basic-color', '#0093c5');
        $type_line = get_option('ebb-type-lists', '');
        $logout_message = get_option('ebb-logout-message', '');
        $disable_message = get_option('ebb-disable-message', '');
        $types = explode(",", $type_line);

        foreach($types as $line) {
            $l = explode(":", $line);
            @$type_list[@$l[0]] = @$l[1]; 
        }
?>
        <div class="wrap">
        <h2 style="margin: 10px;">Edel Budget Book設定</h2>
        <h3 style="margin: 15px;">基本設定</h3>
        <form method="post" action="">
            <?php wp_nonce_field('edel-budget-book', '_wpnonce'); ?>
            <table style="width: 95%">
                <tr>
                <th style="padding: 20px 10px;">ベースカラー</th>
                    <td><input type="text" class="color-picker" name="basic-color" value="<?php echo esc_attr($basic_color); ?>"></td>
                </tr>
                <tr>
                    <th style="padding: 20px 10px; width: 160px">項目リスト</th>
                    <td><textarea name="type-lists" style="width: 400px; height: 200px; padding: 10px;" placeholder="1行に１項目を入れてください。"><?php echo esc_textarea($type_line); ?></textarea></td>
                </tr>
                <tr>
                    <th style="padding: 20px 10px; width: 160px">非公開ユーザーページに<br />表示されるメッセージ</th>
                    <td><textarea name="disable-message" style="width: 400px; height: 100px; padding: 10px;" placeholder="非公開のユーザーのページにアクセスしたときに表示されるメッセージを入れてください。"><?php echo esc_textarea($disable_message) ?></textarea></td>
                </tr>
                <tr>
                    <th style="padding: 20px 10px; width: 160px">ログアウト時に<br />表示されるメッセージ</th>
                    <td><textarea name="logout-message" style="width: 400px; height: 100px; padding: 10px;" placeholder="ログインしていない非会員がアクセスしたときに表示されるメッセージを入れてください。"><?php echo esc_textarea($logout_message) ?></textarea></td>
                </tr>
            </table>
            <input type="submit" id="setting-update" name="submit_scripts_update" class="button button-primary" value="設定の更新" style="margin: 30px 0 0 50px;">
        </form>
        </div>

        <?php
    }

    function show_ebb() {
        $user = wp_get_current_user();
        $edit = "";
        $show_flag = true;
        $member_line = "";
        $system_member_id = $user->ID;

        if( isset($_GET) && array_key_exists('member', $_GET) ) {

            $show_flag = false;
            $member_id = intval($_GET['member']);
            if( ! $member_id ) return "<p>対象のユーザーは存在しません。</p>";
            $user_data = get_userdata($member_id);
            if(!$user_data) return "<p>対象のユーザーは存在しません。</p>";
        } else {
            $member_id = $user->ID;
            if(!$system_member_id) {
                $logout_message = get_option("ebb-logout-message", "<p>ログインしてください。</p>");
                $logout_message = str_replace('\"', '"', $logout_message);
                $logout_message = str_replace("\'", "'", $logout_message);
                return "{$logout_message}";
            }    
        }
        $member_line = "<input type='hidden' name='member_id' value='{$member_id}'>";

        $base_color = get_option('ebb-basic-color', "#0093c5");
        $today = date_i18n("Y年m月d日");

        $button_color = "style='background-color: {$base_color}!important; border: 2px solid {$base_color};'";

        $type_line = get_option('ebb-type-lists', '');
        $types = explode(",", $type_line);

        foreach($types as $line) {
            $l = explode(":", $line);
            @$type_list[trim(@$l[0])] = @$l[1]; 
        }

        $disable_user_flag = get_user_meta($member_id, "ebb-disable_user_flag", true);
        if($disable_user_flag) {
            $add_class = "";
            $add_text  = "非公開中";
        } else {
            $add_class = "selected-pie";
            $add_text  = "公開中";
        }

        if($system_member_id == 1) {
            $ebb_show_user = "<a href='javascript:void(0)' class='ebb-show-user {$add_class}' style='width: 60px;'>{$add_text}</a>";
        } else {
            $ebb_show_user = "<a href='javascript:void(0)' class='ebb-show-user-disabled selected-pie' style='width: 60px;'>公開中</a>";
        }
        if($show_flag) {
            $edit = "<a href='javascript:void(0)' class='ebb-add-button' {$button_color}>＋</a>";
            $ebb_show_user = "<a href='javascript:void(0)' class='ebb-show-user {$add_class}' style='width: 60px;'>{$add_text}</a>";
        } else {
            if($disable_user_flag && $system_member_id != 1) {
                $disable_message = get_option("ebb-disable-message", "<p>非公開中です。</p>");
                return $disable_message;
            }
        }

        $load = new EdelBudgetBookLoad();
        add_action('wp_footer', array($load, 'my_enqueue') );

        $user = get_userdata($member_id);
        $display_name = $user->display_name;

        $select_genre  = "<table class='ebb-input'><tr><td style='margin: 5px; padding: 5px; width: 90px; text-align: right; vertical-align: middle!important'>ジャンル</td><td>";
        $select_genre .= "<select name='select-genre' id='ebb-genre-option'>";
        foreach($type_list as $k => $v) {
            $select_genre .= "<option value='{$k}'>{$v}</option>";
        }
        $select_genre .= <<< _GENRE_
            </select></td></tr><tr>
            <td style='margin: 5px; padding: 5px; width: 90px; text-align: right; vertical-align: middle!important'>投資金額</td>
            <td><input type='text' id='input-invest' name='input-invest' value=''></td></tr><tr>
            <td style='margin: 5px; padding: 5px; width: 90px; text-align: right; vertical-align: middle!important'>回収金額</td>
            <td><input type='text' id='input-return' name='input-return' value=''></td></tr><tr>
            <td colspan='4'><textarea id='input-text' name='input-text' placeholder='メモを入力できます（128文字まで）'></textarea></td>
            <input type='hidden' id='input-id' value=0>
            </tr></table>
            <a href='javascript:void(0)' id='ebb-input-submit' {$button_color}>決定</a>
_GENRE_;

        $html = <<< _EOF_
        <style>
    .selected-pie {
        background-color: ${base_color} !important;
      }
    </style>
    <div class="ebb-post_row">
    <div class="ebb-post_col ebb-post_col-2">
    {$member_line}
	<div class="container-calendar">
          <div class="button-container-calendar">
            <h2 id="member_name" style="margin: 0; padding: 0;">{$display_name}さんの戦歴</h2>
            <p id="monthAndYear" style="margin: 0; padding: 0; color: {$base_color}"></p>
            <button id="previous" style="background-color: ${base_color}; border: 1px solid {$base_color}" onclick="previous()">‹</button>
            <button id="next" style="background-color: ${base_color}; border: 1px solid {$base_color}" onclick="next()">›</button>
          </div>
          
          <table class="table-calendar" id="calendar" data-lang="ja">
              <thead id="thead-month"></thead>
              <tbody id="calendar-body"></tbody>
          </table>
          {$edit}
          <div id='modal_ebb_add' data-izimodal-title="収支の入力"  data-izimodal-subtitle="勝敗の結果を入力してください。">
          <div id='modal_ebb_inner' style='margin: 10px;' class='ebb-hidden'>{$select_genre}</div></div>
    </div>
_EOF_;

        $js_list = "";
        foreach($type_list as $k => $v) {
            $js_list .= "{$k}:'{$v}',";
        }
        $js_list = rtrim($js_list, ",");
        $js_list = trim($js_list);
        $genre_list = "var genre_list = {" . $js_list . "}";

        $html .= "<script>{$genre_list}</script>";

        $html .= <<< _EOF2_
        </div>
        <div class="ebb-post_col ebb-post_col-2">
          <div id="user-content" style="border: 1px solid #fff; margin: 1px; padding: 1px;">
            <p class="selected_date" style="text-align: center; margin: 5px; padding: 5px; font-size: 20px; color: #999;"></p>
           <!-- <p class="ebb-button-list">-->
            <div class="ebb-flex ebb-button-list">
            <!--<a href='javascript:void(0)' class='ebb-show-user selected-pie' style='width: 40px;'>公開中</a>-->
            {$ebb_show_user}
            <a href='javascript:void(0)' class='ebb-show-memo' style='width: 140px; background-color: #333;'>メモを表示/非表示</a></div>
            <div id="ebb-table"></div>
          </div>
        </div>
      </div>
      <div class="ebb-post_row">
        <div class="ebb-post_col ebb-post_col-2" style="border: 1px solid #fff; text-align: center;  margin: 0 auto; text-align: center;">
          <div class="ebb-flex">
            <a id="pie-inv" class="ebb-flex-pie-button2" style="width: 90px!important;" href="javascript:void(0)">投資</a>
            <a id="pie-ret" class="ebb-flex-pie-button2" style="width: 90px!important;" href="javascript:void(0)">回収</a>
            <a id="pie-tot" class="ebb-flex-pie-button2" style="width: 90px!important;" href="javascript:void(0)">利益</a>
          </div>
          <div class="ebb-flex">
            <a id="pie-day" class="ebb-flex-pie-button" href="javascript:void(0)">当日</a>
            <a id="pie-week" class="ebb-flex-pie-button" href="javascript:void(0)">今週</a>
            <a id="pie-month" class="ebb-flex-pie-button" href="javascript:void(0)">今月</a>
            <a id="pie-year" class="ebb-flex-pie-button" href="javascript:void(0)">今年</a>
            <a id="pie-all" class="ebb-flex-pie-button" href="javascript:void(0)">全て</a>
          </div>
          <div id="ebb-pie-chart-notice" class="ebb-hidden"></div>
          <svg id="ebb-pie-chart" width="310" height="310"></svg>
        </div>
        <div class="ebb-post_col ebb-post_col-2" style="border: 1px solid #fff; text-align: center;  margin: 0 auto; text-align: center;">
          <div class="ebb-flex" style="margin-top:0;">
            <a id="area-day" class="ebb-flex-area-button" style="width: 90px!important;" href="javascript:void(0)">日次</a>
            <a id="area-week" class="ebb-flex-area-button" style="width: 90px!important;" href="javascript:void(0)">週次</a>
            <a id="area-month" class="ebb-flex-area-button" style="width: 90px!important;" href="javascript:void(0)">月次</a>
          </div>
          <svg id="ebb-area-chart" width="320" height="310" style="margin-top: 30px;"></svg>
        </div>
      </div>
_EOF2_;
        return $html;
    }

    function db_activation() {
        global $wpdb;
        $table = $wpdb->prefix.'ebb_main';

        if($wpdb->get_var("show table like '$table'") != $table) {
            $sql = "CREATE TABLE {$table} (
                id INT(8) NOT NULL AUTO_INCREMENT,
                member_id INT(8),
                day DATE NOT NULL,
                type VARCHAR(32),
                text VARCHAR(128),
                inv INT(16),
                ret INT(16),
                updated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY id (id)
            ) ENGINE=InnoDB DEFAULT CHARSET = utf8;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }
}