import { createIcons, LayoutDashboard, TriangleAlert, Siren, Users, UserCog, History, FileChartColumn, Settings, LogOut, User, Bell, Megaphone, House, Menu, Eye, Pencil, Trash2, Archive, ChevronLeft, ChevronRight } from 'lucide';
import Chart from 'chart.js/auto';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

const ready = () => {
    const theme = getComputedStyle(document.body);
    const themeColor = name => theme.getPropertyValue(name).trim();
    const palette = {
        primary: themeColor('--ui-primary') || '#15803d',
        secondary: themeColor('--ui-secondary') || '#16a34a',
        text: themeColor('--ui-text') || '#172b22',
        categories: ['--ui-chart-disaster','--ui-chart-emergency','--ui-chart-residents'].map((name, i) => {
            const val = themeColor(name);
            return (val && !val.startsWith('var(')) ? val : ['#ef4444', '#f59e0b', '#10b981'][i];
        })
    };
    const sidebar = document.querySelector('.sidebar,.dashboard-sidebar');
    if (sidebar) {
        sidebar.id = 'workspace-navigation';
        sidebar.inert = false;
        sidebar.querySelectorAll('a').forEach(link => {
            link.setAttribute('aria-label', link.textContent.trim());
            link.removeAttribute('title');
            if (link.classList.contains('active') || link.classList.contains('sidebar-item-active')) link.setAttribute('aria-current', 'page');
        });
    }
    const iconMap = {visibility:'eye',edit:'pencil',delete:'trash-2',archive:'archive',chevron_left:'chevron-left',chevron_right:'chevron-right',dashboard:'layout-dashboard',report_problem:'triangle-alert',notification_important:'siren',groups:'users',manage_accounts:'user-cog',history:'history',summarize:'file-chart-column',settings:'settings',logout:'log-out',person:'user',notifications:'bell',campaign:'megaphone',home:'house'};
    document.querySelectorAll('.sidebar .material-icons-round,.dashboard-sidebar .material-icons-round,.sidebar .material-symbols-outlined,.data-table .material-icons-round,.data-table .material-symbols-outlined,.inc-pagination .material-icons-round,.res-pagination .material-icons-round').forEach(el => {
        const name = iconMap[el.textContent.trim()];
        if (name) { const icon = document.createElement('i'); icon.dataset.lucide = name; icon.setAttribute('aria-hidden','true'); el.replaceWith(icon); }
    });
    createIcons({ icons: { LayoutDashboard, TriangleAlert, Siren, Users, UserCog, History, FileChartColumn, Settings, LogOut, User, Bell, Megaphone, House, Menu, Eye, Pencil, Trash2, Archive, ChevronLeft, ChevronRight } });
    document.querySelectorAll('button[title],a[title]').forEach(el => { if (!el.hasAttribute('aria-label')) el.setAttribute('aria-label', el.title); });
    // Reuse each rendered table and its headers; no duplicate mobile markup.
    document.querySelectorAll('.main-area table,.main-content table,main table').forEach(table => {
        // Formal report tables share their document layout between preview and print.
        if (table.closest('.report-preview-outer')) return;
        if (!table.tHead || !table.tBodies.length) return;
        table.classList.add('ui-record-table');
        table.setAttribute('role', 'table');
        let region = table.parentElement;
        if (region.querySelectorAll(':scope > table').length > 1 || region.classList.contains('report-breakdowns')) {
            region = document.createElement('div');
            table.before(region);
            region.append(table);
        }
        region.classList.add('ui-record-region');
        const headers = [...table.tHead.rows[0].cells].map(cell => cell.textContent.trim());
        if (headers.includes('Severity Level')) table.classList.add('ui-disaster-table');
        table.querySelectorAll('thead,tbody').forEach(group => group.setAttribute('role', 'rowgroup'));
        table.querySelectorAll('tr').forEach(row => row.setAttribute('role', 'row'));
        [...table.tHead.rows[0].cells].forEach(cell => cell.setAttribute('role', 'columnheader'));
        [...table.tBodies].forEach(body => [...body.rows].forEach(row => {
            let column = 0;
            [...row.cells].forEach((cell, index) => {
                cell.setAttribute('role', 'cell');
                if (cell.colSpan === 1) cell.dataset.label = headers[table.classList.contains('res-list-table') ? column : index] || '';
                column += cell.colSpan;
            });
        }));
    });
    document.querySelectorAll('table thead th').forEach(el => { if (!el.hasAttribute('scope')) el.scope = 'col'; });
    const main = document.querySelector('.main-area,.main-content,main');
    if (main) {
        main.id ||= 'workspace-content';
        main.tabIndex = -1;
        const skip = document.createElement('a'); skip.href = `#${main.id}`; skip.className = 'ui-skip'; skip.textContent = 'Skip to main content'; document.body.prepend(skip);
    }
    document.querySelectorAll('[data-record-chart]').forEach(canvas => {
        const payload = JSON.parse(canvas.dataset.recordChart);
        const doughnut = payload.type === 'doughnut';

        let chartData = payload.values;
        let chartColors = doughnut ? (payload.colors || palette.categories) : palette.secondary;
        const total = doughnut ? payload.values.reduce((a, b) => a + Number(b), 0) : 0;
        const isEmpty = doughnut && total === 0;

        if (isEmpty) {
            chartData = [1];
            chartColors = ['#e2e8f0'];
        }

        const plugins = [];
        if (doughnut) {
            plugins.push({
                id: 'doughnutCenterStats',
                beforeDraw(chart) {
                    const { ctx, chartArea } = chart;
                    if (!chartArea) return;
                    const { top, bottom, left, right } = chartArea;
                    const centerX = (left + right) / 2;
                    const centerY = (top + bottom) / 2;

                    ctx.save();
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';

                    // Total number
                    ctx.font = 'bold 24px "Public Sans", system-ui, sans-serif';
                    ctx.fillStyle = '#0f172a';
                    ctx.fillText(total.toLocaleString(), centerX, centerY - 8);

                    // Subtitle
                    ctx.font = 'bold 10px "Public Sans", system-ui, sans-serif';
                    ctx.fillStyle = '#64748b';
                    ctx.fillText('TOTAL RECORDS', centerX, centerY + 14);

                    ctx.restore();
                }
            });
        }

        new Chart(canvas, {
            type: payload.type,
            data: {
                labels: isEmpty ? ['No records'] : payload.labels,
                datasets: [{
                    label: 'Records',
                    data: chartData,
                    backgroundColor: chartColors,
                    borderWidth: doughnut ? 2 : 0,
                    borderColor: doughnut ? '#ffffff' : undefined,
                    borderRadius: doughnut ? 0 : 6,
                    maxBarThickness: 30
                }]
            },
            plugins: plugins,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: matchMedia('(prefers-reduced-motion: reduce)').matches ? false : { duration: 500 },
                cutout: doughnut ? '70%' : undefined,
                plugins: {
                    legend: {
                        display: doughnut && !isEmpty,
                        position: 'bottom',
                        labels: {
                            color: palette.text,
                            usePointStyle: true,
                            boxWidth: 8,
                            padding: 16,
                            font: { size: 12, weight: '500' },
                            generateLabels(chart) {
                                const data = chart.data;
                                if (data.labels && data.labels.length && data.datasets.length) {
                                    return data.labels.map((label, i) => {
                                        const val = payload.values[i] ?? 0;
                                        const pct = total > 0 ? Math.round((val / total) * 100) : 0;
                                        return {
                                            text: `${label}: ${val} (${pct}%)`,
                                            fillStyle: Array.isArray(chartColors) ? (chartColors[i] || palette.categories[i]) : chartColors,
                                            strokeStyle: '#ffffff',
                                            lineWidth: 1,
                                            hidden: false,
                                            index: i
                                        };
                                    });
                                }
                                return [];
                            }
                        }
                    },
                    tooltip: {
                        enabled: !isEmpty,
                        callbacks: {
                            label(context) {
                                if (doughnut) {
                                    const val = payload.values[context.dataIndex] ?? context.parsed;
                                    const pct = total > 0 ? Math.round((val / total) * 100) : 0;
                                    return ` ${context.label}: ${val} records (${pct}%)`;
                                }
                                return ` Records: ${context.parsed.y}`;
                            }
                        }
                    }
                },
                scales: doughnut ? {} : {
                    x: { grid: { display: false }, border: { display: false } },
                    y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f1f5f9' }, border: { display: false } }
                }
            }
        });
    });
    // Only opt-in tables: never replace server filtering or pagination.
    document.querySelectorAll('[data-ui-search-table]').forEach(table => {
        const label = document.createElement('label'); label.className = 'ui-table-search';
        label.append('Search this page');
        const input = document.createElement('input'); input.type = 'search'; input.placeholder = 'Search displayed activity…'; label.append(input);
        table.closest('.table-wrap').before(label);
        const status = document.createElement('span'); status.setAttribute('role','status'); label.append(status);
        input.addEventListener('input', () => {
            let matches = 0;
            table.querySelectorAll('tbody tr').forEach(row => { row.hidden = !row.textContent.toLowerCase().includes(input.value.toLowerCase()); if (!row.hidden) matches++; });
            status.textContent = `${matches} matching rows`;
        });
    });
    window.uiConfirm = async message => (await Swal.fire({title:'Confirm action',text:message,icon:'warning',showCancelButton:true,confirmButtonText:'Continue',cancelButtonText:'Cancel',confirmButtonColor:palette.primary,reverseButtons:true,focusCancel:true})).isConfirmed;
    document.addEventListener('submit', async event => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (form.matches('[data-profile-form],#changePasswordForm')) return;
        if (!form.noValidate && !form.checkValidity()) { event.preventDefault(); form.reportValidity(); return; }
        if (form.dataset.confirm && !form.dataset.uiConfirmed) {
            event.preventDefault(); event.stopImmediatePropagation();
            const submitter = event.submitter;
            if (await window.uiConfirm(form.dataset.confirm)) { form.dataset.uiConfirmed = 'true'; form.requestSubmit(submitter); delete form.dataset.uiConfirmed; }
            return;
        }
        // Visual state only: preserve submit button name/value and existing AJAX handlers.
        if (form.method.toLowerCase() === 'post') {
            const button = event.submitter;
            if (button) { button.setAttribute('aria-busy','true'); button.style.opacity = '.65'; setTimeout(() => {button.removeAttribute('aria-busy'); button.style.opacity='';},4000); }
        }
    }, true);
    window.uiNotify = (message, icon = 'info') => Swal.fire({text:message,icon,confirmButtonColor:palette.primary});
    window.showToast = (message, icon = 'success') => Swal.fire({toast:true,position:'top-end',text:message,icon,showConfirmButton:false,timer:4500,timerProgressBar:true});
    const feedback = JSON.parse(document.getElementById('ui-feedback')?.textContent || '{}');
    const kind = feedback.error ? 'error' : feedback.success ? 'success' : null;
    if (kind) Swal.fire({toast:true,position:'top-end',icon:kind,title:feedback[kind],showConfirmButton:false,timer:5000,timerProgressBar:true});
};
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ready); else ready();
