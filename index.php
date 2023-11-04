<?php
  session_start(); //セッション使用の宣言
  $mode = 'input';
  $errmessage = array();
  if( isset($_POST['back']) && $_POST['back'] ){
    // 何もしない
  }
  // 確認ボタンが押された時の処理 
  else if( isset($_POST['confirm']) && $_POST['confirm'] ){
    if( !$_POST['fullname'] ){
      $errmessage[] ="名前を入力してください";
    } else if( mb_strlen($_POST['fullname']) > 100 ){
      $errmessage[] = "名前は100文字以内にしてください";
    }
    $_SESSION['fullname'] = htmlspecialchars($_POST['fullname'], ENT_QUOTES);
    
    if( !$_POST['email'] ){
      $errmessage[] ="Eメールを入力してください";
    } else if( mb_strlen($_POST['email']) > 200 ){
      $errmessage[] = "Eメールは200文字以内にしてください";
    } else if( !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ){
      $errmessage[] = "メールアドレスが不正です";
    }
    $_SESSION['email'] = htmlspecialchars($_POST['email'], ENT_QUOTES);


    if( !$_POST['message'] ){
      $errmessage[] ="お問い合わせ内容を入力してください";
    } else if( mb_strlen($_POST['message']) > 500 ){
      $errmessage[] = "お問い合わせ内容は500文字以内にしてください";
    }
    $_SESSION['message'] = htmlspecialchars($_POST['message'], ENT_QUOTES);

    if( $errmessage ){
      $mode = 'input';
    } else {
      $token = bin2hex(mcrypt_create_iv(32, MCRYPT_DEV_URANDOM));
      $_SESSION['token'] = $token;
      $mode = 'confirm';
    }
  }
  // 送信ボタンが押された時の処理
  else if( isset($_POST['send']) && $_POST['send'] ){
    // 入力されるべき値がなかったとき
    if( !$_POST['token'] || !$_SESSION['token'] || !$_SESSION['email']){
      $errmessage[] = '不正な処理が行われました';
      $_SESSION     = array();
      $mode         = 'input';
    }
    // 条件をクリアしても、トークンが一致しないとき（CSRF対策）
    else if( $_POST['token'] != $_SESSION['token']){
      $errmessage[] = '不正な処理が行われました';
      $_SESSION     = array();
      $mode         = 'input';
    } else{
      $message = "お問い合わせを受け付けました。\r\n"
              . "名前：" . $_SESSION['fullname'] . "\r\n"
              . "email：" . $_SESSION['email'] . "\r\n"
              . "お問い合わせ内容：\r\n"
              . preg_replace("/\r\n|\r|\n/", "\r\n", $_SESSION['message']);
              
      mail($_SESSION['email'], "お問い合わせありがとうございます", $message); // 送信者へメール
      mail("solluna_1119@yahoo.co.jp", "お問い合わせありがとうございます", $message); // 管理者へメール
      $_SESSION = array(); // セッションを初期化
      $mode = 'send';
    }
  }
  // 初回表示
  else {
    // 初回（GET）は、何も表示しないので、セッションを初期化する。
    $_SESSION['fullname'] = "";
    $_SESSION['email']    = "";
    $_SESSION['message']  = "";
  }
?>


<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <title>お問い合わせフォーム</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">
    <style>
    body {
        padding: 10px;
        margin: 0 auto;
        max-width: 600px;
    }

    div.button {
        text-align: center;
    }
    </style>
</head>

<body>
    <!-- 入力画面 -->
    <?php if( $mode == 'input' ){ ?>
    <?php if( $errmessage ){
      echo '<div class="alert alert-danger" role="alert">';
      echo implode('<br>', $errmessage);
      echo '</div>';
    }
    ?>

    <form action="./contactform.php" method="post">
        名前 <input type="text" name="fullname" value="<?php echo $_SESSION['fullname'] ?>" class="form-control"><br>
        Eメール <input type="email" name="email" value="<?php echo $_SESSION['email'] ?>" class="form-control"><br>
        お問い合わせ内容<br>
        <textarea cols="40" rows="8" name="message"
            class="form-control"><?php echo $_SESSION['message'] ?></textarea><br>
        <div class="button">
            <input type="submit" name="confirm" value="確認" class="btn btn-primary btn-lg">
        </div>
    </form>

    <!-- 確認画面 -->
    <?php } else if( $mode == 'confirm' ){ ?>
    <form action="./contactform.php" method="post">
        <input type="hidden" name="token" value="<?php echo $_SESSION['token']; ?>">
        名前 <?php echo $_SESSION['fullname'] ?><br>
        Eメール <?php echo $_SESSION['email'] ?><br>
        お問い合わせ内容<br>
        <?php echo nl2br($_SESSION['message']) ?><br>
        <input type="submit" name="back" value="戻る" class="btn btn-primary btn-lg">
        <input type="submit" name="send" value="送信" class="btn btn-primary btn-lg">
    </form>

    <!-- 完了画面 -->
    <?php } else { ?>
    送信しました。お問い合わせありがとうございました。
    <?php } ?>
</body>

</html>