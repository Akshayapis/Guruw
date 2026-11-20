<?php
session_start();
require_once('system/function.php');
$message = '';
$login_id = trim(isset($_POST['login_id']) ? $_POST['login_id'] : '');
if (isset($_POST['login'])) {
    $password = isset($_POST['login_pwd']) ? $_POST['login_pwd'] : '';
    if ($login_id === '' || $password === '') {
        $message = 'Enter your login ID and password.';
    } else {
        $id = cashpe_auth_escape($login_id);
        $res = sql_query("SELECT * FROM `tb_partner` WHERE login_id='".$id."' AND status='active' OR email='".$id."' AND status='active' OR mobile='".$id."' AND status='active'");
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
            $message = 'Invalid credentials or inactive account. Check your login ID and password.';
        }
    }
} elseif (isset($_POST['pwd_reset'])) {
    // Never reveal or change a password to an unauthenticated visitor.
    // Mail must be configured for recovery; otherwise ask the administrator.
    $message = 'Password recovery requires a configured mail service. Please contact your administrator to reset your password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#059669">
  <title><?php echo htmlspecialchars(webdata('company_name')); ?> | Partner Login</title>
  <link rel="icon" href="images/cashpe-icon.svg" type="image/svg+xml">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/dist/css/adminx.css">
  <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="assets/css/cashpe.css?v=20261120r3">
  <script src="assets/dist/js/sweetalert.min.js"></script>
</head>
<body>
<div class="cp-auth">
  <div class="cp-auth-art">
    <a class="cp-brand" href="index.php" style="color:#fff"><img src="images/cashpe-logo-inverse.svg" alt="Cashpe"></a>
    <div>
      <span class="cp-pill" style="background:rgba(167,243,208,.18);color:#d1fae5">PARTNER PLATFORM</span>
      <h2 style="margin-top:20px">Your payments,<br>beautifully under control.</h2>
      <p>Track every collection, settle to your bank and manage all your UPI accounts from one clean dashboard.</p>
    </div>
    <p style="font-size:12px;color:#6ee7b7">🔒 Secure password access · 24×7 payment status</p>
  </div>
  <div class="cp-auth-panel">
    <div class="cp-auth-card">
      <div class="cp-mobile-brand"><img src="images/cashpe-logo.svg" alt="Cashpe"></div>
      <span class="cp-auth-kicker">CASHPE PARTNER</span>
      <h2>Welcome back 👋</h2>
      <p class="muted">Log in to your <?php echo htmlspecialchars(webdata('company_name')); ?> partner account.</p>
      <?php if ($message): ?><div class="cp-auth-notice" role="alert"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
      <form action="Login" method="post">
        <label for="login_id">Mobile / Email / Username</label>
        <input type="text" id="login_id" class="form-control" placeholder="Enter mobile, email or username" name="login_id" value="<?php echo htmlspecialchars($login_id, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="username" required>
        <label for="login_pwd">Password</label>
        <input type="password" id="login_pwd" class="form-control" placeholder="Enter your password" name="login_pwd" autocomplete="current-password" required>
        <div class="form-check mb-3" style="margin-top:14px">
          <input type="checkbox" class="form-check-input" id="terms" required>
          <label class="form-check-label" for="terms">I agree to the terms of service</label>
        </div>
        <button type="submit" name="login" class="cp-btn"><i class="fas fa-sign-in-alt"></i> Log in securely</button>
        <div class="cp-auth-links">
          <button type="submit" name="pwd_reset" formnovalidate class="btn btn-link p-0" style="font-size:12px">Forgot password?</button>
          <a href="CreateAccount" style="font-size:12px;font-weight:700">Create an account →</a>
        </div>
      </form>
      <div style="margin-top:22px;text-align:center"><a href="index.php" style="font-size:12px;color:#94a3b8">← Back to home</a></div>
    </div>
  </div>
</div>
<script src="js/jquery.min.js"></script>
</body>
</html>
