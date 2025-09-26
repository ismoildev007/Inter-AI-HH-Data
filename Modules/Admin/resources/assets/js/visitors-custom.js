// Render Visitors Overview chart from backend data if present
(() => {
  const el = document.querySelector('#visitors-overview-chart');
  if (!el) return;
  const data = window.visitorsChart;
  if (!data || !Array.isArray(data.labels) || !Array.isArray(data.series)) return;
  function ensureApex(cb){ if (window.ApexCharts) return cb();
    const s=document.createElement('script'); s.src='https://cdn.jsdelivr.net/npm/apexcharts@3.44.0'; s.onload=cb; s.async=true; document.head.appendChild(s);
  }
  ensureApex(() => {
    const opt = {
      chart: { height: 370, type: 'area', stacked: false, toolbar: { show: false } },
      series: [{ name: 'Visitors', data: data.series }],
      xaxis: { categories: data.labels, axisBorder:{show:false}, axisTicks:{show:false}, labels:{ style:{ fontSize:'11px', colors:'#64748b' } } },
      yaxis: { labels:{ formatter:(v)=> (+v)+'', style:{ fontSize:'11px', color:'#64748b' } } },
      stroke: { curve: 'smooth', width: 2 },
      fill: { type: 'gradient', gradient:{ shadeIntensity:1, opacityFrom:.4, opacityTo:.3, stops:[0,90,100] } },
      colors: ['#e49e3d'], dataLabels:{ enabled:false }, grid:{ strokeDashArray:3, borderColor:'#ebebf3' },
      tooltip:{ y:{ formatter:(v)=> (+v)+' visits' }, style:{ fontSize:'12px', fontFamily:'Inter' } },
      animations:{ enabled:true }
    };
    try{ new window.ApexCharts(el, opt).render(); }catch(e){ console.error('Visitors chart render failed', e); }
  });
})();
