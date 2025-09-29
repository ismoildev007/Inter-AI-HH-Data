// Render four small sparkline charts with real backend data
(() => {
  const data = window.analyticsData;
  if (!data) return;

  function ensureApex(cb){ if (window.ApexCharts) return cb();
    const s=document.createElement('script'); s.src='https://cdn.jsdelivr.net/npm/apexcharts@3.44.0'; s.async=true; s.onload=cb; document.head.appendChild(s);
  }

  const charts = [
    { id: '#bounce-rate', key: 'bounce', name: 'Hourly Visitors', color: '#64748a' },
    { id: '#page-views', key: 'pageViews', name: 'Daily Visitors', color: '#3454d1' },
    { id: '#site-impressions', key: 'impressions', name: 'Monthly Visitors', color: '#e49e3d' },
    { id: '#conversions-rate', key: 'conversions', name: 'Yearly Visitors', color: '#25b865' },
  ];

  const renderOne = (cfg) => {
    try {
      const el = document.querySelector(cfg.id);
      if (!el) return;
      const d = data[cfg.key];
      if (!d || !Array.isArray(d.series)) return;
      el.innerHTML = '';
      const opt = {
        series: [{ name: cfg.name, data: d.series }],
        chart: { type: 'area', height: 80, sparkline: { enabled: true }, toolbar: { show: false } },
        stroke: { width: 1, curve: 'smooth' },
        fill: { opacity: [0.85, 0.25, 1, 1], gradient: { inverseColors: false, shade: 'light', type: 'vertical', opacityFrom: 0.5, opacityTo: 0.1, stops: [0, 100, 100, 100] } },
        yaxis: { min: 0 },
        colors: [cfg.color],
        xaxis: { categories: d.labels || [], axisBorder: { show: false }, axisTicks: { show: false } },
        tooltip: { y: { formatter: (v) => (+v) + '' }, style: { fontSize: '12px', fontFamily: 'Inter' } },
        animations: { enabled: true },
        dataLabels: { enabled: false },
      };
      new window.ApexCharts(el, opt).render();
    } catch (e) {
      console.error('Analytics sparkline failed', cfg.id, e);
    }
  };

  const run = () => charts.forEach(renderOne);

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => ensureApex(run), { once: true });
  } else {
    ensureApex(run);
  }
})();

