<?php

//SESSIONスタート
session_start();
$userid = $_SESSION['userid'];
$username = $_SESSION['username'];
$gen_name = $_SESSION['gen_name'];

$pill_num = "No.1";

// var_dump($gen_name);
require_once('funcs.php');
//ログインチェック
// loginCheck();
$pdo = db_conn();

// 現場経歴一覧取得_emp_idで抽出
// header("Refresh:5");
// echo date('H:i:s Y-m-d');



// １．杭番号情報抽出
$stmt = $pdo->prepare("select * from design where pill_num = :pill_num");
$stmt->bindValue(':pill_num', $pill_num, PDO::PARAM_STR);
$status = $stmt->execute();
foreach ($stmt as $row) {
  $pill_sign =$row['pill_sign'];
  $virtilength =$row['virtilength'];
  $stcore_numX =$row['stcore_numX'];
  $stcore_numY =$row['stcore_numY'];
}

var_dump($pill_sign);



// ５．ボタン記述
$stmt = $pdo->prepare("select * from pillvirtispec where gen_name = :gen_name and pill_sign = :pill_sign");
$stmt->bindValue(':gen_name', $gen_name, PDO::PARAM_STR);
$stmt->bindValue(':pill_sign', $pill_sign, PDO::PARAM_STR);

$status = $stmt->execute();

$button = "";

if($status==false) {
  //execute（SQL実行時にエラーがある場合）
  $error = $stmt->errorInfo();
  exit("ErrorQuery:".$error[2]);
}
else{
//Selectデータの数だけ自動でループしてくれる
//FETCH_ASSOC=http://php.net/manual/ja/pdostatement.fetch.php
  while( $result = $stmt->fetch(PDO::FETCH_ASSOC)){ 
    $button .='<form name="form'.$result["id"].'" action="time_act.php" method="post" style="font-size:14px;width:800px;">';
    $button .='<div style="display: flex; justify-content:flex-start;margin:5px;">';
    $button .='<p style="font-size:20px;margin:10px;width:150px;">';
    $button .=$result['floor_num'];
    $button .='打設完了：</p>';
    $button .='<input type="hidden" name="gen_name" value="'.$gen_name.'"/>';
    $button .='<input type="hidden" name="pill_num" value="'.$pill_num.'"/>';
    $button .='<input type="hidden" name="floor_num" value="'.$result["floor_num"].'"/>';
    $button .=' <input style="margin:10px;" type="submit" value="'.$result['floor_num'].'打設完了"/>';
    $button .='</div></form>';
// onclick="disabled = true;"1クリックで押せなくなる
  }
}


// ６．グラフ表示
$stmt = $pdo->prepare("select * from speed where gen_name = :gen_name and pill_num = :pill_num");
$stmt->bindValue(':gen_name', $gen_name, PDO::PARAM_STR);
$stmt->bindValue(':pill_num', $pill_num, PDO::PARAM_STR);

$status = $stmt->execute();

$kaidaka = "";
$rgtime = "";


$rgtime3 = "";

if($status==false) {
  //execute（SQL実行時にエラーがある場合）
  $error = $stmt->errorInfo();
  exit("ErrorQuery:".$error[2]);
}
else{
//Selectデータの数だけ自動でループしてくれる
//FETCH_ASSOC=http://php.net/manual/ja/pdostatement.fetch.php
  while( $result = $stmt->fetch(PDO::FETCH_ASSOC)){ 
    $kaidaka .=$result["floor_num"].',';
 

    $rgtime2 = $result["rgtime"] -$rgtime3;
    $rgtime3 = $rgtime2;

    $rgtime .=$result["rgtime"].',';
  }
}



var_dump($rgtime2);








?>




<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width">
<link rel="stylesheet" href="css/main.css" />
<link href="css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.1.4/Chart.min.js"></script>
<style>div{padding: 10px;font-size:16px;}</style>
<title>打上高さ管理</title>
</head>
<body>

<header style="position: fixed;width:100%;z-index: 9999;">
  <nav class="navbar navbar-default" style="background:linear-gradient(to bottom, #8EA9DB 70%, #D9EAFF 100%); border-color:#305496;font-size:15px;display:flex;justify-content:space-between;">
    <div class="container-fluid"><p style="font-size: 20px;color:#E2EFDA;font-weight: bold;">CFTコンクリート施工管理システム</p></div>
    <div>
      <a class="navbar-brand" href="genba.php">現場選択</a>
      <p>LoginID:<?= $userid ?></p>
  </div>
    </div>
  </nav>
</header>



<p style="height:100px;"></p>

<p style="font-size:20px;">現場名：<?= $gen_name ?></p>
<h3>打上り高さ管理</h3>
<div style="display: flex; justify-content:space-around;margin:5px;width:300px;">
<p style="font-size:16px; margin:3px;">杭番号：<?= $pill_num ?></p>
<p style="font-size:16px; margin:3px;">杭符号：<?= $pill_sign ?></p>
</div>
<div style="display: flex; justify-content:space-around;margin:5px;width:300px;">
<p style="font-size:16px; margin:3px;">位置：<?=  $stcore_numX ?> － <?=  $stcore_numY ?></p>
<p style="font-size:16px; margin:3px;">柱長：<?= $virtilength ?></p>
</div>

<form name="form1" action="time_act.php" method="post" style="font-size:14px;width:800px;">
<div style="display: flex; justify-content:flex-start;margin:5px;">
 <p style="font-size:20px;margin:10px;width:150px;">打設開始：</p>
 <input type="hidden" name="gen_name" value="<?= $gen_name ?>"/>
 <input type="hidden" name="pill_num" value="<?= $pill_num ?>"/>
 <input type="hidden" name="floor_num" value="打設開始"/>
 <input style="margin:10px;" type="submit" value="打設　開始" />
 </div>
</form>

 <?= $button ?>

<!-- 折れ線グラフ -->
<div style="width:500px;" >
    <canvas id="chart"></canvas>
   </div>

<!-- グラフ表示位置変更 -->
<script type="text/javascript">
      var canvas;
      var ctx;

      function init() {
          canvas = document.getElementById("chart");
          canvas.style.position = "absolute";
          canvas.style.right = "80px";
          canvas.style.top = "260px";
          ctx = canvas.getContext("2d");
          
          draw();
      }

      function draw() {
          ctx.style = "#000000";
          ctx.rect( 0, 0, 100, 100 );
          ctx.stroke();
      }

      window.onload = function() {
          init();
      };
</script>

<!-- グラフ表示設定 -->
<script>
        var ctx = document.getElementById("chart");
        var myLineChart = new Chart(ctx, {
          // グラフの種類：折れ線グラフを指定
          type: 'line',
          data: {
            // x軸の各メモリ
            labels: [],
            datasets: [
              {
                label: '打上り完了時間',
                data: [],
                borderColor: "#ea2260",
                lineTension: 0, //<===追加
                fill: true, 
                backgroundColor: "#00000000"
                          
              },
            ],
          },
          options: {
            title: {
              display: true,
              text: '打上り高さ管理'
            },
            scales: {
              yAxes: [{
                        // type: 'time',
                        // distribution: 'series'
                        ticks: {
                          suggestedMax: 100,
                          suggestedMin: 50,
                          stepSize: 10,  // 縦メモリのステップ数
                          callback: function(value, index, values){

                    return  value +  'sec'  // 各メモリのステップごとの表記（valueは各ステップの値）
                  }
                }
              }]
            },
          }
        });
  </script>




</body>
</html>