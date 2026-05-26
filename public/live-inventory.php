<?php
/**
 * Live Operations Dashboard – Smart Inventory Management System
 * Polished for display / presentation. No user interaction – just real‑time data.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Operations Room – SIMS</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Google Fonts (Inter) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --bg: #f4f7fe;
            --card-bg: #ffffff;
            --text: #2b3674;
            --text-muted: #a3aed1;
            --primary: #1366d9;
            --green: #55b38a;
            --yellow: #f59e0b;
            --red: #f35588;
            --border: #e9edf7;
            --radius: 16px;
        }

        body {
            background: var(--bg);
            font-family: 'Inter', sans-serif;
            color: var(--text);
            letter-spacing: -0.1px;
            overflow-x: hidden;
        }

        /* Premium Card */
        .card-premium {
            background: var(--card-bg);
            border: none;
            border-radius: var(--radius);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.03);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .card-premium:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.06);
        }

        /* KPI Value */
        .kpi-value {
            font-size: 2.4rem;
            font-weight: 800;
            line-height: 1.1;
            color: var(--text);
        }
        .kpi-value.primary {
            background: linear-gradient(135deg, var(--primary) 0%, #3b82f6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .kpi-label {
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-muted);
        }

        /* Stock Health Mini Cards */
        .health-mini {
            background: #f8fafc;
            border-radius: 12px;
            padding: 14px 12px;
            text-align: center;
            transition: all 0.2s;
        }
        .health-mini:hover {
            background: #fff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }

        .health-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }
        .health-dot.green { background: var(--green); }
        .health-dot.yellow { background: var(--yellow); }
        .health-dot.red { background: var(--red); }

        /* Chart Container */
        .chart-box {
            position: relative;
            height: 260px;
            width: 100%;
        }

        /* Table */
        .table-custom {
            border-collapse: separate;
            border-spacing: 0 6px;
        }
        .table-custom th {
            color: var(--text-muted);
            font-weight: 700;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
            padding: 12px 18px;
        }
        .table-custom td {
            background: #fff;
            padding: 12px 18px;
            border: none;
            font-size: 0.85rem;
            color: var(--text);
            vertical-align: middle;
        }
        .table-custom tbody tr {
            border-radius: 8px;
            transition: background 0.2s;
        }
        .table-custom tbody tr:hover td {
            background: #f8fafc;
        }

        .badge-in {
            background: rgba(85, 179, 138, 0.12);
            color: var(--green);
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
        }
        .badge-out {
            background: rgba(243, 85, 136, 0.10);
            color: var(--red);
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
        }

        /* Leaderboard Progress */
        .progress {
            height: 6px;
            background: #e9edf7;
            border-radius: 4px;
        }
        .progress-bar {
            border-radius: 4px;
        }

        /* Pulse indicator */
        .pulse-live {
            width: 10px;
            height: 10px;
            background: var(--green);
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 10px rgba(85, 179, 138, 0.5);
            animation: pulse-animation 2s infinite;
        }
        @keyframes pulse-animation {
            0% { box-shadow: 0 0 0 0 rgba(85, 179, 138, 0.7); }
            70% { box-shadow: 0 0 0 8px rgba(85, 179, 138, 0); }
            100% { box-shadow: 0 0 0 0 rgba(85, 179, 138, 0); }
        }

        /* Sync button */
        .btn-sync {
            background: #fff;
            border: 1px solid var(--border);
            color: var(--text);
            font-weight: 600;
            padding: 8px 18px;
            border-radius: 8px;
            font-size: 0.8rem;
        }
        .btn-sync:hover {
            background: rgba(19, 102, 217, 0.05);
            color: var(--primary);
            border-color: rgba(19, 102, 217, 0.2);
        }

        /* Data update transition */
        .fade-in {
            animation: fadeIn 0.4s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0.4; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive */
        @media (max-width: 576px) {
            .kpi-value { font-size: 1.8rem; }
        }
    </style>
</head>
<body>

<div class="container-fluid py-4 px-4" style="max-width: 1600px;">

    <!-- Header -->
    <div class="card-premium p-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center justify-content-center rounded-3" style="width:50px; height:50px; background: rgba(19,102,217,0.1); color: var(--primary);">
                    <i class="bi bi-activity fs-4"></i>
                </div>
                <div>
                    <h4 class="fw-bold mb-0">Live Operations Room</h4>
                    <p class="text-muted mb-0" style="font-size:0.85rem;">Real‑time inventory throughput dashboard</p>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center gap-2 bg-light px-3 py-2 rounded-pill border">
                    <span class="pulse-live" id="pulseIndicator"></span>
                    <span class="fw-semibold" style="font-size:0.75rem;">SYSTEM LIVE</span>
                </div>
                <button id="btnRefreshLive" class="btn-sync">
                    <i class="bi bi-arrow-clockwise me-1"></i> Sync
                </button>
            </div>
        </div>
    </div>

    <!-- KPI Row -->
    <div class="row g-4 mb-4">
        <!-- Valuation -->
        <div class="col-lg-4 col-md-6 col-12">
            <div class="card-premium p-4 h-100">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:42px; height:42px; background: rgba(19,102,217,0.1); color: var(--primary);">
                        <i class="bi bi-cash-stack fs-5"></i>
                    </div>
                    <span class="kpi-label">Total Valuation</span>
                </div>
                <div class="kpi-value primary fade-in" id="valTotalValue">ETB 0.00</div>
            </div>
        </div>
        <!-- Units -->
        <div class="col-lg-4 col-md-6 col-12">
            <div class="card-premium p-4 h-100">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:42px; height:42px; background: rgba(85,179,138,0.1); color: var(--green);">
                        <i class="bi bi-boxes fs-5"></i>
                    </div>
                    <span class="kpi-label">Active Stock Units</span>
                </div>
                <div class="kpi-value fade-in" id="valTotalUnits">0</div>
            </div>
        </div>
        <!-- Health -->
        <div class="col-lg-4 col-12">
            <div class="card-premium p-4 h-100">
                <span class="kpi-label mb-3 d-block">Stock Health</span>
                <div class="row g-2">
                    <div class="col-4">
                        <div class="health-mini">
                            <div class="fw-bold" style="font-size:1.5rem;" id="valHealthGreen">0</div>
                            <small class="text-muted">Healthy</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="health-mini">
                            <div class="fw-bold" style="font-size:1.5rem;" id="valHealthYellow">0</div>
                            <small class="text-muted">Low</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="health-mini">
                            <div class="fw-bold" style="font-size:1.5rem;" id="valHealthRed">0</div>
                            <small class="text-muted">Out</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6 col-12">
            <div class="card-premium p-4 h-100">
                <div class="d-flex align-items-center gap-2 mb-4">
                    <i class="bi bi-pie-chart-fill text-primary fs-5"></i>
                    <h6 class="fw-bold mb-0">Category Distribution</h6>
                </div>
                <div class="chart-box">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-12">
            <div class="card-premium p-4 h-100">
                <div class="d-flex align-items-center gap-2 mb-4">
                    <i class="bi bi-graph-up text-green fs-5"></i>
                    <h6 class="fw-bold mb-0">Daily Activity Volume</h6>
                </div>
                <div class="chart-box">
                    <canvas id="lineChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom: Feed + Leaderboard -->
    <div class="row g-4">
        <!-- Activity Feed -->
        <div class="col-lg-8 col-12">
            <div class="card-premium p-0 h-100">
                <div class="p-4 pb-3 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-clock-history text-primary fs-5"></i>
                        <h6 class="fw-bold mb-0">Operations Feed</h6>
                    </div>
                </div>
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-custom w-100 mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Timestamp</th>
                                <th>Product</th>
                                <th class="text-center">Type</th>
                                <th class="text-end pe-4">Quantity</th>
                            </tr>
                        </thead>
                        <tbody id="recentActivityBody">
                            <!-- Skeleton loader rows will be replaced -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Top Products -->
        <div class="col-lg-4 col-12">
            <div class="card-premium p-4 h-100">
                <div class="d-flex align-items-center gap-2 mb-4 border-bottom pb-3">
                    <i class="bi bi-trophy-fill text-warning fs-5"></i>
                    <h6 class="fw-bold mb-0">Top Moving</h6>
                </div>
                <div id="topProductsBody" class="d-flex flex-column gap-3">
                    <!-- Leaderboard skeleton -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Chart instances
    let categoryChartInstance = null;
    let lineChartInstance = null;

    const formatCurrency = (val) => 'ETB ' + new Intl.NumberFormat('en-US').format(val);
    const formatDate = (dateString) => {
        const d = new Date(dateString);
        return d.toLocaleDateString('en-US', { month: 'short', day: '2-digit' }) + ' - ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    };

    function initCharts() {
        Chart.defaults.color = '#a3aed1';
        Chart.defaults.font.family = 'Inter';

        const catCtx = document.getElementById('categoryChart').getContext('2d');
        categoryChartInstance = new Chart(catCtx, {
            type: 'doughnut',
            data: { labels: [], datasets: [{ data: [], backgroundColor: ['#1366d9', '#55b38a', '#f59e0b', '#f35588', '#06b6d4', '#8b5cf6', '#ec4899'], borderWidth: 2, borderColor: '#ffffff' }] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { font: { size: 12, family: 'Inter' }, padding: 15, color: '#2b3674' }
                    }
                },
                cutout: '72%'
            }
        });

        const lineCtx = document.getElementById('lineChart').getContext('2d');
        lineChartInstance = new Chart(lineCtx, {
            type: 'line',
            data: { labels: [], datasets: [] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                elements: { line: { tension: 0.3, borderWidth: 3 }, point: { radius: 4, hitRadius: 10, backgroundColor: '#ffffff', borderWidth: 2 } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#e9edf7' }, ticks: { font: { size: 11 }, color: '#a3aed1' } },
                    x: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#a3aed1' } }
                },
                plugins: { legend: { display: false } }
            }
        });
    }

    let lastDataHash = '';

    async function fetchLiveData() {
        try {
            const pulse = document.getElementById('pulseIndicator');
            pulse.style.background = 'var(--primary)';
            pulse.style.boxShadow = '0 0 12px rgba(19,102,217,0.4)';

            const response = await fetch('../api/live_data.php');
            const textData = await response.text();

            pulse.style.background = 'var(--green)';
            pulse.style.boxShadow = '0 0 10px rgba(85,179,138,0.5)';

            if (textData === lastDataHash) return;
            lastDataHash = textData;
            const data = JSON.parse(textData);

            // KPI updates with fade animation
            const animateValue = (el, newValue) => {
                if (el.innerText !== newValue) {
                    el.classList.remove('fade-in');
                    void el.offsetWidth; // trigger reflow
                    el.classList.add('fade-in');
                    el.innerText = newValue;
                }
            };

            animateValue(document.getElementById('valTotalValue'), formatCurrency(data.metrics.total_value));
            animateValue(document.getElementById('valTotalUnits'), data.metrics.total_units.toLocaleString());
            animateValue(document.getElementById('valHealthGreen'), data.health.green);
            animateValue(document.getElementById('valHealthYellow'), data.health.yellow);
            animateValue(document.getElementById('valHealthRed'), data.health.red);

            // Category chart
            if (data.charts.category.labels.length > 0) {
                categoryChartInstance.data.labels = data.charts.category.labels;
                categoryChartInstance.data.datasets[0].data = data.charts.category.data;
                categoryChartInstance.update();
            }

            // Line chart
            if (data.charts.line.labels.length > 0) {
                lineChartInstance.data.labels = data.charts.line.labels;
                lineChartInstance.data.datasets = [{
                    label: 'Transaction Volume',
                    data: data.charts.line.data,
                    borderColor: '#1366d9',
                    backgroundColor: 'rgba(19, 102, 217, 0.06)',
                    fill: true
                }];
                lineChartInstance.update();
            }

            // Activity feed
            const feed = document.getElementById('recentActivityBody');
            feed.innerHTML = '';
            if (!data.recent_activity || data.recent_activity.length === 0) {
                feed.innerHTML = `<tr><td colspan="4" class="text-center py-5 text-muted"><i class="bi bi-inbox d-block fs-3 mb-2"></i>No movements recorded yet</td></tr>`;
            } else {
                data.recent_activity.forEach(item => {
                    const isOut = item.movement_type === 'OUT';
                    feed.innerHTML += `
                        <tr>
                            <td class="ps-4 text-muted small">${formatDate(item.created_at)}</td>
                            <td class="fw-semibold">${item.product_name}</td>
                            <td class="text-center"><span class="${isOut ? 'badge-out' : 'badge-in'}">${isOut ? 'OUT' : 'IN'}</span></td>
                            <td class="text-end pe-4 fw-bold ${isOut ? 'text-red' : 'text-success'}">${isOut ? '-' : '+'}${parseInt(item.quantity).toLocaleString()}</td>
                        </tr>
                    `;
                });
            }

            // Top products leaderboard
            const topDiv = document.getElementById('topProductsBody');
            topDiv.innerHTML = '';
            if (!data.top_moving || data.top_moving.length === 0) {
                topDiv.innerHTML = `<div class="text-center py-5 text-muted"><i class="bi bi-trophy d-block fs-3 mb-2"></i>No sales data yet</div>`;
            } else {
                const maxOut = Math.max(...data.top_moving.map(i => parseInt(i.total_out) || 1));
                const colors = ['bg-primary', 'bg-success', 'bg-info', 'bg-warning', 'bg-secondary'];
                data.top_moving.forEach((item, idx) => {
                    const percent = Math.round((parseInt(item.total_out) / maxOut) * 100);
                    const color = colors[idx] || 'bg-secondary';
                    topDiv.innerHTML += `
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-semibold" style="font-size:0.85rem;">${item.name}</span>
                                <span class="text-muted small">${parseInt(item.total_out).toLocaleString()} units</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar ${color}" style="width: ${percent}%;"></div>
                            </div>
                        </div>
                    `;
                });
            }

        } catch (error) {
            console.error('Live sync error:', error);
            document.getElementById('pulseIndicator').style.background = 'var(--red)';
        }
    }

    document.getElementById('btnRefreshLive').addEventListener('click', () => {
        lastDataHash = '';
        fetchLiveData();
    });

    document.addEventListener('DOMContentLoaded', () => {
        initCharts();
        fetchLiveData();
        setInterval(fetchLiveData, 5000);
    });
</script>

</body>
</html>