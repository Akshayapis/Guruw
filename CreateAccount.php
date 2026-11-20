<?php
session_start();
require_once('system/function.php');
$message = '';
$name = trim(isset($_POST['name']) ? $_POST['name'] : '');
$company_name = trim(isset($_POST['company_name']) ? $_POST['company_name'] : '');
$mobile = trim(isset($_POST['mobile']) ? $_POST['mobile'] : '');
$email = trim(isset($_POST['email']) ? $_POST['email'] : '');
if (isset($_POST['create_account'])) {
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    if ($name === '' || $company_name === '' || !preg_match('/^[0-9]{10}$/', $mobile) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
        $message = 'Enter your name, business, valid mobile and email, and a password of at least 6 characters.';
    } else {
        $m = cashpe_auth_escape($mobile);
        $e = cashpe_auth_escape($email);
        $res = sql_query("SELECT * FROM `tb_partner` WHERE login_id='".$m."' OR mobile='".$m."' OR email='".$e."'");
        if ($res === false) {
            $message = 'Database connection failed. Please check the server configuration.';
        } elseif (sql_num_rows($res) > 0) {
            $message = 'This mobile number or email is already registered. Please log in.';
        } else {
            $n = cashpe_auth_escape(strip_tags($name));
            $c = cashpe_auth_escape(strip_tags($company_name));
            $hash = cashpe_auth_escape(pwd_hash($password));
            $token = bin2hex(random_bytes(16));
            $secret = bin2hex(random_bytes(16));
            $query = sql_query("INSERT INTO tb_partner (`login_id`, `login_pwd`, `name`, `company_name`, `mobile`, `email`, `pan_no`, `uid_no`, `status`, `type`, `balance`, `token`, `secret`, `slab`, `trial_done`, `expire_date`, `start_date`, `settle_account`, `upi_active`, `paytm_active`) VALUES ('".$m."','".$hash."','".$n."','".$c."','".$m."','".$e."','','','active','0','0','".$token."','".$secret."','3','0','01-01-2000 00:00:00','','{}','0','0')");
            if ($query && sql_insert_id()) {
                session_regenerate_id(true);
                $_SESSION['is_login'] = 'YES';
                $_SESSION['uid'] = sql_insert_id();
                header('Location: Dashboard');
                exit;
            }
            $message = 'Account could not be created. Please check the database connection and try again.';
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
  <title><?php echo htmlspecialchars(webdata('company_name')); ?> | Create Account</title>
  <link rel="icon" href="images/cashpe-icon.svg" type="image/svg+xml">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/dist/css/adminx.css">
  <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="assets/css/cashpe.css?v=20261120r5">
  <script src="assets/dist/js/sweetalert.min.js"></script>
</head>
<body>
<div class="cp-auth">
  <div class="cp-auth-art">
    <a class="cp-brand" href="index.php" style="color:#fff"><img src="images/cashpe-logo-inverse.svg" alt="Cashpe"></a>
    <div>
      <span class="cp-pill" style="background:rgba(167,243,208,.18);color:#d1fae5">FREE TO START</span>
      <h2 style="margin-top:20px">Start accepting UPI<br>payments today.</h2>
      <p>Create your partner account, configure your collection UPI ID and go live in minutes.</p>
    </div>
    <p style="font-size:12px;color:#6ee7b7">⚡ Zero setup fee · Instant activation</p>
  </div>
  <div class="cp-auth-panel">
    <div class="cp-auth-card" style="max-width:520px">
      <div class="cp-mobile-brand"><img src="images/cashpe-logo.svg" alt="Cashpe"></div>
      <span class="cp-auth-kicker">JOIN CASHPE</span>
      <h2>Create your account</h2>
      <p class="muted">Register with your mobile number — quick and paperless.</p>
      <?php if ($message): ?><div class="cp-auth-notice" role="alert"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
      <form action="" method="post">
        <label for="name">Your name</label><input type="text" class="form-control" id="name" maxlength="50" name="name" value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Full name" required>
        <label for="company_name">Business name</label><input type="text" class="form-control" id="company_name" maxlength="50" name="company_name" value="<?php echo htmlspecialchars($company_name, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Your business" required>
        <label for="mobile">Mobile number</label><input type="text" class="form-control" id="mobile" minlength="10" maxlength="10" name="mobile" value="<?php echo htmlspecialchars($mobile, ENT_QUOTES, 'UTF-8'); ?>" onkeypress="return isNumber(event)" placeholder="10-digit mobile" required>
        <label for="email">Email address</label><input type="email" class="form-control" id="email" maxlength="80" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" placeholder="you@business.com" required>
        <label for="password">Create password</label><input type="password" class="form-control" id="password" minlength="6" name="password" placeholder="Minimum 6 characters" required>
        <div class="form-check mb-3" style="margin-top:14px">
          <input type="checkbox" class="form-check-input" id="accept" required>
          <label class="form-check-label" for="accept">I accept the <a href="docs/ahkweb-terms_V2.docx">terms &amp; conditions</a></label>
        </div>
        <button type="submit" name="create_account" class="cp-btn"><i class="fas fa-check-circle"></i> Create account</button>
        <div class="cp-auth-links" style="justify-content:center"><a href="Login" style="font-size:12px">Already registered? Log in →</a></div>
      </form>
    </div>
  </div>
</div>
<script src="js/jquery.min.js"></script>
<script src="assets/dist/js/custom-new.js"></script>
</body>
</html>
