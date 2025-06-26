<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Subscription.php';

use App\Auth;
use App\Subscription;

$auth = new Auth();
$auth->requireLogin();

$subscription = new Subscription();
$userId = $auth->getCurrentUserId();
$subscriptions = $subscription->getByUserId($userId);
$subscriptionsByCategory = $subscription->getByCategory($userId);
$monthlyTotal = $subscription->getMonthlyTotal($userId);

// Calculate monthly trend data (mock data for demo)
$monthlyTrend = [
    ['month' => '1月', 'amount' => $monthlyTotal * 0.85],
    ['month' => '2月', 'amount' => $monthlyTotal * 0.87],
    ['month' => '3月', 'amount' => $monthlyTotal * 0.90],
    ['month' => '4月', 'amount' => $monthlyTotal * 0.92],
    ['month' => '5月', 'amount' => $monthlyTotal * 0.95],
    ['month' => '6月', 'amount' => $monthlyTotal],
];

$pageTitle = '分析';
$currentPage = 'analysis';

$additionalStyles = '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';

ob_start();
?>

<div class="analysis-grid">
    <div class="chart-card">
        <h3 class="chart-title">月額費用の推移</h3>
        <canvas id="trendChart"></canvas>
    </div>
    
    <div class="chart-card">
        <h3 class="chart-title">カテゴリ別費用</h3>
        <canvas id="categoryChart"></canvas>
    </div>
    
    <div class="chart-card">
        <h3 class="chart-title">支払い方法別統計</h3>
        <canvas id="paymentChart"></canvas>
    </div>
    
    <div class="insights-card">
        <h3 class="chart-title">インサイト</h3>
        <div class="insight-items">
            <div class="insight-item">
                <i class="fas fa-chart-line text-success"></i>
                <div>
                    <h4>支出の増加</h4>
                    <p>過去6ヶ月で月額費用が15%増加しています</p>
                </div>
            </div>
            <div class="insight-item">
                <i class="fas fa-tag text-primary"></i>
                <div>
                    <h4>最大カテゴリ</h4>
                    <p>エンターテイメントが全体の45%を占めています</p>
                </div>
            </div>
            <div class="insight-item">
                <i class="fas fa-calendar-check text-warning"></i>
                <div>
                    <h4>更新頻度</h4>
                    <p>月更新のサービスが全体の80%です</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="table-card">
    <h3 class="chart-title">サービス別詳細</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>サービス名</th>
                <th>月額料金</th>
                <th>年間費用</th>
                <th>契約期間</th>
                <th>費用効率</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($subscriptions as $sub): ?>
                <?php
                $startDate = new DateTime($sub['start_date']);
                $now = new DateTime();
                $interval = $startDate->diff($now);
                $months = $interval->y * 12 + $interval->m;
                
                $yearlyAmount = $sub['monthly_fee'] * 12;
                $efficiency = $months > 0 ? round($sub['monthly_fee'] * $months / $months, 2) : 0;
                ?>
                <tr>
                    <td><?= htmlspecialchars($sub['service_name']) ?></td>
                    <td>¥<?= number_format($sub['monthly_fee']) ?></td>
                    <td>¥<?= number_format($yearlyAmount) ?></td>
                    <td><?= $months ?>ヶ月</td>
                    <td>
                        <span class="efficiency-badge <?= $efficiency < $sub['monthly_fee'] ? 'good' : 'normal' ?>">
                            ¥<?= number_format($efficiency) ?>/月
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<style>
.analysis-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.chart-card, .insights-card, .table-card {
    background: var(--surface);
    padding: 1.5rem;
    border-radius: 1rem;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
}

.chart-card {
    height: 400px;
    position: relative;
}

.chart-card canvas {
    max-height: 350px !important;
}

.chart-title {
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    color: var(--text-primary);
}

.insight-items {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.insight-item {
    display: flex;
    gap: 1rem;
    align-items: start;
}

.insight-item i {
    font-size: 1.5rem;
    margin-top: 0.25rem;
}

.insight-item h4 {
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.insight-item p {
    font-size: 0.8125rem;
    color: var(--text-secondary);
}

.text-success { color: var(--success-color); }
.text-primary { color: var(--primary-color); }
.text-warning { color: var(--warning-color); }

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    text-align: left;
    padding: 0.75rem;
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--text-secondary);
    border-bottom: 2px solid var(--border-color);
}

.data-table td {
    padding: 1rem 0.75rem;
    border-bottom: 1px solid var(--border-color);
}

.efficiency-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 500;
}

.efficiency-badge.good {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success-color);
}

.efficiency-badge.normal {
    background: rgba(107, 114, 128, 0.1);
    color: var(--text-secondary);
}
</style>

<script>
// Monthly Trend Chart
const trendCtx = document.getElementById('trendChart').getContext('2d');
const trendData = <?= json_encode($monthlyTrend) ?>;

new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: trendData.map(d => d.month),
        datasets: [{
            label: '月額費用',
            data: trendData.map(d => d.amount),
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        layout: {
            padding: {
                bottom: 20
            }
        },
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '¥' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

// Category Chart
const categoryData = <?= json_encode($subscriptionsByCategory) ?>;
const categoryCtx = document.getElementById('categoryChart').getContext('2d');

const categoryTotals = {};
const categoryColors = {};

Object.values(categoryData).forEach(category => {
    let total = 0;
    category.subscriptions.forEach(sub => {
        total += parseFloat(sub.monthly_fee);
    });
    categoryTotals[category.name] = total;
    categoryColors[category.name] = category.color;
});

new Chart(categoryCtx, {
    type: 'doughnut',
    data: {
        labels: Object.keys(categoryTotals),
        datasets: [{
            data: Object.values(categoryTotals),
            backgroundColor: Object.keys(categoryTotals).map(name => categoryColors[name])
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        layout: {
            padding: {
                top: 10,
                bottom: 10
            }
        },
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    padding: 15,
                    font: { size: 12 }
                }
            }
        }
    }
});

// Payment Method Chart
const paymentCtx = document.getElementById('paymentChart').getContext('2d');
new Chart(paymentCtx, {
    type: 'bar',
    data: {
        labels: ['クレジットカード', 'デビットカード', 'PayPal', '銀行振込'],
        datasets: [{
            label: 'サービス数',
            data: [12, 3, 2, 1],
            backgroundColor: '#6366f1'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        layout: {
            padding: {
                bottom: 20
            }
        },
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../templates/layout.php';
?>