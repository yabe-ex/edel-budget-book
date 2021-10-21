(function($) {
  var selected_month;
  var today_format;

  function getNumberFormat(number, decimals) {
    if (!decimals) {
      var decimals = 0;
    }

    // 位をずらし四捨五入
    var place = Math.pow(10, decimals);
    var number = Math.round(number * place) / place;
    // 整数と小数に分割
    var num = number.toString().split(".");
    // カンマ付与
    var integer = num[0].replace(/([0-9]{1,3})(?=(?:[0-9]{3})+$)/g, "$1,");

    var str;
    if (num[1]) {
      if (num[1].length < decimals) {
        // 指定桁数まで0を追加
        var zero = decimals - num[1].length;
        for (var i = 0; i < zero; i++) {
          num[1] = num[1] + "0";
        }
      }
      str = integer + "." + num[1];
    } else {
      str = integer;
    }

    return str;
  }

  function draw_pie_chart(data) {
    if (data.length == 0) {
      $("#ebb-pie-chart-notice").html("<p>データがありません。</p>");
      $("#ebb-pie-chart-notice").removeClass("ebb-hidden");
      return false;
    } else if (data.length == 1) {
      data.push({ type: "iii", value: "0" });
    }

    var width = 300;
    var height = 300;

    // dataの nameごとにグラフで使用する色を設定
    var color = d3
      .scaleOrdinal()
      .domain(data.map(d => d.type))
      .range(
        d3
          .quantize(t => d3.interpolateSpectral(t * 0.8 + 0.1), data.length)
          .reverse()
      );

    // 円グラフの内側・外側半径の設定
    var arc = d3
      .arc()
      .innerRadius(0)
      .outerRadius(Math.min(width, height) / 2 - 1);

    // 円グラフのラベル表示位置を設定
    const radius = (Math.min(width, height) / 2) * 0.57;
    var arcLabel = d3
      .arc()
      .innerRadius(radius)
      .outerRadius(radius);

    // 円グラフ作成用の角度を計算
    var sum = 0;
    var arcs = d3
      .pie()
      .sort(null) // data配列の順番でグラフを作成（未指定の場合は、valueの降順）
      .value(function(d) {
        sum += Number(d.value);
        return d.value;
      })(data);

    // 円グラフ描画エリアを選択
    var svg = d3
      .select("#ebb-pie-chart")
      .attr("text-anchor", "middle")
      .style("font", "12px sans-serif");

    // g要素を追加
    const g = svg
      .append("g")
      .attr("transform", `translate(${width / 2},${height / 2})`);

    // g要素に各データのpath要素を追加
    g.selectAll("path")
      .data(arcs)
      .enter()
      .append("path")
      .attr("fill", d => color(d.data.type))
      .attr("stroke", "white")
      .attr("d", arc)
      .append("title")
      .text(d => `${d.data.type}: ${d.data.value.toLocaleString()}`);

    // データごとにtext要素を設定
    const text = g
      .selectAll("text")
      .data(arcs)
      .enter()
      .append("text")
      .attr("transform", d => `translate(${arcLabel.centroid(d)})`)
      .attr("dy", "0.35em");

    // text要素にデータ名を設定（dataのnameを設定）
    text
      .filter(d => d.data.value > 0)
      .filter(d => d.endAngle - d.startAngle > 0.3)
      .append("tspan")
      .attr("x", 0)
      .attr("y", "-0.7em")
      .style("font-weight", "bold")
      //      .text(d => d.data.type);
      .text(function(d) {
        return genre_list[d.data.type];
      });

    // text要素にデータ名を設定（dataのvalueを設定）
    text
      //        .filter(d => d.endAngle - d.startAngle > 0.7535)
      .filter(d => d.data.value > 0)
      .filter(d => d.endAngle - d.startAngle > 0.3)
      .append("tspan")
      .attr("x", 0)
      .attr("y", "1.0em")
      .attr("fill-opacity", 0.7)
      //      .text(d => d.data.value.toLocaleString());
      .text(function(d) {
        return getNumberFormat(d.data.value, 0);
      });
    svg
      .append("text")
      .attr("x", 150)
      .attr("y", 30)
      .text("総額: " + getNumberFormat(sum) + "円");
  }

  //area chart
  function draw_area_chart(data) {
    var margin = { top: 30, right: 5, bottom: 20, left: 60 };

    var width = 310;
    var height = 300;

    const svg = d3.select("#ebb-area-chart");

    // X軸の設定（日付の最小・最大値、描画範囲）
    var x = d3
      .scaleUtc()
      .domain(
        d3.extent(data, function(d) {
          return new Date(d.date);
        })
      )
      .range([margin.left, width - margin.right]);

    var max = d3.max(data, function(d) {
      return d.value * 1;
    });

    // Y軸の設定（描画データの最小・最大値、描画範囲）
    var y = d3
      .scaleLinear()
      .domain([
        0,
        d3.max(data, function(d) {
          return d.value * 1;
        })
      ])
      .nice()
      .range([height - margin.bottom, margin.top]);

    // Y軸を追加
    var yAxis = d3.axisLeft(y);
    svg
      .append("g")
      .attr("transform", `translate(${margin.left},0)`)
      //      .attr("transform", `translate(${margin.left},0)`)
      .call(yAxis);

    // chart描画エリア

    var area = d3
      .area()
      .x(function(d) {
        return x(new Date(d.date));
      })
      .y1(function(d) {
        return y(d.value);
      })
      .y0(y(0));

    // pathの設定（各データを描画）
    svg
      .append("path")
      .datum(data)
      .attr("fill", "steelblue")
      .attr("d", area);
    // X軸を追加
    var xAxis = d3.axisBottom(x).ticks(7, "%m");
    svg
      .append("g")
      .attr("transform", `translate(0,${height - margin.bottom})`)
      .call(xAxis);
  }

  function show_line_chart(dataset) {
    for (var i = 0; i < dataset.length; i++) {
      dataset[i].value = Number(dataset[i].value);
    }

    const svg = d3.select("#ebb-area-chart");

    width = 310;
    height = 270;
    var padding = 60;

    let timeparser = d3.timeParse("%Y-%m-%d");
    // x軸の目盛りの表示フォーマット
    let format = d3.timeFormat("%m/%d");
    // データをパースします
    dataset = dataset.map(function(d) {
      // 日付のデータをパース
      return { date: timeparser(d.date), value: d.value };
    });

    // svg要素にg要素を追加しクラスを付与しxに代入
    x = svg.append("g").attr("class", "axis axis-x");

    // svg要素にg要素を追加しクラスを付与しyに代入
    y = svg.append("g").attr("class", "axis axis-y");

    // x軸の目盛りの量
    //let xTicks = window.innerWidth < 768 ? 6 : 12;
    let xTicks = 5;
    // X軸を時間のスケールに設定する
    xScale = d3
      .scaleTime()
      // 最小値と最大値を指定しX軸の領域を設定する
      .domain([
        // データ内の日付の最小値を取得
        d3.min(dataset, function(d) {
          return d.date;
        }),
        // データ内の日付の最大値を取得
        d3.max(dataset, function(d) {
          return d.date;
        })
      ])
      // SVG内でのX軸の位置の開始位置と終了位置を指定しX軸の幅を設定する
      .range([padding, width]);

    // Y軸を値のスケールに設定する
    yScale = d3
      .scaleLinear()
      // 最小値と最大値を指定しX軸の領域を設定する
      .domain([
        // 0を最小値として設定
        //0,
        d3.min(dataset, function(d) {
          return parseInt(d.value);
        }),
        // データ内のvalueの最大値を取得
        d3.max(dataset, function(d) {
          return parseInt(d.value);
        })
      ])
      // SVG内でのY軸の位置の開始位置と終了位置を指定しY軸の幅を設定する
      .range([height, padding]);

    // scaleをセットしてX軸を作成
    axisx = d3
      .axisBottom(xScale)
      // グラフの目盛りの数を設定
      .ticks(xTicks)
      // 目盛りの表示フォーマットを設定
      .tickFormat(format);

    // scaleをセットしてY軸を作成
    axisy = d3.axisLeft(yScale);

    // X軸の位置を指定し軸をセット
    x.attr("transform", "translate(" + 0 + "," + height + ")")
      .call(axisx)
      .selectAll("text")
      .style("text-anchor", "end")
      .attr("dx", "-.8em")
      .attr("dy", ".15em")
      .attr("transform", "rotate(-65)");

    // Y軸の位置を指定し軸をセット
    y.attr("transform", "translate(" + padding + "," + 0 + ")").call(axisy);

    let color = d3.rgb("#85a7cc");
    // パス要素を追加
    path = svg.append("path");
    //lineを生成
    line = d3
      .line()
      // lineのX軸をセット
      .x(function(d) {
        return xScale(d.date);
      })
      // lineのY軸をセット
      .y(function(d) {
        return yScale(parseInt(d.value));
      });

    path
      // dataをセット
      .datum(dataset)
      // 塗りつぶしをなしに
      .attr("fill", "none")
      // strokeカラーを設定
      .attr("stroke", color)
      .attr("stroke-width", 2)
      // d属性を設定
      .attr("d", line);

    /*
    if (
      d3.min(dataset, function(d) {
        return parseInt(d.value);
      }) < 0
    ) {
      svg
        .append("g")
        .attr("transform", "translate(" + padding + "," + 0 + ")")
        .call(
          d3
            .axisLeft(yScale)
            .ticks(1)
            .tickSize(-width + padding)
            .tickFormat(function(d) {
              return "";
            })
        );
    }
    */
  }

  function ajax_fire(date_str, type, member_id) {
    $.ajax({
      type: "POST",
      url: ajaxurl,
      data: {
        action: "ebb_ajax_do",
        date: date_str,
        type: type,
        member_id: member_id
      },
      dataType: "json"
    })
      .done(function(data) {
        if (!type) {
          var content = $("#ebb-table");
          content.html(data);
        } else {
          $.each(data, function(i, v) {
            var ymd = v.day.split("-");
            var year = ymd[0];
            var month = Number(ymd[1]);
            var day = Number(ymd[2]);
            var target = $(`[data-date=${day}] p.content`);

            //var gain = (Number(v.sum_ret) - Number(v.sum_inv)) / 10000;
            var gain = Number(v.sum_ret) - Number(v.sum_inv);

            var font_class = "minus";
            if (gain > 0) {
              font_class = "plus";
            }
            gain = Math.abs(gain);
            gain = getNumberFormat(gain);
            //gain = gain.toFixed(1);

            target.removeClass("gray");
            target.addClass(`input ${font_class}`);
            target.html(gain);
          });
        }
      })
      .fail(function(XMLHttpRequest, textStatus, error) {
        console.log("失敗" + error);
        console.log(XMLHttpRequest.responseText);
      });
  }

  function ajax_insert(gen, inv, ret, text, date) {
    var db_id = $("#input-id").val();
    $.ajax({
      type: "POST",
      url: ajaxurl,
      data: {
        action: "ebb_ajax_in",
        gen: gen,
        inv: inv,
        ret: ret,
        text: text,
        day: date,
        id: db_id
      },
      dataType: "json"
    })
      .done(function(data) {
        if (data > 0) {
          var today_format = get_today_format();
          var member_id = $('input[name="member_id"]').val();
          ajax_fire(today_format, 0, member_id);
          ajax_fire(today_format, 1, member_id);

          find = $(".ebb-flex-pie-button2" + ".selected-pie");

          prepare_area_chart();
        } else {
          console.log("DB更新処理に失敗しました。");
        }
      })
      .fail(function(XMLHttpRequest, textStatus, error) {
        console.log("失敗" + error);
        console.log(XMLHttpRequest.responseText);
      });
  }

  function ajax_pie_chart(select_date, search_type, ret) {
    var member_id = $('input[name="member_id"]').val();
    $("#ebb-pie-chart-notice").addClass("ebb-hidden");
    $("#ebb-pie-chart").empty();
    $.ajax({
      type: "POST",
      url: ajaxurl,
      data: {
        action: "ebb_ajax_pi",
        date: select_date,
        type: search_type,
        ret: ret,
        member_id: member_id
      },
      dataType: "json"
    })
      .done(function(data) {
        if (data.length > 0) {
          draw_pie_chart(data);
          $("#ebb-pie-chart-notice").addClass("ebb-hidden");
        } else {
          $("#ebb-pie-chart-notice").removeClass("ebb-hidden");
        }
      })
      .fail(function(XMLHttpRequest, textStatus, error) {
        console.log("失敗" + error);
        console.log(XMLHttpRequest.responseText);
      });
  }

  function ajax_area_chart(span, ret) {
    var member_id = $('input[name="member_id"]').val();
    $("#ebb-area-chart").empty();
    $.ajax({
      type: "POST",
      url: ajaxurl,
      data: {
        action: "ebb_ajax_ar",
        span: span,
        ret: ret,
        member_id: member_id
      },
      dataType: "json"
    })
      .done(function(data) {
        if (ret == "tot") {
          show_line_chart(data);
        } else {
          if (data.length > 0) {
            draw_area_chart(data);
          } else {
            console.log("データがありません");
          }
        }
      })
      .fail(function(XMLHttpRequest, textStatus, error) {
        console.log("失敗" + error);
        console.log(XMLHttpRequest.responseText);
      });
  }

  function str2thismonth(str_month) {
    var ym = str_month.split(/年|月/);
    return (this_month = ym[0] + "-" + ("0" + ym[1]).slice(-2)); // yyyy-mm format
  }

  function isNumber(val) {
    var pattern = /^\d*$/;
    return pattern.test(val);
  }

  function get_today_format() {
    var date = $(".selected_date").text();
    date = date.replace("年", "-");
    date = date.replace("月", "-");
    date = date.replace("日", "");

    var ymd = date.split("-");

    return (
      ymd[0] + "-" + ("0" + ymd[1]).slice(-2) + "-" + ("0" + ymd[2]).slice(-2)
    );
  }

  function prepare_area_chart() {
    /*      var svg = $("#ebb-pie-chart");
      var target = $(this);
      $(".ebb-flex-pie-button").removeClass("selected-pie");
      target.addClass("selected-pie");
*/
    var select_date;
    var search_type;

    var target = $(".ebb-flex-pie-button" + ".selected-pie").attr("id");

    if (target == "pie-day") {
      select_date = get_today_format();
      search_type = 0;
    } else if (target == "pie-week") {
      var curr = new Date();
      var first = curr.getDate() - curr.getDay();
      //var last = first + 6;
      //var firstday = new Date(curr.setDate(first)).toUTCString();
      var startday = new Date(curr.setDate(first));

      var year = startday.getFullYear();
      var month = startday.getMonth() + 1;
      var day = startday.getDate();

      select_date =
        year + "-" + ("0" + month).slice(-2) + "-" + ("0" + day).slice(-2);
      search_type = 1;
    } else if (target == "pie-month") {
      var select_month = $("#monthAndYear").text();
      select_date = str2thismonth(select_month);
      search_type = 0;
    } else if (target == "pie-year") {
      var select_month = $("#monthAndYear").text();
      var year = select_month.split("年");
      select_date = year[0];
      search_type = 0;
    } else if (target == "pie-all") {
      select_date = 0;
      search_type = 2;
    }

    var ret;
    var find = $(".ebb-flex-pie-button2" + ".selected-pie").attr("id");

    if (find == "pie-tot") {
      ret = "tot";
    } else if (find == "pie-inv") {
      ret = "inv";
    } else {
      ret = "ret";
    }

    var find = $(".ebb-flex-area-button" + ".selected-pie").attr("id");
    var span = "monthly";
    if (find == "area-day") {
      span = "daily";
    } else if (find == "area-week") {
      span = "weekly";
    }

    var member_id = $('input[name="member_id"]').val();

    $.ajax({
      type: "POST",
      url: ajaxurl,
      data: {
        action: "ebb_ajax_pi",
        date: select_date,
        type: search_type,
        ret: ret,
        member_id: member_id
      },
      dataType: "json"
    })
      .done(function(data) {
        if (data.length > 0) {
          $("#ebb-pie-chart-notice").addClass("ebb-hidden");
          draw_pie_chart(data);
        } else {
          $("#ebb-pie-chart").empty();
          $("#ebb-pie-chart-notice").html("<p>データがありません。</p>");
          $("#ebb-pie-chart-notice").removeClass("ebb-hidden");
        }
      })
      .fail(function(XMLHttpRequest, textStatus, error) {
        console.log("失敗" + error);
        console.log(XMLHttpRequest.responseText);
      });

    ajax_area_chart(span, ret);
  }

  //ここからメイン処理
  $(document).ready(function() {
    var today = new Date();
    var year = today.getFullYear();
    var month = today.getMonth() + 1;
    var day = today.getDate();

    selected_month = month;

    day = ("0" + day).slice(-2);
    month = ("0" + month).slice(-2);

    var today_str = year + "年" + month + "月" + day + "日";
    var today_format = year + "-" + month + "-" + day;

    content = $("#user-content p.selected_date");
    var html = `${today_str}`;
    content.html(html);

    var member_id = $('input[name="member_id"]').val();

    ajax_fire(today_format, 0, member_id);
    ajax_fire(year + "-" + month, 1, member_id);

    var select_month = $("#monthAndYear").text();
    var select_date = str2thismonth(select_month);

    $("#pie-month").addClass("selected-pie");
    $("#pie-ret").addClass("selected-pie");

    ajax_pie_chart(select_date, 0, "ret");

    $("#area-day").addClass("selected-pie");
    ajax_area_chart("daily", "ret");

    $(document).on("click", "td.date-picker", function() {
      $(".today").removeClass("today");
      $(this).addClass("today");

      var td_now = $(this);
      year = td_now.data("year");
      month = td_now.data("month");
      day = td_now.data("date");

      var date = year + "年" + month + "月" + day + "日";

      content = $("#user-content p.selected_date");
      html = `${date}`;
      content.html(html);

      day = ("0" + day).slice(-2);
      month = ("0" + month).slice(-2);

      today_str = year + "年" + month + "月" + day + "日";
      today_format = year + "-" + month + "-" + day;

      var ret;
      var find = $(".ebb-flex-pie-button2" + ".selected-pie").attr("id");

      if (find == "pie-tot") {
        ret = "tot";
      } else if (find == "pie-inv") {
        ret = "inv";
      } else {
        ret = "ret";
      }
      var member_id = $('input[name="member_id"]').val();
      ajax_fire(today_format, 0, member_id);

      var find = $(".ebb-flex-pie-button" + ".selected-pie").attr("id");
      if (find == "pie-day") {
        ajax_pie_chart(today_format, 0, ret);
      }
    });

    $(document).on("click", "#previous", function() {
      var this_month = str2thismonth($("#monthAndYear").text());
      var member_id = $('input[name="member_id"]').val();
      ajax_fire(this_month, 1, member_id);
    });
    $(document).on("click", "#next", function() {
      var this_month = str2thismonth($("#monthAndYear").text());
      var member_id = $('input[name="member_id"]').val();
      ajax_fire(this_month, 1, member_id);
    });

    $(document).on("click", ".ebb-show-memo", function(e) {
      $(".ebb-memo-line").toggleClass("ebb-hidden");
    });

    $("#modal_ebb_add").iziModal({
      headerColor: "#333", //ヘッダー部分の色
      width: 340, //横幅
      overlayColor: "rgba(0, 0, 0, 0.5)", //モーダルの背景色
      fullscreen: true, //全画面表示
      transitionIn: "fadeInUp", //表示される時のアニメーション
      transitionOut: "fadeOutDown" //非表示になる時のアニメーション
    });

    $(document).on("click", ".ebb-add-button", function(e) {
      e.preventDefault();
      $("#modal_ebb_inner").removeClass("ebb-hidden");
      $("#modal_ebb_add").iziModal("open");
    });

    $("#modal_ebb_add").iziModal({
      headerColor: "#333", //ヘッダー部分の色
      width: 340, //横幅
      overlayColor: "rgba(0, 0, 0, 0.5)", //モーダルの背景色
      fullscreen: true, //全画面表示
      transitionIn: "fadeInUp", //表示される時のアニメーション
      transitionOut: "fadeOutDown" //非表示になる時のアニメーション
    });
    $(document).on("click", ".ebb-edit", function(e) {
      e.preventDefault();
      $("#modal_ebb_inner").removeClass("ebb-hidden");
      $("#modal_ebb_add").iziModal("open");

      var id = $(this).data("edit");

      var gen = $(`#genre_${id}`).data("value");
      var inv = $(`#inv_${id}`).data("value");
      var ret = $(`#ret_${id}`).data("value");
      var text = $(`#ebb-text_${id}`)
        .find("p.ebb-text")
        .text();

      $("#input-id").val(id);
      $("#input-invest").val(inv);
      $("#input-return").val(ret);
      $("#input-text").text(text);
      $("#ebb-genre-option").val(gen);
    });

    $(document).on("click", ".ebb-delete", function(e) {
      var id = $(this).data("delete");

      if (!confirm("削除しても良いですか？")) {
        return false;
      }
      $.ajax({
        type: "POST",
        url: ajaxurl,
        data: { action: "ebb_ajax_rm", id: id },
        dataType: "json"
      })
        .done(function(data) {
          if (data > 0) {
            var today_format = get_today_format();
            var member_id = $('input[name="member_id"]').val();
            ajax_fire(today_format, 0, member_id);
            ajax_fire(today_format, 1, member_id);
          } else {
            console.log("DB更新処理に失敗しました。");
          }
        })
        .fail(function(XMLHttpRequest, textStatus, error) {
          console.log("失敗" + error);
          console.log(XMLHttpRequest.responseText);
        });
    });

    $(document).on("click", "#ebb-input-submit", function(e) {
      var inv = $("#input-invest").val();
      var ret = $("#input-return").val();
      var text = $("#input-text").val();

      var gen = $("[name=select-genre] option:selected").val();
      var date = $("#user-content p.selected_date").text();

      if (!isNumber(inv) || !inv) {
        $("#input-invest").val("整数を入力してください");
        return false;
      }
      if (!isNumber(ret) || !ret) {
        $("#input-return").val("整数を入力してください");
        return false;
      }

      if (text.length > 128) {
        alert(text.length + "文字が入力されています（128文字までです）。");
      }

      var date = get_today_format();
      ajax_insert(gen, inv, ret, text, date);

      $("#modal_ebb_add").iziModal("close");
    });

    /* chart */

    $(document).on("click", ".ebb-flex-pie-button", function(e) {
      var target = $(this);
      $(".ebb-flex-pie-button").removeClass("selected-pie");
      target.addClass("selected-pie");
      prepare_area_chart();
    });

    $(document).on("click", ".ebb-flex-pie-button2", function(e) {
      var target = $(this);
      $(".ebb-flex-pie-button2").removeClass("selected-pie");
      target.addClass("selected-pie");
      prepare_area_chart();
    });

    $(document).on("click", ".ebb-flex-area-button", function(e) {
      var target = $(this);
      $(".ebb-flex-area-button").removeClass("selected-pie");
      target.addClass("selected-pie");

      var find = $(".ebb-flex-area-button" + ".selected-pie").attr("id");
      if (find == "area-day") {
        span = "daily";
      } else if (find == "area-week") {
        span = "weekly";
      } else {
        span = "monthly";
      }

      var find = $(".ebb-flex-pie-button2" + ".selected-pie").attr("id");

      var type = "tot";
      if (find == "pie-ret") {
        type = "ret";
      } else if (find == "pie-inv") {
        type = "inv";
      }
      ajax_area_chart(span, type);
    });
  });

  $(document).on("click", ".ebb-show-user-disabled", function(e) {
    alert("操作できません");
  });

  $(document).on("click", ".ebb-show-user", function(e) {
    var target = $(this);
    var flag;
    if (target.hasClass("selected-pie")) {
      flag = 1;
    } else {
      flag = 0;
    }
    var member_id = $('input[name="member_id"]').val();

    $.ajax({
      type: "POST",
      url: ajaxurl,
      data: {
        action: "ebb_ajax_ud",
        flag: flag,
        member_id: member_id
      },
      dataType: "json"
    })
      .done(function(data) {
        if (data) {
          if (flag) {
            target.removeClass("selected-pie");
            target.text("非公開中");
          } else {
            target.addClass("selected-pie");
            target.text("公開中");
          }
        }
      })
      .fail(function(XMLHttpRequest, textStatus, error) {
        console.log("失敗" + error);
        console.log(XMLHttpRequest.responseText);
      });
  });
})(jQuery);
