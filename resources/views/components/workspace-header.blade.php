<header class="topbar dashboard-header">
    @include('components.navigation-toggle')
    <div class="topbar-title flex-1 min-w-0">
        <p class="ui-eyebrow">DASHBOARD</p>
        <h2 class="text-xl md:text-2xl font-bold tracking-tight text-slate-800">
            WELCOME, <span class="text-emerald-700">{{ strtoupper(auth()->user()->name ?? 'ADMINISTRATOR') }}</span>
        </h2>
    </div>

    <div class="dashboard-header-right flex items-center gap-4 ml-auto flex-shrink-0">
        <div class="dashboard-clock-card text-right bg-emerald-50/80 border border-emerald-200/80 px-4 py-2 rounded-xl shadow-xs">
            <div id="dashboardRealtimeClock" class="font-mono text-base md:text-xl font-bold text-emerald-800 tracking-tight leading-none" aria-live="off">
                {{ now()->format('h:i:s A') }}
            </div>
            <div id="dashboardRealtimeDate" class="text-[11px] font-semibold text-slate-600 mt-1 leading-none">
                {{ now()->format('F j, Y - l') }}
            </div>
        </div>
    </div>

    <script>
        (function() {
            function updateDashboardClock() {
                var clockEl = document.getElementById('dashboardRealtimeClock');
                var dateEl = document.getElementById('dashboardRealtimeDate');
                if (!clockEl && !dateEl) return;
                var now = new Date();
                if (clockEl) {
                    var hours = now.getHours();
                    var minutes = now.getMinutes();
                    var seconds = now.getSeconds();
                    var ampm = hours >= 12 ? 'PM' : 'AM';
                    hours = hours % 12;
                    hours = hours ? hours : 12;
                    clockEl.textContent = (hours < 10 ? '0' + hours : hours) + ':' +
                                          (minutes < 10 ? '0' + minutes : minutes) + ':' +
                                          (seconds < 10 ? '0' + seconds : seconds) + ' ' + ampm;
                }
                if (dateEl) {
                    var days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                    var months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                    dateEl.textContent = months[now.getMonth()] + ' ' + now.getDate() + ', ' + now.getFullYear() + ' - ' + days[now.getDay()];
                }
            }
            setInterval(updateDashboardClock, 1000);
        })();
    </script>
</header>
