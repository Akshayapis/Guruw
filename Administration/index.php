<?php
session_start();
require_once('../system/function.php');
$message = '';
if (isset($_POST['login'])) {
    $login_id = trim(isset($_POST['login_id']) ? $_POST['login_id'] : '');
    $password = isset($_POST['login_pwd']) ? $_POST['login_pwd'] : '';
    if ($login_id === '' || $password === '') {
        $message = 'Enter your administrator username and password.';
    } else {
        $id = cashpe_auth_escape($login_id);
        $res = sql_query("SELECT * FROM `tb_partner` WHERE login_id='".$id."' AND type='1' AND status='active'");
        if ($res === false) {
            $message = 'Database connection failed. Please check the server configuration.';
        } else {
            $user = sql_fetch_array($res);
            if ($user && isset($user['id'], $user['login_pwd']) && pwd_verify($password, $user['login_pwd'])) {
                session_regenerate_id(true);
                $_SESSION['is_login'] = 'YES';
                $_SESSION['uid'] = $user['id'];
                header('Location: Dashboard');
                exit;
            }
            $message = 'Invalid administrator credentials or inactive account.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#059669">
  <title>Cashpe | Admin Login</title>
  <link rel="icon" href="../images/cashpe-icon.svg" type="image/svg+xml">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/dist/css/adminx.css">
  <link rel="stylesheet" href="../assets/vendor/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/cashpe.css?v=20261120r3">
  <script src="../assets/dist/js/sweetalert.min.js"></script>
</head>
<body>
<div class="cp-auth">
  <div class="cp-auth-art">
    <a class="cp-brand" href="../index.php" style="color:#fff"><img src="../images/cashpe-logo-inverse.svg" alt="Cashpe"></a>
    <div>
      <span class="cp-pill" style="background:rgba(167,243,208,.18);color:#d1fae5">ADMIN CONSOLE</span>
      <h2 style="margin-top:20px">Complete control<br>of your payments.</h2>
      <p>Manage merchants, plans, UPI accounts and settlements from one secure console.</p>
    </div>
    <p style="font-size:12px;color:#6ee7b7">🔐 Password-protected administrator access</p>
  </div>
  <div class="cp-auth-panel">
    <div class="cp-auth-card">
      <div class="cp-mobile-brand"><img src="../images/cashpe-logo.svg" alt="Cashpe"></div>
      <span class="cp-auth-kicker">ADMIN CONSOLE</span>
      <h2>Admin login 🛡️</h2>
      <p class="muted">Sign in to the Cashpe administration console.</p>
      <?php if ($message): ?><div class="cp-auth-notice" role="alert"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
      <form action="" method="post">
        <label for="login_id">Administrator username</label>
        <input type="text" id="login_id" class="form-control" placeholder="Enter admin username" name="login_id" autocomplete="username" required>
        <label for="login_pwd">Password</label>
        <input type="password" id="login_pwd" class="form-control" placeholder="Enter password" name="login_pwd" autocomplete="current-password" required>
        <div class="form-check mb-3" style="margin-top:14px">
          <input type="checkbox" class="form-check-input" id="terms" required>
          <label class="form-check-label" for="terms">I am an authorised administrator</label>
        </div>
        <button type="submit" name="login" class="cp-btn"><i class="fas fa-shield-alt"></i> Enter admin console</button>
        <div class="cp-auth-links" style="justify-content:center;margin-top:20px">
          <a href="../Login" style="font-size:12px">Partner login →</a>
        </div>
      </form>
    </div>
  </div>
</div>
<script src="../js/jquery.min.js"></script>
</body>
</html>
