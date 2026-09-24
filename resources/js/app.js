// Chart.js is only needed on the dashboard, so it is a separate chunk that
// loads on demand instead of shipping (~200 kB) with every page.
window.loadChart = () => import('chart.js/auto').then((module) => module.default);
