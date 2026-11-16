(() => {
    const pageContent = document.getElementById('pageContent');
    const sidebar = document.getElementById('appSidebar');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebarClose = document.querySelector('.sidebar-close');
    const pageTitle = document.getElementById('pageTitle');
    const charts = new Map();
    let dashboardMetricsTimer = null;
    let dashboardMetricsController = null;

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

    function showToast(type, heading, description) {
        if (typeof window.toastMagic?.[type] !== 'function') {
            console.error('ToastMagic is unavailable; the portal notification could not be displayed.');
            return;
        }

        window.toastMagic[type]({ heading, description });
    }

    function filterOptions(parentSelector, childSelector, attributeName) {
        const parent = pageContent.querySelector(parentSelector);
        const child = pageContent.querySelector(childSelector);

        if (!parent || !child) {
            return;
        }

        const selectedValue = parent.value;
        let selectedOptionIsVisible = false;

        [...child.options].forEach((option, index) => {
            if (index === 0) {
                return;
            }

            const allowedValues = (option.dataset[attributeName] || '').split(',').filter(Boolean);
            const visible = allowedValues.includes(selectedValue);

            option.hidden = !visible;
            option.disabled = !visible;

            if (visible && option.selected) {
                selectedOptionIsVisible = true;
            }
        });

        if (!selectedOptionIsVisible) {
            child.value = '';
        }
    }

    function initializeAcademicFormFilters() {
        filterOptions('[data-course-program-filter]', '[data-academic-course-filter]', 'studyPrograms');
        filterOptions('[data-academic-course-filter]', '[data-academic-year-filter]', 'courseId');
        filterOptions('[data-faculty-filter]', '[data-department-filter]', 'facultyId');
        filterOptions('[data-department-filter]', '[data-lecturer-filter]', 'departmentId');

        const inServiceFlag = pageContent.querySelector('#is_in_service_teacher');
        const studySessionSelect = pageContent.querySelector('#study_session_id');
        if (inServiceFlag && studySessionSelect) {
            const updateSessionOptions = () => {
                Array.from(studySessionSelect.options).forEach((option) => {
                    const inServiceOnly = option.dataset.inServiceOnly === 'true';
                    option.hidden = inServiceOnly && !inServiceFlag.checked;
                    option.disabled = inServiceOnly && !inServiceFlag.checked;
                    if (option.disabled && option.selected) studySessionSelect.value = '';
                });
            };

            inServiceFlag.addEventListener('change', updateSessionOptions);
            updateSessionOptions();
        }
    }

    function initializePaymentModal() {
        const modalElement = pageContent.querySelector('#payFeesModal');

        if (!modalElement || modalElement.dataset.initialized === 'true' || typeof bootstrap === 'undefined') {
            return;
        }

        modalElement.dataset.initialized = 'true';

        const form = modalElement.querySelector('#payFeesForm');
        const invoiceSelect = modalElement.querySelector('#payment_invoice_id');
        const amountInput = modalElement.querySelector('#payment_amount');
        const methodSelect = modalElement.querySelector('#payment_method');
        const phoneInput = modalElement.querySelector('#payment_phone_number');
        const mobileFields = modalElement.querySelector('#mobileMoneyFields');
        const formFields = modalElement.querySelector('#payFeesFormFields');
        const waitingPanel = modalElement.querySelector('#payFeesWaiting');
        const statusMessage = modalElement.querySelector('#payFeesStatusMessage');
        const authorizationLink = modalElement.querySelector('#paymentAuthorizationLink');
        const tryAgainButton = modalElement.querySelector('#payFeesTryAgain');
        const submitButton = modalElement.querySelector('#payFeesSubmit');
        const submitSpinner = modalElement.querySelector('#payFeesSubmitSpinner');
        const mainSpinner = modalElement.querySelector('#payFeesSpinner');
        const errorPanel = modalElement.querySelector('#payFeesError');
        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        let requestInProgress = false;
        let activePaymentReference = modalElement.dataset.paymentRef || null;
        let polling = false;

        const updateInvoiceAmount = () => {
            const selected = invoiceSelect.selectedOptions[0];
            const balance = selected ? Number(selected.dataset.balance) : 0;
            amountInput.max = String(Math.floor(balance));
            amountInput.value = String(Math.floor(balance));
            modalElement.querySelector('#payment_amount_help').textContent = `Outstanding balance: UGX ${Math.floor(balance).toLocaleString()}`;
        };

        const updateMethodFields = () => {
            const mobileMoney = methodSelect.value === 'mobile_money';
            mobileFields.classList.toggle('d-none', !mobileMoney);
            phoneInput.required = mobileMoney;
        };

        const showError = (message) => {
            errorPanel.textContent = message;
            errorPanel.classList.remove('d-none');
        };

        const showWaiting = (message, finished = false) => {
            formFields.classList.add('d-none');
            waitingPanel.classList.remove('d-none');
            statusMessage.textContent = message;
            mainSpinner.classList.toggle('d-none', finished);
            submitButton.classList.add('d-none');
        };

        const resetForm = () => {
            activePaymentReference = null;
            authorizationLink.classList.add('d-none');
            waitingPanel.classList.add('d-none');
            formFields.classList.remove('d-none');
            submitButton.classList.remove('d-none');
            errorPanel.classList.add('d-none');
            updateInvoiceAmount();
        };

        const pollPayment = async () => {
            if (polling || !activePaymentReference) return;

            polling = true;
            while (polling && modalElement.classList.contains('show')) {
                await new Promise((resolve) => window.setTimeout(resolve, 5000));
                if (!polling || !activePaymentReference) break;

                try {
                    const statusUrl = modalElement.dataset.statusUrlTemplate.replace(
                        '__REFERENCE__',
                        encodeURIComponent(activePaymentReference),
                    );
                    const response = await fetch(statusUrl, {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    const result = await response.json();

                    if (result.status === 'success') {
                        polling = false;
                        showWaiting(result.message, true);
                        window.setTimeout(() => window.location.reload(), 1600);
                        return;
                    }

                    if (result.status === 'failed') {
                        polling = false;
                        showWaiting(result.message, true);
                        tryAgainButton.classList.remove('d-none');
                        return;
                    }

                    statusMessage.textContent = result.message || 'Waiting for payment confirmation. Keep this window open.';
                } catch (error) {
                    statusMessage.textContent = 'Connection interrupted. Reconnecting while we check your payment…';
                }
            }

            polling = false;
        };

        invoiceSelect.addEventListener('change', updateInvoiceAmount);
        methodSelect.addEventListener('change', updateMethodFields);
        tryAgainButton.addEventListener('click', resetForm);
        modalElement.addEventListener('hidden.bs.modal', () => { polling = false; });
        modalElement.addEventListener('shown.bs.modal', () => {
            if (activePaymentReference && !formFields.classList.contains('d-none')) {
                showWaiting('Checking your payment…');
            }
            pollPayment();
        });
        updateInvoiceAmount();
        updateMethodFields();

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (requestInProgress) return;

            requestInProgress = true;
            errorPanel.classList.add('d-none');
            submitButton.disabled = true;
            submitSpinner.classList.remove('d-none');

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const result = await response.json();

                if (!response.ok) {
                    showError(result.errors ? Object.values(result.errors).flat()[0] : (result.message || 'We could not start your payment.'));
                    return;
                }

                activePaymentReference = result.payment_ref;

                if (result.status === 'redirect' && result.checkout_url) {
                    window.location.assign(result.checkout_url);
                    return;
                }

                showWaiting(result.message || 'Approve the payment prompt on your phone. We are waiting for confirmation.');
                if (result.authorization_url) {
                    authorizationLink.href = result.authorization_url;
                    authorizationLink.classList.remove('d-none');
                }
                pollPayment();
            } catch (error) {
                showError('We could not connect to the payment service. Please try again.');
            } finally {
                requestInProgress = false;
                submitButton.disabled = false;
                submitSpinner.classList.add('d-none');
            }
        });

        if (activePaymentReference) {
            modal.show();
        }
    }

    function initializeCharts() {
        const definitions = [
            { id: 'gradeDistributionChart', type: 'bar', label: 'Assessment records', color: '#5470d5', integer: true },
            { id: 'courseAveragesChart', type: 'bar', label: 'Average score (%)', color: '#38a886', max: 100 },
            { id: 'studentsByProgrammeChart', type: 'bar', label: 'Students', color: '#8064c8', integer: true },
            { id: 'userRolesChart', type: 'doughnut', label: 'User accounts', colors: ['#5470d5', '#38a886', '#e3a84b', '#8064c8', '#d36b75'] },
        ];

        if (typeof Chart === 'undefined') {
            if (definitions.some(({ id }) => pageContent.querySelector(`#${id}`))) {
                showToast('warning', 'Charts unavailable', 'Check your internet connection and refresh the page.');
            }
            return;
        }

        definitions.forEach((definition) => {
            const canvas = pageContent.querySelector(`#${definition.id}`);
            if (!canvas || charts.has(definition.id)) return;

            const isDoughnut = definition.type === 'doughnut';
            charts.set(definition.id, new Chart(canvas, {
                type: definition.type,
                data: {
                    labels: JSON.parse(canvas.dataset.labels || '[]'),
                    datasets: [{
                        label: definition.label,
                        data: JSON.parse(canvas.dataset.values || '[]'),
                        backgroundColor: definition.colors || definition.color,
                        borderColor: isDoughnut ? '#fff' : definition.color,
                        borderWidth: isDoughnut ? 2 : 0,
                        borderRadius: isDoughnut ? 0 : 6,
                        maxBarThickness: 44,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: { legend: { display: isDoughnut, position: 'bottom', labels: { color: '#68758b', usePointStyle: true, padding: 16 } } },
                    scales: isDoughnut ? {} : {
                        x: {
                            grid: { display: false },
                            border: { display: false },
                            ticks: { color: '#8490a3', font: { size: 10 } },
                        },
                        y: {
                            beginAtZero: true,
                            max: definition.max,
                            border: { display: false },
                            grid: { color: '#eef1f6', drawTicks: false },
                            ticks: { precision: definition.integer ? 0 : 1, color: '#8490a3', padding: 8, font: { size: 10 } },
                        },
                    },
                },
            }));
        });
    }

    function updateDashboardCharts(chartsData) {
        const chartKeys = {
            gradeDistributionChart: 'gradeDistribution',
            courseAveragesChart: 'courseAverages',
            studentsByProgrammeChart: 'studentsByProgramme',
            userRolesChart: 'userRoles',
        };

        Object.entries(chartKeys).forEach(([chartId, dataKey]) => {
            const chart = charts.get(chartId);
            const items = chartsData?.[dataKey];
            if (!chart || !Array.isArray(items)) return;

            chart.data.labels = items.map((item) => item.label);
            chart.data.datasets[0].data = items.map((item) => Number(item.total));
            chart.update('none');
        });
    }

    function renderRecentGrades(grades) {
        const list = pageContent.querySelector('#recentGrades');
        if (!list || !Array.isArray(grades)) return;
        list.replaceChildren();

        if (grades.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'text-secondary mb-0';
            empty.textContent = 'No grade records are available yet.';
            list.append(empty);
            return;
        }

        grades.forEach((grade) => {
            const row = document.createElement('div');
            row.className = 'd-flex justify-content-between align-items-center border-bottom py-2';
            const course = document.createElement('span');
            const code = document.createElement('strong');
            code.textContent = grade.courseCode || 'Course';
            const title = document.createElement('small');
            title.className = 'd-block text-secondary';
            title.textContent = grade.courseTitle || '';
            course.append(code, title);
            const badge = document.createElement('span');
            badge.className = 'grade-badge';
            badge.textContent = grade.letterGrade || '—';
            row.append(course, badge);
            list.append(row);
        });
    }

    function renderRecentAuditLogs(logs) {
        const list = pageContent.querySelector('#recentAuditLogs');
        if (!list || !Array.isArray(logs)) return;
        list.replaceChildren();

        if (logs.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'text-secondary mb-0';
            empty.textContent = 'System activity will appear here after sensitive actions are recorded.';
            list.append(empty);
            return;
        }

        logs.forEach((log) => {
            const row = document.createElement('div');
            row.className = 'd-flex flex-wrap justify-content-between gap-2 border-bottom py-2';
            const details = document.createElement('span');
            const action = document.createElement('strong');
            action.textContent = log.action || 'Activity';
            const actor = document.createElement('small');
            actor.className = 'd-block text-secondary';
            actor.textContent = log.user || 'System';
            details.append(action, actor);
            const time = document.createElement('time');
            time.className = 'text-secondary';
            if (log.createdAt) {
                time.dateTime = log.createdAt;
                time.textContent = new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(log.createdAt));
            }
            row.append(details, time);
            list.append(row);
        });
    }

    async function refreshDashboardMetrics() {
        const dashboardStats = pageContent.querySelector('#dashboardStats');
        const metricsUrl = dashboardStats?.dataset.dashboardMetricsUrl;
        if (!metricsUrl) return;

        dashboardMetricsController?.abort();
        dashboardMetricsController = new AbortController();

        try {
            const response = await fetch(metricsUrl, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                cache: 'no-store',
                signal: dashboardMetricsController.signal,
            });
            if (!response.ok) return;
            const metrics = await response.json();

            Object.entries(metrics.stats || {}).forEach(([key, value]) => {
                const element = pageContent.querySelector(`[data-dashboard-stat="${key}"]`);
                if (element) element.textContent = key === 'averageScore' ? Number(value).toFixed(1) : Number(value).toLocaleString();
            });

            updateDashboardCharts(metrics.charts || {});
            renderRecentGrades(metrics.recentGrades);
            renderRecentAuditLogs(metrics.recentAuditLogs);

            const updatedAt = pageContent.querySelector('#dashboardUpdatedAt');
            if (updatedAt && metrics.updatedAt) {
                updatedAt.textContent = `Updated ${new Intl.DateTimeFormat(undefined, { hour: 'numeric', minute: '2-digit' }).format(new Date(metrics.updatedAt))}`;
            }
        } catch (error) {
            if (error.name !== 'AbortError') console.warn('Dashboard metrics could not be refreshed.', error);
        }
    }

    function initializeDashboardMetrics() {
        window.clearInterval(dashboardMetricsTimer);
        dashboardMetricsController?.abort();
        if (!pageContent.querySelector('#dashboardStats')) return;

        refreshDashboardMetrics();
        dashboardMetricsTimer = window.setInterval(refreshDashboardMetrics, 30000);
    }

    function updateActiveLink(url) {
        const currentUrl = new URL(url, window.location.href);
        const currentPath = currentUrl.pathname.replace(/\/+$/, '') || '/';

        document.querySelectorAll('.sidebar-link[data-page-link]').forEach((link) => {
            const linkPath = new URL(link.href, window.location.href).pathname.replace(/\/+$/, '') || '/';
            const isActive = linkPath === currentPath || currentPath.startsWith(`${linkPath}/`);
            link.classList.toggle('is-active', isActive);

            if (isActive) {
                link.setAttribute('aria-current', 'page');
            } else {
                link.removeAttribute('aria-current');
            }
        });
    }

    function showNavigationError() {
        showToast('error', 'Page unavailable', 'That page could not be loaded. Please try again.');
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

        if (!title) {
            throw new Error('The requested page did not return a navigation fragment.');
        }

        charts.forEach((chart) => chart.destroy());
        charts.clear();
        window.clearInterval(dashboardMetricsTimer);
        dashboardMetricsController?.abort();
        pageContent.innerHTML = html;

        pageTitle.textContent = title;
        document.title = `${title} | VU Student Portal`;

        updateActiveLink(url);

        if (addHistory) {
            window.history.pushState({}, '', url);
        }

        closeSidebar({ restoreFocus: false });

        window.scrollTo({ top: 0, behavior: 'smooth' });
        pageContent.focus({ preventScroll: true });
        initializeCharts();
        initializeDashboardMetrics();
        initializeAcademicFormFilters();
        initializePaymentModal();
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

    document.addEventListener('change', (event) => {
        if (
            event.target.matches(
                '[data-course-program-filter], [data-academic-course-filter], [data-faculty-filter], [data-department-filter]',
            )
        ) {
            initializeAcademicFormFilters();
        }
    });

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-editor-command]');

        if (!button) {
            return;
        }

        const editor = button.closest('.answer-editor')?.querySelector('[data-answer-editor]');

        if (!editor) {
            return;
        }

        editor.focus();
        document.execCommand(button.dataset.editorCommand, false);
    });

    document.addEventListener('mousedown', (event) => {
        if (event.target.closest('[data-editor-command]')) {
            event.preventDefault();
        }
    });

    document.addEventListener('submit', (event) => {
        const form = event.target.closest('[data-assessment-editor-form]');

        if (!form) {
            return;
        }

        const editor = form.querySelector('[data-answer-editor]');
        const answerContent = form.querySelector('[data-answer-content]');

        if (editor && answerContent) {
            answerContent.value = editor.innerHTML;
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
    initializeDashboardMetrics();
    initializeAcademicFormFilters();
    initializePaymentModal();
})();
