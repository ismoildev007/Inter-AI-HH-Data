(() => {
  const loadScript = (src) => new Promise((resolve) => {
    if (document.querySelector(`script[data-manual="${src}"]`)) return resolve();
    const s = document.createElement('script');
    s.src = src;
    s.async = true;
    s.dataset.manual = src;
    s.onload = resolve;
    s.onerror = resolve;
    document.head.appendChild(s);
  });

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

  const legacyNeeded = needsDashboardCustom || needsAnalytics || document.querySelector('.nxl-navigation');
  if (!legacyNeeded) return;

  (async () => {
    await loadScript('https://code.jquery.com/jquery-3.6.0.min.js');
    await loadScript('https://cdn.jsdelivr.net/npm/moment@2.29.4/min/moment.min.js');
    await loadScript('https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js');
    await loadScript('https://cdn.jsdelivr.net/npm/perfect-scrollbar@1.5.5/dist/perfect-scrollbar.min.js');
    await loadScript('/assets/vendors/js/nxlNavigation.min.js');
    if (needsDashboardCustom || needsAnalytics) {
      await loadScript('https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js');
      await loadScript('https://cdn.jsdelivr.net/npm/jquery-circle-progress@1.2.2/dist/circle-progress.min.js');
      await loadScript('/assets/vendors/js/jquery.time-to.min.js');
    }
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
  })();

  document.addEventListener('click', (e) => {
    const link = e.target && e.target.closest('.nxl-account-dropdown .dropdown-item[href]');
    if (!link) return;
    e.preventDefault();
    window.location.assign(link.getAttribute('href'));
  });
})();
