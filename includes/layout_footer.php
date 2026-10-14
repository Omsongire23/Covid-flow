<?php
declare(strict_types=1);

/**
 * layout_footer.php — Global footer, disclaimer, scripts, and back-to-top button.
 * Expected optional variable:
 *  - $pageScript (string|array): JS module file(s) relative to /assets/js/ to load
 */
?>
  </main> <!-- /#main-content -->

  <!-- Site Footer -->
  <footer class="site-footer">
    <!-- Mandatory Medical Disclaimer Banner -->
    <div class="footer-disclaimer-band">
      <div class="container disclaimer-inner">
        <svg class="icon icon-md text-warn" aria-hidden="true"><use href="#icon-warn"></use></svg>
        <p class="disclaimer-text">
          <strong>Medical Disclaimer:</strong> This portal is developed for Software Testing and Quality Assurance educational purposes. Information is gathered from the World Health Organization (WHO), Ministry of Health and Family Welfare (MoHFW), and the disease.sh repository. It does not replace professional medical consultation, diagnosis, or clinical advice. In a health emergency, call your local medical emergency services or national helpline <strong>1075</strong> immediately.
        </p>
      </div>
    </div>

    <div class="container footer-grid">
      <!-- Brand & Mission Column -->
      <div class="footer-col footer-about">
        <div class="footer-brand">
          <img src="/assets/img/logo.svg" alt="" width="30" height="30" aria-hidden="true">
          <span>COVID-19 <strong>Portal</strong></span>
        </div>
        <p class="footer-desc">
          Evidence-based pandemic information platform engineered with strict input validation, dual-engine regular expression parity, and verifiable software quality deliverables.
        </p>
        <div class="sqa-badge">
          <span>Course: Software Testing &amp; Quality Assurance</span>
        </div>
      </div>

      <!-- Information Links -->
      <div class="footer-col">
        <h2 class="footer-heading">Public Health</h2>
        <ul class="footer-links">
          <li><a href="/prevention.php">Prevention Guidelines</a></li>
          <li><a href="/symptoms.php">Symptoms Checker</a></li>
          <li><a href="/vaccination.php">Vaccination Information</a></li>
          <li><a href="/myths.php">Myths vs. Facts</a></li>
          <li><a href="/helplines.php">Emergency Helplines</a></li>
        </ul>
      </div>

      <!-- Portal & Interactive Tools -->
      <div class="footer-col">
        <h2 class="footer-heading">Portal Services</h2>
        <ul class="footer-links">
          <li><a href="/statistics.php">Global Trends &amp; Tables</a></li>
          <li><a href="/program-registration.php">Programme Registration</a></li>
          <li><a href="/community.php">Community Discussions</a></li>
          <li><a href="/quality-lab.php">QA &amp; Regex Lab</a></li>
          <?php if (current_user()): ?>
            <li><a href="/profile.php">Account Dashboard</a></li>
          <?php else: ?>
            <li><a href="/login.php">Account Sign In</a></li>
          <?php endif; ?>
        </ul>
      </div>

      <!-- Quality Standards & Compliance -->
      <div class="footer-col">
        <h2 class="footer-heading">Quality &amp; Standards</h2>
        <ul class="footer-links">
          <li><a href="/quality-lab.php">Live Pattern Test Runner</a></li>
          <li><span class="footer-static-item">Dual Engine: JS / PHP (Du)</span></li>
          <li><span class="footer-static-item">Zero Inline Scripts (CSP)</span></li>
          <li><span class="footer-static-item">OWASP Compliant Session Security</span></li>
          <li><span class="footer-static-item">WCAG 2.1 AA Contrast</span></li>
        </ul>
      </div>
    </div>

    <!-- Sub-footer bar -->
    <div class="footer-bottom">
      <div class="container footer-bottom-inner">
        <p>&copy; <?= date('Y') ?> COVID-19 Information Portal &bull; SQA Mini Project &bull; All Rights Reserved.</p>
        <p class="footer-meta">Data source: <a href="https://disease.sh" target="_blank" rel="noopener noreferrer">disease.sh</a> &bull; Handcrafted with HTML5, CSS3, ES Modules, and PHP PDO</p>
      </div>
    </div>
  </footer>

  <!-- Back to Top Button -->
  <button type="button" class="btn-back-to-top" id="backToTopBtn" aria-label="Back to top" hidden>
    <svg class="icon" aria-hidden="true"><use href="#icon-chevron-up"></use></svg>
  </button>

  <!-- Global Application JS -->
  <script type="module" src="/assets/js/app.js"></script>

  <!-- Page-specific JS modules -->
  <?php if (!empty($pageScript)): ?>
    <?php foreach ((array)$pageScript as $script): ?>
      <script type="module" src="/assets/js/<?= e($script) ?>"></script>
    <?php endforeach; ?>
  <?php endif; ?>
</body>
</html>
