<!-- Cashpe footer -->
<footer class="cp-footer" id="About">
  <div class="cp-wrap">
    <div class="cp-footer-grid">
      <div>
        <a class="cp-brand" href="index.php" style="color:#fff"><img src="images/cashpe-logo.svg" alt="Cashpe"></a>
        <p style="margin-top:14px">Accept UPI payments directly into your bank account. Smart collection, real-time status checks, instant settlement — all from one dashboard.</p>
      </div>
      <div>
        <h5>Platform</h5>
        <a href="index.php#Payments">Payments</a>
        <a href="index.php#Pricing">Pricing</a>
        <a href="Developer">Developer API</a>
        <a href="trial/index.php">Live Demo</a>
      </div>
      <div>
        <h5>Support</h5>
        <?php if ($cpMobile) { ?><a href="tel:+91<?php echo preg_replace('/[^0-9]/', '', $cpMobile); ?>">+91 <?php echo htmlspecialchars($cpMobile); ?></a><?php } ?>
        <?php if ($cpEmail) { ?><a href="mailto:<?php echo htmlspecialchars($cpEmail); ?>"><?php echo htmlspecialchars($cpEmail); ?></a><?php } ?>
        <a href="Login">Partner Login</a>
        <a href="CreateAccount">Create Account</a>
      </div>
      <div>
        <h5>Company</h5>
        <p><?php echo htmlspecialchars(webdata('company_about')); ?></p>
        <a href="docs/ahkweb-terms_V2.docx">Terms of Service</a>
      </div>
    </div>
    <div class="cp-bottom"><?php echo webdata('web_rights'); ?></div>
  </div>
</footer>
<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>
<?php echo webdata('live_chat'); ?>
</body>
</html>
