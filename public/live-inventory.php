<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Operations Dashboard - Smart Inventory</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Google Fonts (Inter) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --bg-color: #030712; /* Deep Obsidian Black */
            --card-bg: #0b0f19; /* Dense Slate Card */
            --text-main: #f3f4f6; /* Off-White Main */
            --text-muted: #6b7280; /* Neutral Muted */
            --accent-green: #10b981; /* Emerald Green */
            --accent-yellow: #f59e0b; /* Amber Amber */
            --accent-red: #f43f5e; /* Rose Red */
            --accent-blue: #3b82f6; /* Indigo Blue */
            --border-subtle: rgba(255, 255, 255, 0.05);
        }

        body { 
            background-color: var(--bg-color); 
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            letter-spacing: -0.1px;
            overflow-x: hidden;
        }

        /* Premium SaaS Card Styling */
        .card-custom { 
            background-color: var(--card-bg); 
            border: 1px solid var(--border-subtle); 
            border-radius: 16px; 
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5); 
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s ease;
        }
        
        .card-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.6);
            border-color: rgba(255, 255, 255, 0.1);
        }

        .card-title-custom {
            color: var(--text-muted);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 700;
        }

        /* Shimmer Animation loading slots */
        .shimmer-bg {
            background: linear-gradient(90deg, rgba(255, 255, 255, 0.02) 25%, rgba(255, 255, 255, 0.08) 50%, rgba(255, 255, 255, 0.02) 75%);
            background-size: 200% 100%;
            animation: shimmer-pulse 1.8s infinite ease-in-out;
        }
        @keyframes shimmer-pulse {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        .skeleton-text {
            height: 12px;
            border-radius: 4px;
            display: inline-block;
        }
        .skeleton-badge {
            height: 20px;
            width: 60px;
            border-radius: 12px;
            display: inline-block;
        }

        /* Valuation & Hero Typography */
        .stat-value-custom { 
            font-size: 2.8rem; 
            font-weight: 800; 
            line-height: 1.1; 
            background: linear-gradient(135deg, #ffffff 0%, #9ca3af 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-value-glow {
            background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Health circles & indicator bars */
        .health-circle { 
            display: inline-block; 
            width: 10px; 
            height: 10px; 
            border-radius: 50%; 
            margin-right: 6px; 
            box-shadow: 0 0 8px currentColor;
        }
        
        .text-green { color: var(--accent-green) !important; }
        .text-yellow { color: var(--accent-yellow) !important; }
        .text-red { color: var(--accent-red) !important; }
        
        .bg-green { background-color: var(--accent-green); color: var(--accent-green); }
        .bg-yellow { background-color: var(--accent-yellow); color: var(--accent-yellow); }
        .bg-red { background-color: var(--accent-red); color: var(--accent-red); }

        .chart-container-custom { 
            position: relative; 
            height: 280px; 
            width: 100%; 
        }
        
        /* Table Layouts */
        .table-custom { 
            color: var(--text-main); 
        }
        .table-custom th { 
            background-color: rgba(255, 255, 255, 0.02); 
            color: var(--text-muted); 
            border-bottom: 1px solid var(--border-subtle);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 1px;
            padding: 14px 18px;
        }
        .table-custom td {
            padding: 14px 18px;
            border-bottom: 1px solid var(--border-subtle);
            font-size: 13.5px;
        }
        .table-custom-hover tbody tr {
            transition: background-color 0.2s ease;
        }
        .table-custom-hover tbody tr:hover { 
            background-color: rgba(255, 255, 255, 0.02); 
        }
        
        /* Translucent Badges */
        .badge-in { 
            background-color: rgba(16, 185, 129, 0.1); 
            color: var(--accent-green); 
            border: 1px solid rgba(16, 185, 129, 0.2);
            padding: 4px 10px; 
            font-size: 10px; 
            font-weight: 600; 
        }
        .badge-out { 
            background-color: rgba(244, 63, 94, 0.1); 
            color: var(--accent-red); 
            border: 1px solid rgba(244, 63, 94, 0.2);
            padding: 4px 10px; 
            font-size: 10px; 
            font-weight: 600; 
        }

        /* Glowing Pulse Indicators */
        .pulse-indicator {
            display: inline-block;
            width: 9px;
            height: 9px;
            background-color: var(--accent-green);
            border-radius: 50%;
            box-shadow: 0 0 10px var(--accent-green);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        .btn-refresh-custom {
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            border: 1px solid var(--border-subtle);
            padding: 8px 18px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .btn-refresh-custom:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border-color: rgba(255, 255, 255, 0.2);
        }

        /* Custom scrollbar for operations feed */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.01);
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body>

<div class="container-fluid py-4 px-4" style="max-width: 1600px;">
    <!-- Top Welcome & Brand Banner -->
    <div class="card-custom p-4 mb-4" style="background: linear-gradient(135deg, #0b0f19 0%, #030712 100%);">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center align-items-start gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center justify-content-center shadow-sm" 
                     style="width: 50px; height: 50px; border-radius: 12px; background: linear-gradient(135deg, #3b82f6 0%, #4f46e5 100%); color: white; font-weight: 800; font-size: 18px; letter-spacing: 0.5px;">
                    LI
                </div>
                <div>
                    <h3 class="fw-bold text-main mb-0" style="letter-spacing: -0.5px;">Live Operations Room</h3>
                    <p class="text-muted mb-0" style="font-size: 12px;">Executive warehouse sales throughput and inventory valuations dashboard.</p>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center gap-2 text-muted bg-dark rounded-pill px-3 py-1.5 border border-secondary border-opacity-10" style="font-size: 12px;">
                    <span class="pulse-indicator" id="pulseIndicator"></span>
                    <span class="fw-semibold" style="letter-spacing: 0.5px;">SYSTEM LIVE</span>
                </div>
                <button id="btnRefreshLive" class="btn btn-refresh-custom">
                    <i class="bi bi-arrow-clockwise"></i> Refresh Sync
                </button>
            </div>
        </div>
    </div>

    <!-- Top KPI Cards Grid -->
    <div class="row g-4 mb-4">
        <!-- Valuation -->
        <div class="col-lg-4 col-md-6 col-12">
            <div class="card-custom h-100 p-4" style="background: linear-gradient(145deg, #0b0f19, #050811); border-left: 4px solid var(--accent-blue);">
                <span class="card-title-custom d-block mb-2">Total Inventory Valuation</span>
                <div class="d-flex align-items-center gap-3 mt-1">
                    <i class="bi bi-cash-stack text-primary fs-3"></i>
                    <div class="stat-value-custom stat-value-glow" id="valTotalValue">$0.00</div>
                </div>
            </div>
        </div>
        <!-- Units -->
        <div class="col-lg-4 col-md-6 col-12">
            <div class="card-custom h-100 p-4" style="background: linear-gradient(145deg, #0b0f19, #050811); border-left: 4px solid var(--accent-green);">
                <span class="card-title-custom d-block mb-2">Total Active Stock Units</span>
                <div class="d-flex align-items-center gap-3 mt-1">
                    <i class="bi bi-box-seam text-green fs-3"></i>
                    <div class="stat-value-custom" id="valTotalUnits">0</div>
                </div>
            </div>
        </div>
        <!-- Health Breakdown -->
        <div class="col-lg-4 col-12">
            <div class="card-custom h-100 p-4">
                <span class="card-title-custom d-block mb-3">Warehouse Stock Health Indicators</span>
                <div class="d-flex justify-content-between align-items-center mt-2 px-1">
                    <!-- Healthy -->
                    <div class="text-center bg-dark bg-opacity-50 rounded-3 p-2 border border-secondary border-opacity-10" style="flex-grow: 1; margin: 0 4px;">
                        <div class="d-flex align-items-center justify-content-center mb-1 gap-1">
                            <span class="health-circle bg-green"></span>
                            <span class="fw-bold text-green fs-4" id="valHealthGreen">0</span>
                        </div>
                        <div style="font-size: 10px; color: var(--text-muted); font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase;">Healthy (10+)</div>
                    </div>
                    <!-- Low -->
                    <div class="text-center bg-dark bg-opacity-50 rounded-3 p-2 border border-secondary border-opacity-10" style="flex-grow: 1; margin: 0 4px;">
                        <div class="d-flex align-items-center justify-content-center mb-1 gap-1">
                            <span class="health-circle bg-yellow"></span>
                            <span class="fw-bold text-yellow fs-4" id="valHealthYellow">0</span>
                        </div>
                        <div style="font-size: 10px; color: var(--text-muted); font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase;">Low Stock (&lt;10)</div>
                    </div>
                    <!-- Out -->
                    <div class="text-center bg-dark bg-opacity-50 rounded-3 p-2 border border-secondary border-opacity-10" style="flex-grow: 1; margin: 0 4px;">
                        <div class="d-flex align-items-center justify-content-center mb-1 gap-1">
                            <span class="health-circle bg-red"></span>
                            <span class="fw-bold text-red fs-4" id="valHealthRed">0</span>
                        </div>
                        <div style="font-size: 10px; color: var(--text-muted); font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase;">Out of Stock</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">
        <!-- Category Distribution -->
        <div class="col-lg-6 col-12">
            <div class="card-custom p-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-pie-chart text-primary fs-5"></i>
                    <h5 class="fw-bold text-main mb-0" style="font-size: 15px;">Stock Category Capacity Distribution</h5>
                </div>
                <div class="chart-container-custom">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
        <!-- Trend Activity -->
        <div class="col-lg-6 col-12">
            <div class="card-custom p-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-graph-up text-green fs-5"></i>
                    <h5 class="fw-bold text-main mb-0" style="font-size: 15px;">Daily Activity Volume (7 Days Trend)</h5>
                </div>
                <div class="chart-container-custom">
                    <canvas id="lineChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Split: Live Activity Feed & Top Products Leaderboard -->
    <div class="row g-4">
        <!-- Live Activity Feed Table -->
        <div class="col-lg-8 col-12">
            <div class="card-custom h-100">
                <div class="d-flex align-items-center gap-2 p-4 pb-3 border-bottom" style="border-color: var(--border-subtle) !important;">
                    <i class="bi bi-activity text-danger fs-5"></i>
                    <h5 class="fw-bold text-main mb-0" style="font-size: 15px;">Chronological Operations Feed</h5>
                </div>
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-custom table-custom-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4" style="width: 25%;">Timestamp</th>
                                <th>Product Details</th>
                                <th class="text-center" style="width: 20%;">Type</th>
                                <th class="text-end pe-4" style="width: 20%;">Qty Amount</th>
                            </tr>
                        </thead>
                        <tbody id="recentActivityBody">
                            <!-- Shimmering Skeleton Loader Rows -->
                            <tr>
                                <td class="ps-4"><span class="skeleton-text shimmer-bg" style="width: 70%;"></span></td>
                                <td><span class="skeleton-text shimmer-bg" style="width: 50%;"></span></td>
                                <td class="text-center"><span class="skeleton-badge shimmer-bg"></span></td>
                                <td class="text-end pe-4"><span class="skeleton-text shimmer-bg" style="width: 40px;"></span></td>
                            </tr>
                            <tr>
                                <td class="ps-4"><span class="skeleton-text shimmer-bg" style="width: 65%;"></span></td>
                                <td><span class="skeleton-text shimmer-bg" style="width: 45%;"></span></td>
                                <td class="text-center"><span class="skeleton-badge shimmer-bg"></span></td>
                                <td class="text-end pe-4"><span class="skeleton-text shimmer-bg" style="width: 35px;"></span></td>
                            </tr>
                            <tr>
                                <td class="ps-4"><span class="skeleton-text shimmer-bg" style="width: 75%;"></span></td>
                                <td><span class="skeleton-text shimmer-bg" style="width: 55%;"></span></td>
                                <td class="text-center"><span class="skeleton-badge shimmer-bg"></span></td>
                                <td class="text-end pe-4"><span class="skeleton-text shimmer-bg" style="width: 45px;"></span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Top Products Leaderboard Column -->
        <div class="col-lg-4 col-12">
            <div class="card-custom h-100 p-4">
                <div class="d-flex align-items-center gap-2 mb-3 border-bottom pb-3" style="border-color: var(--border-subtle) !important;">
                    <i class="bi bi-trophy text-yellow fs-5"></i>
                    <h5 class="fw-bold text-main mb-0" style="font-size: 15px;">Top Moving Products Leaderboard</h5>
                </div>
                <div class="d-flex flex-column" id="topProductsBody">
                    <!-- Shimmering Skeleton Loader Lists -->
                    <div class="d-flex flex-column mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="skeleton-text shimmer-bg" style="width: 60%; height: 14px;"></span>
                            <span class="skeleton-text shimmer-bg" style="width: 25%;"></span>
                        </div>
                        <div class="shimmer-bg" style="height: 6px; border-radius: 4px; width: 100%;"></div>
                    </div>
                    <div class="d-flex flex-column mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="skeleton-text shimmer-bg" style="width: 50%; height: 14px;"></span>
                            <span class="skeleton-text shimmer-bg" style="width: 20%;"></span>
                        </div>
                        <div class="shimmer-bg" style="height: 6px; border-radius: 4px; width: 100%;"></div>
                    </div>
                    <div class="d-flex flex-column mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="skeleton-text shimmer-bg" style="width: 55%; height: 14px;"></span>
                            <span class="skeleton-text shimmer-bg" style="width: 22%;"></span>
                        </div>
                        <div class="shimmer-bg" style="height: 6px; border-radius: 4px; width: 100%;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JS Controller Logic -->
<script>
    // Chart instances
    let categoryChartInstance = null;
    let lineChartInstance = null;

    // Formatting tools
    const formatCurrency = (val) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(val);
    const formatDate = (dateString) => {
        const d = new Date(dateString);
        return d.toLocaleDateString('en-US', { month: 'short', day: '2-digit' }) + ' - ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    };

    // Initialize charts with clean slate styling config
    function initCharts() {
        Chart.defaults.color = '#9ca3af';
        Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.05)';

        // Category capacity distribution donut chart
        const catCtx = document.getElementById('categoryChart').getContext('2d');
        categoryChartInstance = new Chart(catCtx, {
            type: 'doughnut',
            data: { 
                labels: [], 
                datasets: [{ 
                    data: [], 
                    backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#f43f5e', '#06b6d4', '#8b5cf6', '#ec4899'], 
                    borderWidth: 2,
                    borderColor: '#0b0f19'
                }] 
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                plugins: { 
                    legend: { 
                        position: 'right', 
                        labels: { 
                            font: { size: 12, family: 'Inter', weight: '500' },
                            padding: 15
                        } 
                    } 
                },
                cutout: '72%'
            }
        });

        // 7-day movement line chart
        const lineCtx = document.getElementById('lineChart').getContext('2d');
        lineChartInstance = new Chart(lineCtx, {
            type: 'line',
            data: { labels: [], datasets: [] },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                elements: { 
                    line: { tension: 0.15, borderWidth: 3 }, 
                    point: { radius: 4, hitRadius: 10 } 
                }, 
                scales: { 
                    y: { 
                        beginAtZero: true, 
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        ticks: { font: { family: 'Inter', size: 10 } }
                    },
                    x: { 
                        grid: { display: false },
                        ticks: { font: { family: 'Inter', size: 10 } }
                    }
                },
                plugins: { legend: { display: false } }
            }
        });
    }

    let lastDataHash = '';

    // Fetch and sync dashboard metrics
    async function fetchLiveData() {
        try {
            // Pulsing blue lock while syncing
            const pulse = document.getElementById('pulseIndicator');
            pulse.style.backgroundColor = '#3b82f6';
            pulse.style.boxShadow = '0 0 10px #3b82f6';
            
            const response = await fetch('api/live_data.php');
            const textData = await response.text();
            
            pulse.style.backgroundColor = 'var(--accent-green)';
            pulse.style.boxShadow = '0 0 10px var(--accent-green)';

            // Smart sync: don't re-render unless hash modified
            if (textData === lastDataHash) {
                console.log('Operational room stats unchanged. Skipping render cycle.');
                return;
            }
            
            console.log('Inventory state change recognized. Redrawing dashboard layout.');
            lastDataHash = textData;
            const data = JSON.parse(textData);

            // 1. Render valuations
            document.getElementById('valTotalValue').innerText = formatCurrency(data.metrics.total_value);
            document.getElementById('valTotalUnits').innerText = data.metrics.total_units.toLocaleString();
            
            // 2. Render health circles
            document.getElementById('valHealthGreen').innerText = data.health.green;
            document.getElementById('valHealthYellow').innerText = data.health.yellow;
            document.getElementById('valHealthRed').innerText = data.health.red;

            // 3. Render categories donut
            if (data.charts.category.labels.length > 0) {
                categoryChartInstance.data.labels = data.charts.category.labels;
                categoryChartInstance.data.datasets[0].data = data.charts.category.data;
                categoryChartInstance.update();
            }

            // 4. Render daily activity curves
            if (data.charts.line.labels.length > 0) {
                lineChartInstance.data.labels = data.charts.line.labels;
                lineChartInstance.data.datasets = [{
                    label: 'Transaction Volume',
                    data: data.charts.line.data,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.05)',
                    fill: true
                }];
                lineChartInstance.update();
            }

            // 5. Render Chronological Feed rows
            const recentBody = document.getElementById('recentActivityBody');
            recentBody.innerHTML = '';
            if (data.recent_activity.length === 0) {
                recentBody.innerHTML = '<tr><td colspan="4" class="text-center py-5 text-muted"><i class="bi bi-clock-history d-block fs-3 mb-2"></i>No movements logged yet</td></tr>';
            } else {
                data.recent_activity.forEach(item => {
                    const isOut = item.movement_type === 'OUT';
                    const badgeClass = isOut ? 'badge-out' : 'badge-in';
                    const icon = isOut ? '<i class="bi bi-arrow-up-right"></i>' : '<i class="bi bi-arrow-down-left"></i>';
                    
                    recentBody.innerHTML += `
                        <tr>
                            <td class="ps-4 text-muted">${formatDate(item.created_at)}</td>
                            <td class="fw-bold text-main">${item.product_name}</td>
                            <td class="text-center"><span class="badge ${badgeClass} rounded-pill">${icon} ${item.movement_type}</span></td>
                            <td class="text-end pe-4 fw-bold ${isOut ? 'text-red' : 'text-green'}" style="font-size: 15px;">${isOut ? '-' : '+'}${parseInt(item.quantity).toLocaleString()}</td>
                        </tr>
                    `;
                });
            }

            // 6. Render Top Products progress bar Leaderboard
            const topBody = document.getElementById('topProductsBody');
            topBody.innerHTML = '';
            if (!data.top_moving || data.top_moving.length === 0) {
                topBody.innerHTML = '<div class="text-center py-5 text-muted"><i class="bi bi-trophy d-block fs-3 mb-2"></i>No sales throughput recorded yet</div>';
            } else {
                const maxOut = Math.max(...data.top_moving.map(item => parseInt(item.total_out) || 1));
                const badgeColors = ['bg-primary', 'bg-success', 'bg-info', 'bg-warning', 'bg-secondary'];
                
                data.top_moving.forEach((item, index) => {
                    const percent = Math.min(100, Math.round((parseInt(item.total_out) / maxOut) * 100));
                    const colorClass = badgeColors[index] || 'bg-secondary';
                    
                    topBody.innerHTML += `
                        <div class="d-flex flex-column mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold d-flex align-items-center gap-2" style="font-size: 13.5px;">
                                    <span class="badge ${colorClass} rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 20px; height: 20px; font-size: 9px; padding: 0;">${index + 1}</span>
                                    <span class="text-main">${item.name}</span>
                                </span>
                                <span class="fw-bold text-muted" style="font-size: 12.5px;">${parseInt(item.total_out).toLocaleString()} units</span>
                            </div>
                            <div class="progress" style="height: 6px; background-color: rgba(255,255,255,0.04); border-radius: 4px;">
                                <div class="progress-bar ${colorClass}" role="progressbar" style="width: ${percent}%; border-radius: 4px;"></div>
                            </div>
                        </div>
                    `;
                });
            }

        } catch (error) {
            console.error('Operations room sync failure:', error);
            document.getElementById('pulseIndicator').style.backgroundColor = 'var(--accent-red)';
            document.getElementById('pulseIndicator').style.boxShadow = '0 0 10px var(--accent-red)';
        }
    }

    // Manual Refresh Trigger
    document.getElementById('btnRefreshLive').addEventListener('click', (e) => {
        const btn = e.currentTarget;
        const origContent = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Syncing...';
        btn.disabled = true;
        lastDataHash = ''; // Reset hash to force UI redrawing
        fetchLiveData().then(() => {
            setTimeout(() => { 
                btn.innerHTML = origContent; 
                btn.disabled = false;
            }, 600);
        });
    });

    // Initialize and start operation polling cycles
    document.addEventListener('DOMContentLoaded', () => {
        initCharts();
        fetchLiveData(); // Fire initial fetch
        setInterval(fetchLiveData, 5000); // Poll feed every 5 seconds
    });
</script>

</body>
</html>
