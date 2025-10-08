// Custom initializers to guarantee animations without relying on theme init
// Payment Record chart
function ensureApexCharts(cb){
  if (window.ApexCharts) return cb();
  const s = document.createElement('script');
  s.src = 'https://cdn.jsdelivr.net/npm/apexcharts@3.44.0';
  s.async = true;
  s.onload = () => cb();
  s.onerror = () => console.error('Failed to load ApexCharts from CDN');
  document.head.appendChild(s);
}

if (typeof window !== 'undefined') {
  const initPayment = () => {
    const paymentEl = document.querySelector('#payment-records-chart');
    if (!paymentEl) return false;
    const render = () => {
      const options = {
      chart: { height: 380, width: '100%', stacked: false, toolbar: { show: false } },
      stroke: { width: [1, 2, 3], curve: 'smooth', lineCap: 'round' },
      plotOptions: { bar: { endingShape: 'rounded', columnWidth: '30%' } },
      colors: ['#3454d1', '#a2acc7', '#E1E3EA'],
      series: [
        { name: 'Payment Rejected', type: 'bar', data: [23, 11, 22, 27, 13, 22, 37, 21, 44, 22, 30, 21] },
        { name: 'Payment Completed', type: 'line', data: [44, 55, 41, 67, 22, 43, 21, 41, 56, 27, 43, 41] },
        { name: 'Awaiting Payment', type: 'bar', data: [44, 55, 41, 67, 22, 43, 21, 41, 56, 27, 43, 56] }
      ],
      fill: { opacity: [0.85, 0.25, 1, 1], gradient: { inverseColors: false, shade: 'light', type: 'vertical', opacityFrom: 0.5, opacityTo: 0.1, stops: [0, 100, 100, 100] } },
      markers: { size: 0 },
      xaxis: { categories: ['JAN/23','FEB/23','MAR/23','APR/23','MAY/23','JUN/23','JUL/23','AUG/23','SEP/23','OCT/23','NOV/23','DEC/23'], axisBorder: { show: false }, axisTicks: { show: false }, labels: { style: { fontSize: '10px', colors: '#A0ACBB' } } },
      yaxis: { labels: { formatter: (v) => v + 'K', offsetX: -5, offsetY: 0, style: { color: '#A0ACBB' } } },
      grid: { xaxis: { lines: { show: false } }, yaxis: { lines: { show: false } } },
      dataLabels: { enabled: false },
      tooltip: { y: { formatter: (v) => v + 'K' }, style: { fontSize: '12px', fontFamily: 'Inter' } },
      legend: { show: false, labels: { fontSize: '12px', colors: '#A0ACBB' }, fontSize: '12px', fontFamily: 'Inter' },
      animations: { enabled: true }
      };
      window.requestAnimationFrame(() => {
        try { new window.ApexCharts(paymentEl, options).render(); } catch (e) { console.error('Payment chart init failed:', e); }
      });
    };
    ensureApexCharts(render);
    return true;
  };
  if (!initPayment()) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initPayment, { once: true });
    } else {
      // Try again on next tick if DOM updated after hydration
      setTimeout(initPayment, 50);
    }
  }
}
