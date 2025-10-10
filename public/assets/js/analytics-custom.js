// Render four small sparkline charts with real backend data
(() => {
  const MAX_ATTEMPTS = 20;
  const RETRY_DELAY = 150;
  let attempts = 0;

  function ensureApex(cb){
    if (window.ApexCharts) return cb();
    const run = () => { if (window.ApexCharts) cb(); };
    const append = (src, onError) => {
      const s = document.createElement('script');
      s.src = src;
      s.async = true;
      s.onload = run;
      s.onerror = onError;
      document.head.appendChild(s);
    };
    append('https://cdn.jsdelivr.net/npm/apexcharts@3.44.0', () => {
      append('/assets/vendors/js/apexcharts.min.js', () => { if (!window.ApexCharts) cb(); });
    });
  }

  const charts = [
    { id: '#bounce-rate', key: 'bounce', name: 'Hourly Visitors', color: '#64748a' },
    { id: '#page-views', key: 'pageViews', name: 'Daily Visitors', color: '#3454d1' },
    { id: '#site-impressions', key: 'impressions', name: 'Monthly Visitors', color: '#e49e3d' },
    { id: '#conversions-rate', key: 'conversions', name: 'Yearly Visitors', color: '#25b865' },
    // Vacancies
    {
      id: '#vacancy-hourly',
      key: 'vac_hourly',
      name: 'Hourly Vacancies',
      colors: ['#8b5cf6', '#2563eb', '#f97316'],
    },
    {
      id: '#vacancy-daily',
      key: 'vac_daily',
      name: 'Daily Vacancies',
      colors: ['#06b6d4', '#0ea5e9', '#fbbf24'],
    },
    {
      id: '#vacancy-weekly',
      key: 'vac_weekly',
      name: 'Weekly Vacancies',
      colors: ['#ef4444', '#38bdf8', '#f59e0b'],
    },
    {
      id: '#vacancy-monthly',
      key: 'vac_monthly',
      name: 'Monthly Vacancies',
      colors: ['#10b981', '#1d4ed8', '#fb7185'],
    },
  ];

  const renderOne = (cfg, data) => {
    try {
      const el = document.querySelector(cfg.id);
      if (!el) return;
      const d = data[cfg.key];
      if (!d) return;
      el.innerHTML = '';
      const rawLabels = Array.isArray(d.labels) ? d.labels : [];
      const labels = rawLabels.length ? rawLabels.map((v) => (v == null ? '' : String(v))) : [''];
      const rawSeries = d.series;

      let buildSeries = [];
      if (Array.isArray(rawSeries)) {
        const seriesValues = rawSeries.length ? rawSeries.map((v) => Number(v) || 0) : [0];
        buildSeries = [{ name: cfg.name, data: seriesValues }];
      } else if (rawSeries && typeof rawSeries === 'object') {
        buildSeries = Object.entries(rawSeries).map(([name, values]) => ({
          name,
          data: Array.isArray(values) ? values.map((v) => Number(v) || 0) : [0],
        }));
        buildSeries = buildSeries.map((serie) => ({
          name: serie.name,
          data: serie.data.length ? serie.data : [0],
        }));
        if (!buildSeries.length) {
          buildSeries = [{ name: cfg.name, data: [0] }];
        }
      } else {
        buildSeries = [{ name: cfg.name, data: [0] }];
      }

      const allValues = buildSeries.flatMap((serie) => serie.data);
      const maxVal = allValues.reduce((m, v) => (v > m ? v : m), 0);
      const hasData = maxVal > 0;
      const opt = {
        series: buildSeries,
        chart: { type: 'area', height: 80, sparkline: { enabled: true }, toolbar: { show: false } },
        stroke: { width: 2, curve: 'smooth' },
        fill: { opacity: [0.85, 0.25, 1, 1], gradient: { inverseColors: false, shade: 'light', type: 'vertical', opacityFrom: 0.5, opacityTo: 0.1, stops: [0, 100, 100, 100] } },
        markers: { size: hasData ? 0 : 3 },
        yaxis: hasData ? { min: 0 } : { min: -1, max: 1 },
        colors: Array.isArray(cfg.colors) ? cfg.colors.slice(0, buildSeries.length) : [cfg.color],
        xaxis: { categories: labels, axisBorder: { show: false }, axisTicks: { show: false } },
        tooltip: { y: { formatter: (v) => (+v) + '' }, style: { fontSize: '12px', fontFamily: 'Inter' } },
        animations: { enabled: true },
        dataLabels: { enabled: false },
      };
      if (!window.ApexCharts) return;
      new window.ApexCharts(el, opt).render();
    } catch (e) {
      console.error('Analytics sparkline failed', cfg.id, e);
    }
  };

  const run = (data) => charts.forEach((cfg) => renderOne(cfg, data));

  const init = () => {
    const data = window.analyticsData;
    if (!data || typeof data !== 'object') {
      if (attempts < MAX_ATTEMPTS) {
        attempts += 1;
        setTimeout(init, RETRY_DELAY);
      }
      return;
    }
    const launch = () => ensureApex(() => run(data));
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', launch, { once: true });
    } else {
      launch();
    }
  };

  init();
})();
