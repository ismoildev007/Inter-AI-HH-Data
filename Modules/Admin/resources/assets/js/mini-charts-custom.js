// Render three small area charts using backend-provided data
(() => {
  const data = window.dashboardMini;
  const ids = [
    { id: '#task-completed-area-chart', name: 'Users', seriesKey: 'users' },
    { id: '#new-tasks-area-chart', name: 'Applications', seriesKey: 'applications' },
    { id: '#project-done-area-chart', name: 'Resumes', seriesKey: 'resumes' },
  ];
  const colorMap = {
    users: '#3454d1',          // blue
    applications: '#25b865',   // green
    resumes: '#d13b4c',        // red
  };
  if (!data || !Array.isArray(data.labels)) return;
  function ensureApex(cb){ if (window.ApexCharts) return cb();
    const s=document.createElement('script'); s.src='https://cdn.jsdelivr.net/npm/apexcharts@3.44.0'; s.async=true; s.onload=cb; document.head.appendChild(s);
  }
  const renderOne = (cfg) => {
    const el = document.querySelector(cfg.id);
    if (!el) return;
    // Clear previous (theme-initialized) chart if any
    el.innerHTML = '';
    const series = data.series[cfg.seriesKey] || [];
    const color = colorMap[cfg.seriesKey] || '#3454d1';
    const opt = {
      series: [{ name: cfg.name, data: series }],
      chart: { type: 'area', height: 100, toolbar: { show: false }, sparkline: { enabled: true } },
      stroke: { width: 2, curve: 'smooth' },
      fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: .2, opacityTo: .75, stops: [0, 90, 100] } },
      colors: [color],
      grid: { show: false }, legend: { show: false }, dataLabels: { enabled: false },
      xaxis: { categories: data.labels, axisBorder: { show: false }, axisTicks: { show: false } },
      tooltip: { y: { formatter: (v) => (+v) + '' }, style: { fontSize: '12px', fontFamily: 'Inter' } },
      animations: { enabled: true },
    };
    try { new window.ApexCharts(el, opt).render(); } catch(e) { console.error('Mini chart failed', cfg.id, e); }
  };

  ensureApex(() => { ids.forEach(renderOne); });
})();
