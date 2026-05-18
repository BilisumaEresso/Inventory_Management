<?php
/**
 * Live Operations Dashboard - Smart Inventory Management System
 * Synchronized with the SIMS light/clean design system.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Operations Dashboard - SIMS</title>
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
            --bg-color: #f4f7fe; /* Light gray background */
            --card-bg: #ffffff; /* White cards */
            --text-dark: #2b3674; /* Navy text */
            --text-muted: #a3aed1; /* Gray text */
            --primary-blue: #1366d9; /* SIMS primary blue */
            --accent-green: #55b38a; /* SIMS green */
            --accent-yellow: #f59e0b; /* Amber */
            --accent-red: #f35588; /* SIMS red */
            --border-subtle: #e9edf7;
        }

        body { 
            background-color: var(--bg-color); 
            color: var(--text-dark);
            font-family: 'Inter', sans-serif;
            letter-spacing: -0.1px;
            overflow-x: hidden;
        }

        /* SIMS Standard Card Styling */
        .card-custom { 
            background-color: var(--card-bg); 
            border: none; 
            border-radius: 16px; 
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03); 
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .card-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.06);
        }

        .card-title-custom {
            color: var(--text-muted);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
        }

        /* Shimmer Animation loading slots */
        .shimmer-bg {
            background: linear-gradient(90deg, #f0f3fa 25%, #e2e8f0 50%, #f0f3fa 75%);
            background-size: 200% 100%;
            animation: shimmer-pulse 1.5s infinite ease-in-out;
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

        /* Valuation Typography */
        .stat-value-custom { 
            font-size: 2.4rem; 
            font-weight: 800; 
            line-height: 1.1; 
            color: var(--text-dark);
        }

        .stat-value-glow {
            background: linear-gradient(135deg, var(--primary-blue) 0%, #3b82f6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Health circles */
        .health-circle { 
            display: inline-block; 
            width: 10px; 
            height: 10px; 
            border-radius: 50%; 
            margin-right: 6px; 
        }
        
        .text-green { color: var(--accent-green) !important; }
        .text-yellow { color: var(--accent-yellow) !important; }
        .text-red { color: var(--accent-red) !important; }
        
        .bg-green { background-color: var(--accent-green); }
        .bg-yellow { background-color: var(--accent-yellow); }
        .bg-red { background-color: var(--accent-red); }

        .chart-container-custom { 
            position: relative; 
            height: 280px; 
            width: 100%; 
        }
        
        /* Table Layouts */
        .table-custom th { 
            background-color: transparent; 
            color: var(--text-muted); 
            border-bottom: 1px solid var(--border-subtle);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
            padding: 14px 18px;
        }
        .table-custom td {
            padding: 14px 18px;
            border-bottom: 1px solid var(--border-subtle);
            font-size: 13.5px;
            color: var(--text-dark);
        }
        .table-custom-hover tbody tr:hover { 
            background-color: rgba(19, 102, 217, 0.02); 
        }
        
        /* SIMS Standard Badges */
        .badge-in { 
            background-color: rgba(85, 179, 138, 0.12); 
            color: var(--accent-green); 
            padding: 5px 12px; 
            font-size: 11px; 
            font-weight: 700; 
            border-radius: 20px;
        }
        .badge-out { 
            background-color: rgba(243, 85, 136, 0.10); 
            color: var(--accent-red); 
            padding: 5px 12px; 
            font-size: 11px; 
            font-weight: 700; 
            border-radius: 20px;
        }

        /* Glowing Pulse Indicators */
        .pulse-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            background-color: var(--accent-green);
            border-radius: 50%;
            box-shadow: 0 0 10px rgba(85, 179, 138, 0.5);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(85, 179, 138, 0.7); }
            70% { box-shadow: 0 0 0 8px rgba(85, 179, 138, 0); }
            100% { box-shadow: 0 0 0 0 rgba(85, 179, 138, 0); }
        }

        .btn-refresh-custom {
            background: #fff;
            color: var(--text-dark);
            border: 1px solid var(--border-subtle);
            padding: 8px 20px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        .btn-refresh-custom:hover {
            background: rgba(19, 102, 217, 0.05);
            color: var(--primary-blue);
            border-color: rgba(19, 102, 217, 0.2);
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body>

<div class="container-fluid py-4 px-4" style="max-width: 1600px;">
    <!-- Top Welcome & Brand Banner -->
    <div class="card-custom p-4 mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center align-items-start gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center justify-content-center shadow-sm" 
                     style="width: 50px; height: 50px; border-radius: 12px; background: rgba(19, 102, 217, 0.1); color: var(--primary-blue); font-weight: 800; font-size: 20px;">
                    <i class="bi bi-activity"></i>
                </div>
                <div>
                    <h4 class="fw-bold text-dark mb-0" style="letter-spacing: -0.5px;">Live Operations Room</h4>
                    <p class="text-muted mb-0" style="font-size: 13px;">Real-time warehouse throughput and inventory dashboard.</p>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center gap-2 text-dark bg-light rounded-pill px-3 py-2 border" style="font-size: 12px; font-weight: 600;">
                    <span class="pulse-indicator" id="pulseIndicator"></span>
                    <span style="letter-spacing: 0.5px;">SYSTEM LIVE</span>
                </div>
                <button id="btnRefreshLive" class="btn btn-refresh-custom">
                    <i class="bi bi-arrow-clockwise"></i> Sync
                </button>
            </div>
        </div>
    </div>

    <!-- Top KPI Cards Grid -->
    <div class="row g-4 mb-4">
        <!-- Valuation -->
        <div class="col-lg-4 col-md-6 col-12">
            <div class="card-custom h-100 p-4 position-relative overflow-hidden">
                <div class="position-absolute" style="top:-20px; right:-20px; opacity:0.03; transform:rotate(-15deg);">
                    <i class="bi bi-cash-stack" style="font-size:120px;"></i>
                </div>
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:42px;height:42px;background:rgba(19,102,217,.1);color:var(--primary-blue);">
                        <i class="bi bi-currency-exchange fs-5"></i>
                    </div>
                    <span class="card-title-custom mb-0">Total Valuation</span>
                </div>
                <div class="stat-value-custom stat-value-glow mt-2" id="valTotalValue">ETB 0.00</div>
            </div>
        </div>
        <!-- Units -->
        <div class="col-lg-4 col-md-6 col-12">
            <div class="card-custom h-100 p-4 position-relative overflow-hidden">
                <div class="position-absolute" style="top:-20px; right:-20px; opacity:0.03; transform:rotate(-15deg);">
                    <i class="bi bi-box-seam" style="font-size:120px;"></i>
                </div>
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:42px;height:42px;background:rgba(85,179,138,.1);color:var(--accent-green);">
                        <i class="bi bi-boxes fs-5"></i>
                    </div>
                    <span class="card-title-custom mb-0">Active Stock Units</span>
                </div>
                <div class="stat-value-custom mt-2" id="valTotalUnits">0</div>
            </div>
        </div>
        <!-- Health Breakdown -->
        <div class="col-lg-4 col-12">
            <div class="card-custom h-100 p-4">
                <span class="card-title-custom d-block mb-3"><i class="bi bi-heart-pulse me-1"></i> Stock Health Indicators</span>
                <div class="d-flex justify-content-between align-items-center mt-2 px-1">
                    <!-- Healthy -->
                    <div class="text-center bg-light rounded-3 p-2 border" style="flex-grow: 1; margin: 0 4px;">
                        <div class="d-flex align-items-center justify-content-center mb-1 gap-1">
                            <span class="health-circle bg-green"></span>
                            <span class="fw-bold text-dark fs-4" id="valHealthGreen">0</span>
                        </div>
                        <div style="font-size: 10px; color: var(--text-muted); font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase;">Healthy (15+)</div>
                    </div>
                    <!-- Low -->
                    <div class="text-center bg-light rounded-3 p-2 border" style="flex-grow: 1; margin: 0 4px;">
                        <div class="d-flex align-items-center justify-content-center mb-1 gap-1">
                            <span class="health-circle bg-yellow"></span>
                            <span class="fw-bold text-dark fs-4" id="valHealthYellow">0</span>
                        </div>
                        <div style="font-size: 10px; color: var(--text-muted); font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase;">Low (&lt;15)</div>
                    </div>
                    <!-- Out -->
                    <div class="text-center bg-light rounded-3 p-2 border" style="flex-grow: 1; margin: 0 4px;">
                        <div class="d-flex align-items-center justify-content-center mb-1 gap-1">
                            <span class="health-circle bg-red"></span>
                            <span class="fw-bold text-dark fs-4" id="valHealthRed">0</span>
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
                <div class="d-flex align-items-center gap-2 mb-4">
                    <i class="bi bi-pie-chart text-primary fs-5"></i>
                    <h6 class="fw-bold text-dark mb-0" style="font-size: 16px;">Category Distribution</h6>
                </div>
                <div class="chart-container-custom">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
        <!-- Trend Activity -->
        <div class="col-lg-6 col-12">
            <div class="card-custom p-4">
                <div class="d-flex align-items-center gap-2 mb-4">
                    <i class="bi bi-graph-up text-green fs-5"></i>
                    <h6 class="fw-bold text-dark mb-0" style="font-size: 16px;">Daily Activity Volume</h6>
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
            <div class="card-custom h-100 p-0 overflow-hidden">
                <div class="d-flex align-items-center gap-2 p-4 pb-3 border-bottom">
                    <i class="bi bi-clock-history text-primary fs-5"></i>
                    <h6 class="fw-bold text-dark mb-0" style="font-size: 16px;">Chronological Operations Feed</h6>
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
                <div class="d-flex align-items-center gap-2 mb-4 border-bottom pb-3">
                    <i class="bi bi-trophy text-yellow fs-5"></i>
                    <h6 class="fw-bold text-dark mb-0" style="font-size: 16px;">Top Moving Products</h6>
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

    // Formatting tools (Updated for ETB)
    const formatCurrency = (val) => 'ETB ' + new Intl.NumberFormat('en-US').format(val);
    const formatDate = (dateString) => {
        const d = new Date(dateString);
        return d.toLocaleDateString('en-US', { month: 'short', day: '2-digit' }) + ' - ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    };

    // Initialize charts with light mode styling config
    function initCharts() {
        Chart.defaults.color = '#a3aed1';
        Chart.defaults.font.family = 'Inter';

        // Category capacity distribution donut chart
        const catCtx = document.getElementById('categoryChart').getContext('2d');
        categoryChartInstance = new Chart(catCtx, {
            type: 'doughnut',
            data: { 
                labels: [], 
                datasets: [{ 
                    data: [], 
                    backgroundColor: ['#1366d9', '#55b38a', '#f59e0b', '#f35588', '#06b6d4', '#8b5cf6', '#ec4899'], 
                    borderWidth: 2,
                    borderColor: '#ffffff'
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
                            padding: 15,
                            color: '#2b3674'
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
                    line: { tension: 0.3, borderWidth: 3 }, 
                    point: { radius: 4, hitRadius: 10, backgroundColor: '#ffffff', borderWidth: 2 } 
                }, 
                scales: { 
                    y: { 
                        beginAtZero: true, 
                        grid: { color: '#e9edf7' },
                        ticks: { font: { family: 'Inter', size: 11 }, color: '#a3aed1' }
                    },
                    x: { 
                        grid: { display: false },
                        ticks: { font: { family: 'Inter', size: 11 }, color: '#a3aed1' }
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
            // Pulsing blue while syncing
            const pulse = document.getElementById('pulseIndicator');
            pulse.style.backgroundColor = '#1366d9';
            pulse.style.boxShadow = '0 0 10px rgba(19, 102, 217, 0.5)';
            
            const response = await fetch('../api/live_data.php');
            const textData = await response.text();
            
            pulse.style.backgroundColor = 'var(--accent-green)';
            pulse.style.boxShadow = '0 0 10px rgba(85, 179, 138, 0.5)';

            // Smart sync: don't re-render unless hash modified
            if (textData === lastDataHash) return;
            
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
                    borderColor: '#1366d9',
                    backgroundColor: 'rgba(19, 102, 217, 0.05)',
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
                    const icon = isOut ? '↓' : '↑';
                    
                    recentBody.innerHTML += `
                        <tr>
                            <td class="ps-4 text-muted">${formatDate(item.created_at)}</td>
                            <td class="fw-semibold text-dark">${item.product_name}</td>
                            <td class="text-center"><span class="${badgeClass}">${icon} ${item.movement_type}</span></td>
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
                                <span class="fw-semibold d-flex align-items-center gap-2" style="font-size: 13.5px; color: var(--text-dark);">
                                    <span class="badge ${colorClass} rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 20px; height: 20px; font-size: 9px; padding: 0;">${index + 1}</span>
                                    <span>${item.name}</span>
                                </span>
                                <span class="fw-semibold text-muted" style="font-size: 12px;">${parseInt(item.total_out).toLocaleString()} units</span>
                            </div>
                            <div class="progress" style="height: 6px; background-color: #e9edf7; border-radius: 4px;">
                                <div class="progress-bar ${colorClass}" role="progressbar" style="width: ${percent}%; border-radius: 4px;"></div>
                            </div>
                        </div>
                    `;
                });
            }

        } catch (error) {
            console.error('Operations room sync failure:', error);
            document.getElementById('pulseIndicator').style.backgroundColor = 'var(--accent-red)';
            document.getElementById('pulseIndicator').style.boxShadow = '0 0 10px rgba(243, 85, 136, 0.5)';
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
