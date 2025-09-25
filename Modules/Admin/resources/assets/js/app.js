import "./dashboard-custom.js";
import "./visitors-custom.js";

// Ensure these assets are included in the manifest for Blade asset() usage
import "../images/logo-abbr.png";
import "../images/favicon.ico";
import "../images/logo-full.png";
import "../images/avatar/1.png";
import "../images/avatar/2.png";
import "../images/avatar/3.png";
import "../images/avatar/4.png";
import "../vendors/img/flags/4x3/us.svg";
import "../vendors/img/flags/1x1/us.svg";
import "../vendors/img/flags/1x1/sa.svg";
import "../vendors/img/flags/1x1/bd.svg";
import "../vendors/img/flags/1x1/ch.svg";
import "../vendors/img/flags/1x1/nl.svg";

// Utilities
function loadScript(src) {
  return new Promise((resolve) => {
    const s = document.createElement('script');
    s.src = src; s.async = true; s.onload = resolve; s.onerror = resolve; document.head.appendChild(s);
  });
}

// Dashboard/Analytics initializers
function ensureApexCharts(cb) {
  if (window.ApexCharts) return cb();
  const s = document.createElement('script');
  s.src = 'https://cdn.jsdelivr.net/npm/apexcharts@3.44.0';
  s.async = true;
  s.onload = () => cb();
  s.onerror = () => cb();
  document.head.appendChild(s);
}

// Load charts initializers only when relevant elements exist
const needsDashboard = !!(document.querySelector('#payment-records-chart') ||
  document.querySelector('#total-sales-color-graph') ||
  document.querySelector('#task-completed-area-chart') ||
  document.querySelector('#new-tasks-area-chart') ||
  document.querySelector('#project-done-area-chart'));

const needsAnalytics = !!(document.querySelector('#visitors-overview-statistics-chart') ||
  document.querySelector('#campaign-alytics-bar-chart') ||
  document.querySelector('#leads-overview-donut') ||
  document.querySelector('#social-radar-chart') ||
  document.querySelector('#bounce-rate') ||
  document.querySelector('#page-views') ||
  document.querySelector('#site-impressions') ||
  document.querySelector('#conversions-rate') ||
  document.querySelector('[data-time-countdown]'));

// Load legacy libs in correct order (jQuery -> moment -> plugins), then theme inits
const legacyNeeded = needsDashboard || needsAnalytics || document.querySelector('.nxl-navigation');
if (legacyNeeded) {
  (async () => {
    await loadScript('https://code.jquery.com/jquery-3.6.0.min.js');
    await loadScript('https://cdn.jsdelivr.net/npm/moment@2.29.4/min/moment.min.js');
    // Bootstrap + PerfectScrollbar are required by the theme navigation
    await loadScript('https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js');
    await loadScript('https://cdn.jsdelivr.net/npm/perfect-scrollbar@1.5.5/dist/perfect-scrollbar.min.js');
    // Local navigation script depends on the above
    await loadScript(new URL('../vendors/js/nxlNavigation.min.js', import.meta.url).href);
    if (needsDashboard || needsAnalytics) {
      await loadScript('https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js');
      await loadScript('https://cdn.jsdelivr.net/npm/jquery-circle-progress@1.2.2/dist/circle-progress.min.js');
      // Load the same timeTo plugin bundled with the template to avoid API mismatches
      await loadScript(new URL('../vendors/js/jquery.time-to.min.js', import.meta.url).href);
    }
    if (needsDashboard) {
      ensureApexCharts(() => import('../js/dashboard-init.min.js'));
    }
    if (needsAnalytics) {
      ensureApexCharts(() => import('../js/analytics-init.min.js'));
    }
    // Theme common init after jQuery
    await import('../js/common-init.min.js');
  })();
}

// Ensure header account dropdown items navigate properly
document.addEventListener('click', (e) => {
  const link = e.target && e.target.closest('.nxl-account-dropdown .dropdown-item[href]');
  if (link) {
    // In case any vendor script prevented default on dropdown items
    e.preventDefault();
    window.location.assign(link.getAttribute('href'));
  }
});
