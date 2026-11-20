<?php
require_once('system/function.php');
$cpCompany = webdata('company_name');
$cpTag = webdata('web_tag');
$cpMobile = support('mobile');
$cpEmail = support('email');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#059669">
  <meta name="description" content="<?php echo htmlspecialchars($cpTag); ?> — collect UPI payments directly to your bank account with instant settlement.">
  <title><?php echo htmlspecialchars($cpTag ?: $cpCompany); ?></title>
  <link rel="icon" href="images/cashpe-icon.svg" type="image/svg+xml">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/dist/css/adminx.css">
  <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="assets/css/cashpe.css?v=20261120r3">
  <script src="assets/dist/js/sweetalert.min.js"></script>
</head>
<body class="cp-site">
  <header class="cp-nav">
    <div class="cp-nav-inner">
      <a class="cp-brand" href="index.php"><img src="images/cashpe-logo.svg" alt="Cashpe"></a>
      <nav class="cp-links">
        <a href="index.php">Home</a>
        <a href="index.php#Payments">Payments</a>
        <a href="index.php#Pricing">Pricing</a>
        <a href="Developer">Developers</a>
        <a href="trial/index.php">Live Demo</a>
      </nav>
      <div class="cp-links" style="margin-left:0">
        <?php if ($cpMobile) { ?>
          <a href="https://wa.me/91<?php echo preg_replace('/[^0-9]/', '', $cpMobile); ?>" title="Chat on WhatsApp"><i class="fab fa-whatsapp fa-lg" style="color:#25d366"></i></a>
        <?php } ?>
        <a href="Login" class="cp-btn ghost small">Log in</a>
        <a href="CreateAccount" class="cp-btn small">Get started <i class="fas fa-arrow-right"></i></a>
      </div>
    </div>
  </header>
