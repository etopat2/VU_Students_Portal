(() => {
    const pageContent = document.getElementById('pageContent');
    const sidebar = document.getElementById('appSidebar');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebarClose = document.querySelector('.sidebar-close');
    const pageTitle = document.getElementById('pageTitle');
    const charts = new Map();

    function closeSidebar({ restoreFocus = true } = {}) {
        if (!sidebar || window.innerWidth >= 992) {
            return;
        }

        sidebar.classList.remove('is-open');
        sidebar.setAttribute('aria-hidden', 'true');
        sidebarBackdrop?.classList.remove('is-visible');
        document.body.classList.remove('sidebar-open');
        sidebarToggle?.setAttribute('aria-expanded', 'false');

        if (restoreFocus) {
            sidebarToggle?.focus();
        }
    }

    function openSidebar() {
        if (!sidebar || window.innerWidth >= 992) {
            return;
        }

        sidebar.classList.add('is-open');
        sidebar.setAttribute('aria-hidden', 'false');
        sidebarBackdrop?.classList.add('is-visible');
        document.body.classList.add('sidebar-open');
        sidebarToggle?.setAttribute('aria-expanded', 'true');
        sidebarClose?.focus();
    }

    function syncSidebarForViewport() {
        if (!sidebar) {
            return;
        }

        if (window.innerWidth >= 992) {
            sidebar.classList.remove('is-open');
            sidebar.setAttribute('aria-hidden', 'false');
            sidebarBackdrop?.classList.remove('is-visible');
            document.body.classList.remove('sidebar-open');
            sidebarToggle?.setAttribute('aria-expanded', 'false');
            return;
        }

        if (!sidebar.classList.contains('is-open')) {
            sidebar.setAttribute('aria-hidden', 'true');
        }
    }

    function showChartUnavailableMessage() {
        if (typeof Chart !== 'undefined') {
            return;
        }

        const chartCards = pageContent.querySelectorAll('.chart-card .card-body');
        chartCards.forEach((card) => {
            const message = document.createElement('p');
            message.className = 'alert alert-warning mt-3 mb-0';
            message.setAttribute('role', 'status');
            message.textContent = 'Charts could not load. Check your internet connection and refresh the page.';
            card.append(message);
        });
    }

    function initializeCharts() {
        const gradeCanvas = pageContent.querySelector('#gradeDistributionChart');

        if (!gradeCanvas) {
            return;
        }

        if (typeof Chart === 'undefined') {
            showChartUnavailableMessage();
            return;
        }

        charts.set('gradeDistributionChart', new Chart(gradeCanvas, {
            type: 'bar',
            data: {
                labels: JSON.parse(gradeCanvas.dataset.labels || '[]'),
                datasets: [{
                    label: 'Assessment records',
                    data: JSON.parse(gradeCanvas.dataset.values || '[]'),
                    backgroundColor: '#5470d5',
                    borderRadius: 6,
                    maxBarThickness: 44,
                }],
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: { color: '#8490a3', font: { size: 10 } },
                    },
                    y: {
                        beginAtZero: true,
                        border: { display: false },
                        grid: { color: '#eef1f6', drawTicks: false },
                        ticks: {
                            precision: 0,
                            color: '#8490a3',
                            padding: 8,
                            font: { size: 10 },
                        },
                    },
                },
            },
        }));
    }

    function updateActiveLink(url) {
        const currentUrl = new URL(url, window.location.href);
        const currentPath = currentUrl.pathname.replace(/\/+$/, '') || '/';

        document.querySelectorAll('.sidebar-link[data-page-link]').forEach((link) => {
            const linkPath = new URL(link.href, window.location.href).pathname.replace(/\/+$/, '') || '/';
            const isResourceRoute = ['/students', '/lecturers', '/courses', '/grades'].includes(linkPath);
            const isActive = linkPath === currentPath || (isResourceRoute && currentPath.startsWith(`${linkPath}/`));
            link.classList.toggle('is-active', isActive);

            if (isActive) {
                link.setAttribute('aria-current', 'page');
            } else {
                link.removeAttribute('aria-current');
            }
        });
    }

    function showNavigationError() {
        const alert = document.createElement('div');
        alert.className = 'alert alert-danger page-load-error';
        alert.setAttribute('role', 'alert');
        alert.textContent = 'That page could not be loaded. Please try again.';
        pageContent.prepend(alert);
    }

    function filterTable(input) {
        const table = pageContent.querySelector(input.dataset.tableFilter);

        if (!table) {
            return;
        }

        const query = input.value.trim().toLocaleLowerCase();
        const rows = [...table.querySelectorAll('tbody tr[data-filter-row]')];
        let visibleCount = 0;

        rows.forEach((row) => {
            const matches = row.textContent.toLocaleLowerCase().includes(query);
            row.classList.toggle('d-none', !matches);
            visibleCount += Number(matches);
        });

        table.querySelector('.filter-empty')?.classList.toggle('d-none', visibleCount > 0);
    }

    async function loadPage(url, { addHistory = true } = {}) {
        const response = await fetch(url, {
            headers: {
                Accept: 'text/html',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error(`Page request failed with status ${response.status}`);
        }

        const html = await response.text();
        const title = response.headers.get('X-Page-Title');

        charts.forEach((chart) => chart.destroy());
        charts.clear();
        pageContent.innerHTML = html;

        if (title) {
            pageTitle.textContent = title;
            document.title = `${title} | VU Student Portal`;
        }

        updateActiveLink(url);

        if (addHistory) {
            window.history.pushState({}, '', url);
        }

        closeSidebar({ restoreFocus: false });

        window.scrollTo({ top: 0, behavior: 'smooth' });
        pageContent.focus({ preventScroll: true });
        initializeCharts();
    }

    sidebarToggle?.addEventListener('click', openSidebar);
    sidebarClose?.addEventListener('click', () => closeSidebar());
    sidebarBackdrop?.addEventListener('click', () => closeSidebar());
    window.addEventListener('resize', syncSidebarForViewport);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && sidebar?.classList.contains('is-open')) {
            closeSidebar();
        }
    });

    document.addEventListener('input', (event) => {
        const input = event.target.closest('[data-table-filter]');

        if (input) {
            filterTable(input);
        }
    });

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[data-page-link]');

        if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        const url = new URL(link.href, window.location.href);

        if (url.origin !== window.location.origin) {
            return;
        }

        event.preventDefault();
        pageContent.querySelector('.page-load-error')?.remove();
        pageContent.setAttribute('aria-busy', 'true');

        loadPage(url.href).catch((error) => {
            console.error('Unable to load the requested portal page.', error);
            showNavigationError();
        }).finally(() => {
            pageContent.removeAttribute('aria-busy');
        });
    });

    window.addEventListener('popstate', () => {
        pageContent.setAttribute('aria-busy', 'true');

        loadPage(window.location.href, { addHistory: false }).catch((error) => {
            console.error('Unable to restore the requested portal page.', error);
            showNavigationError();
        }).finally(() => {
            pageContent.removeAttribute('aria-busy');
        });
    });

    syncSidebarForViewport();
    initializeCharts();
})();
