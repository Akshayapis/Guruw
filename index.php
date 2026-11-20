<?php
require('inc/head.php');
$cpSlab = sql_fetch_array(sql_query("SELECT * FROM `tb_slab` WHERE id='".webdata('slab_id')."' "));
$cpUpiFee = isset($cpSlab['upi']) ? $cpSlab['upi'] : '0.50';
$cpVanFee = isset($cpSlab['van']) ? $cpSlab['van'] : '0.50';
?>
<section class="cp-hero">
  <div class="cp-hero-grid">
    <div>
      <span class="cp-eyebrow">💚 UPI payments, simplified</span>
      <h1>Collect payments.<br><em>Get paid instantly.</em></h1>
      <p class="cp-lead">A powerful UPI payment gateway for modern businesses. Accept payments through PhonePe, Google Pay, Paytm and every UPI app — straight into your bank account, with zero setup hassle.</p>
      <div class="cp-hero-actions">
        <a href="CreateAccount" class="cp-btn">Start for free <i class="fas fa-arrow-right"></i></a>
        <a href="Developer" class="cp-btn ghost"><i class="fas fa-code"></i> View API docs</a>
      </div>
      <div class="cp-trust">
        <div><strong>0%</strong><span>Setup charges</span></div>
        <div><strong>24×7</strong><span>Status checking</span></div>
        <div><strong>Instant</strong><span>Bank settlement</span></div>
      </div>
    </div>
    <div class="cp-hero-card">
      <div class="mock-top"><span>LIVE COLLECTION</span><span class="cp-pill"><i class="fas fa-circle" style="font-size:7px"></i> Online</span></div>
      <div class="mock-amount">₹2,499<span style="font-size:17px;color:#94a3b8">.00</span></div>
      <div style="color:#64748b;font-size:12px">Payment received · UPI</div>
      <div class="mock-qr"><img src="images/cashpe-icon.svg" alt="UPI QR"></div>
      <div style="display:flex;justify-content:space-between;align-items:center">
        <span class="cp-pill"><i class="fas fa-check-circle"></i> Settled to bank</span>
        <strong style="color:#065f46;font-size:13px">Ref #CS98341</strong>
      </div>
    </div>
  </div>
</section>

<section class="cp-section" id="Payments">
  <div class="cp-wrap">
    <div class="cp-center">
      <span class="cp-kicker">Everything you need</span>
      <h2>One dashboard. Every payment.</h2>
      <p class="cp-sub">From your first collect request to high-volume settlement, Cashpe keeps your payment flow simple, fast and always in sync.</p>
    </div>
    <div class="cp-grid">
      <div class="cp-feature"><div class="ico"><i class="fas fa-qrcode"></i></div><h3>UPI QR &amp; deep links</h3><p>Generate collect requests, QR codes and app links that work with every UPI application on Android and iOS.</p></div>
      <div class="cp-feature"><div class="ico"><i class="fas fa-sync-alt"></i></div><h3>Real-time status</h3><p>Automatic payment verification with UPI and Paytm status checks. Know the moment a customer pays.</p></div>
      <div class="cp-feature"><div class="ico"><i class="fas fa-university"></i></div><h3>Fast settlement</h3><p>Move settled funds to your bank account from a single screen with a complete, auditable statement.</p></div>
      <div class="cp-feature"><div class="ico"><i class="fas fa-shield-alt"></i></div><h3>Secure by design</h3><p>Token–secret API authentication, signed checksums and OTP-protected partner access on every request.</p></div>
      <div class="cp-feature"><div class="ico"><i class="fas fa-plug"></i></div><h3>Developer friendly</h3><p>Simple POST endpoints for payments, status and callbacks. Copy-paste integration in minutes.</p></div>
      <div class="cp-feature"><div class="ico"><i class="fas fa-chart-line"></i></div><h3>Complete reporting</h3><p>Filter, search and export every transaction. Admin controls for merchants, plans and pricing slabs.</p></div>
    </div>
  </div>
</section>

<section class="cp-section cp-dark">
  <div class="cp-wrap">
    <div class="cp-center">
      <span class="cp-kicker" style="color:#6ee7b7">How it works</span>
      <h2>Live in four simple steps</h2>
      <p class="cp-sub">No paperwork, no gateway approval wait. Start accepting payments today.</p>
    </div>
    <div class="cp-steps">
      <div class="cp-step"><h4>Create account</h4><p>Register with your business details in under two minutes.</p></div>
      <div class="cp-step"><h4>Add your UPI ID</h4><p>Configure your collections and settlement bank account.</p></div>
      <div class="cp-step"><h4>Integrate or share</h4><p>Use the payment API, a QR code or a simple payment link.</p></div>
      <div class="cp-step"><h4>Get paid</h4><p>Status updates automatically; settle directly to your bank.</p></div>
    </div>
  </div>
</section>

<section class="cp-section" id="Pricing">
  <div class="cp-wrap">
    <div class="cp-center">
      <span class="cp-kicker">Transparent pricing</span>
      <h2>Plans that grow with you</h2>
      <p class="cp-sub">Start free. Upgrade only when you are ready. No hidden charges.</p>
    </div>
    <div class="cp-price-grid">
      <div class="cp-price">
        <h3>Free trial</h3><div class="amount">₹0</div>
        <p style="color:#64748b;font-size:13px">For 3 days · everything unlocked</p>
        <ul><li>0 transaction fee</li><li>Unlimited transactions</li><li>UPI QR &amp; deep links</li><li>Full dashboard access</li></ul>
        <a href="CreateAccount" class="cp-btn ghost" style="width:100%">Start free trial</a>
      </div>
      <div class="cp-price featured">
        <span class="tag">MOST POPULAR</span>
        <h3>Growth</h3><div class="amount">₹499<span style="font-size:15px;color:#94a3b8">/mo</span></div>
        <p style="color:#64748b;font-size:13px">For serious businesses</p>
        <ul><li>UPI collect @ <?php echo htmlspecialchars($cpUpiFee); ?>%</li><li>Virtual account @ <?php echo htmlspecialchars($cpVanFee); ?>%</li><li>Instant settlement</li><li>Developer API &amp; webhooks</li><li>Priority support</li></ul>
        <a href="Login" class="cp-btn" style="width:100%">Choose Growth</a>
      </div>
      <div class="cp-price">
        <h3>Enterprise</h3><div class="amount">Custom</div>
        <p style="color:#64748b;font-size:13px">For high-volume teams</p>
        <ul><li>Custom MDR slabs</li><li>Dedicated UPI accounts</li><li>Bulk settlement</li><li>Account manager</li></ul>
        <a href="Login" class="cp-btn ghost" style="width:100%">Talk to us</a>
      </div>
    </div>
  </div>
</section>

<section class="cp-section cp-dark" style="padding:64px 0">
  <div class="cp-wrap cp-center">
    <h2 style="color:#fff">Ready to accept your first payment?</h2>
    <p class="cp-sub">Join businesses collecting securely with Cashpe every single day.</p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:26px">
      <a href="CreateAccount" class="cp-btn">Create free account <i class="fas fa-arrow-right"></i></a>
      <a href="trial/index.php" class="cp-btn ghost">Try live demo</a>
    </div>
  </div>
</section>
<?php require_once('inc/foot.php'); ?>
