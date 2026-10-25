/* AutoCare - M1 static SPA shell.
   Hash-based router: views are HTML templates in /views, loaded into #app
   without a page reload. No backend calls yet (added in M4, wrapped in
   service files in M5). */

const routes = {
  '/login':               { view: 'login',               title: 'Log in',        layout: 'auth' },
  '/register':            { view: 'register',            title: 'Create account', layout: 'auth' },
  '/dashboard':           { view: 'dashboard',           title: 'Dashboard' },
  '/vehicles':            { view: 'vehicles',            title: 'My vehicles' },
  '/vehicles/details':    { view: 'vehicle-details',     title: 'Vehicle details' },
  '/services':            { view: 'services',            title: 'Services' },
  '/fuel':                { view: 'fuel',                title: 'Fuel logs' },
  '/reminders':           { view: 'reminders',           title: 'Reminders' },
  '/profile':             { view: 'profile',             title: 'Profile' },
  '/admin/users':         { view: 'admin-users',         title: 'Users' },
  '/admin/service-types': { view: 'admin-service-types', title: 'Service types' },
  '/404':                 { view: 'not-found',           title: 'Page not found' }
};

const appEl = document.getElementById('app');
const sidebarEl = document.getElementById('sidebar');

/* Per-view setup that cannot live in the HTML template (scripts in
   injected HTML do not run). */
const viewInit = {
  fuel: initFuelChart
};

async function render() {
  const path = (location.hash.replace(/^#/, '') || '/login').split('?')[0];
  const route = routes[path] || routes['/404'];

  document.body.dataset.layout = route.layout || 'app';
  document.title = route.title + ' - AutoCare';
  markActiveNav(path);

  try {
    const res = await fetch('views/' + route.view + '.html');
    if (!res.ok) throw new Error('HTTP ' + res.status);
    appEl.innerHTML = await res.text();
  } catch (err) {
    appEl.innerHTML =
      '<div class="alert alert-danger m-4">Could not load this page. ' +
      'Open the app through a local web server (for example ' +
      '<code>php -S localhost:8000 -t frontend</code>), not by double-clicking index.html.</div>';
    return;
  }

  const offcanvas = bootstrap.Offcanvas.getInstance(sidebarEl);
  if (offcanvas) offcanvas.hide();
  window.scrollTo(0, 0);
  appEl.focus({ preventScroll: true });

  if (viewInit[route.view]) viewInit[route.view]();
}

function markActiveNav(path) {
  document.querySelectorAll('[data-nav]').forEach(function (link) {
    const target = link.dataset.nav;
    const active = path === target || (target === '/vehicles' && path.startsWith('/vehicles'));
    link.classList.toggle('active', active);
    if (active) link.setAttribute('aria-current', 'page');
    else link.removeAttribute('aria-current');
  });
}

function showToast(message) {
  const el = document.createElement('div');
  el.className = 'toast ac-toast align-items-center border-0';
  el.setAttribute('role', 'status');
  el.innerHTML =
    '<div class="d-flex"><div class="toast-body">' + message + '</div>' +
    '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>';
  document.getElementById('toasts').appendChild(el);
  const toast = new bootstrap.Toast(el, { delay: 3500 });
  el.addEventListener('hidden.bs.toast', function () { el.remove(); });
  toast.show();
}

/* Demo-only form handling: validates, then navigates or closes the modal.
   Replaced by real AJAX calls in M4. */
document.addEventListener('submit', function (e) {
  const form = e.target.closest('form.js-demo-form');
  if (!form) return;
  e.preventDefault();
  form.classList.add('was-validated');
  if (!form.checkValidity()) return;

  const modalEl = form.closest('.modal');
  if (modalEl) {
    bootstrap.Modal.getInstance(modalEl).hide();
    form.classList.remove('was-validated');
    form.reset();
    showToast('Saved (demo data, not stored yet)');
  }
  if (form.dataset.goto) location.hash = form.dataset.goto;
});

document.addEventListener('click', function (e) {
  if (e.target.closest('#decodeVin')) {
    showToast('VIN lookup is connected to the NHTSA API in a later milestone');
  }
});

function initFuelChart() {
  const canvas = document.getElementById('fuelChart');
  if (!canvas || typeof Chart === 'undefined') return;

  const css = getComputedStyle(document.documentElement);
  const red = css.getPropertyValue('--ac-red').trim();
  const ink = css.getPropertyValue('--ac-ink').trim();

  new Chart(canvas, {
    data: {
      labels: ['May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
      datasets: [
        { type: 'bar', label: 'Fuel cost (KM)', data: [182, 205, 240, 198, 176, 214],
          backgroundColor: ink, yAxisID: 'y', borderRadius: 2 },
        { type: 'line', label: 'Consumption (L/100 km)', data: [8.4, 8.1, 8.9, 7.9, 7.6, 7.8],
          borderColor: red, backgroundColor: red, tension: 0.3, pointRadius: 4, yAxisID: 'y1' }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      scales: {
        y:  { beginAtZero: true, title: { display: true, text: 'KM' } },
        y1: { position: 'right', min: 5, max: 11, grid: { drawOnChartArea: false },
              title: { display: true, text: 'L/100 km' } }
      },
      plugins: { legend: { position: 'bottom' } }
    }
  });
}

window.addEventListener('hashchange', render);
window.addEventListener('DOMContentLoaded', function () {
  if (!location.hash) location.hash = '#/login';
  else render();
});
