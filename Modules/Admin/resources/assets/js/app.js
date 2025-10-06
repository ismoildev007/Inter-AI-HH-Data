import "./dashboard-custom.js";
import "./visitors-custom.js";
import "./mini-charts-custom.js";
import "./analytics-custom.js";

// Ensure these assets are included in the manifest for Blade asset() usage
import "../images/logo-abbr.png";
import "../images/favicon.ico";
import "../images/logo-full.png";
import "../images/avatar/1.png";
import "../images/avatar/5.svg";
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
// We use custom initializers for dashboard charts; do NOT import theme dashboard-init to avoid duplicate charts
const needsDashboardCustom = !!(document.querySelector('#payment-records-chart') ||
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
const legacyNeeded = needsDashboardCustom || needsAnalytics || document.querySelector('.nxl-navigation');
if (legacyNeeded) {
  (async () => {
    await loadScript('https://code.jquery.com/jquery-3.6.0.min.js');
    await loadScript('https://cdn.jsdelivr.net/npm/moment@2.29.4/min/moment.min.js');
    // Bootstrap + PerfectScrollbar are required by the theme navigation
    await loadScript('https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js');
    await loadScript('https://cdn.jsdelivr.net/npm/perfect-scrollbar@1.5.5/dist/perfect-scrollbar.min.js');
    // Local navigation script depends on the above
    await loadScript(new URL('../vendors/js/nxlNavigation.min.js', import.meta.url).href);
    if (needsDashboardCustom || needsAnalytics) {
      await loadScript('https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js');
      await loadScript('https://cdn.jsdelivr.net/npm/jquery-circle-progress@1.2.2/dist/circle-progress.min.js');
      // Load the same timeTo plugin bundled with the template to avoid API mismatches
      await loadScript(new URL('../vendors/js/jquery.time-to.min.js', import.meta.url).href);
    }
    // Do NOT import the theme's analytics-init (it draws default demo charts)
    // Instead, initialize only the date range picker, our custom charts handle rendering
    if (needsAnalytics) {
      const initRange = () => {
        const $ = window.jQuery;
        const el = $('#reportrange');
        if (!el.length || !window.moment) return;
        const start = window.moment().subtract(29, 'days');
        const end = window.moment();
        function cb(s, e) {
          $('#reportrange span').html(s.format('MMM D, YY') + ' - ' + e.format('MMM D, YY'));
        }
        el.daterangepicker({
          startDate: start,
          endDate: end,
          ranges: {
            'Today': [window.moment(), window.moment()],
            'Yesterday': [window.moment().subtract(1, 'days'), window.moment().subtract(1, 'days')],
            'Last 7 Days': [window.moment().subtract(6, 'days'), window.moment()],
            'Last 30 Days': [window.moment().subtract(29, 'days'), window.moment()],
            'This Month': [window.moment().startOf('month'), window.moment().endOf('month')],
            'Last Month': [window.moment().subtract(1, 'month').startOf('month'), window.moment().subtract(1, 'month').endOf('month')]
          }
        }, cb);
        cb(start, end);
      };
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initRange, { once: true });
      } else {
        initRange();
      }
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
