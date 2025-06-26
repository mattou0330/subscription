<?php
// エラー表示を有効化（デバッグ用）
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Subscription.php';

use App\Auth;
use App\Subscription;

try {
    $auth = new Auth();
    $auth->requireLogin();

    $subscription = new Subscription();
    $userId = $auth->getCurrentUserId();
    $subscriptions = $subscription->getByUserId($userId);
    $subscriptionsByCategory = $subscription->getByCategory($userId);
    $monthlyTotal = $subscription->getMonthlyTotal($userId);
} catch (Exception $e) {
    // エラーが発生した場合はダッシュボードにリダイレクト
    $_SESSION['error'] = '分析ページの読み込みに失敗しました';
    header('Location: dashboard.php');
    exit;
}

// 選択された年（デフォルトは現在の年）
$selectedYear = (int)($_GET['year'] ?? date('Y'));
$currentMonth = (int)date('n');
$currentYear = (int)date('Y');

// 月額費用の実績と予測データを計算
$monthlyTrend = [];
$db = Database::getInstance()->getConnection();

for ($month = 1; $month <= 12; $month++) {
    $isPast = ($selectedYear < $currentYear) || ($selectedYear == $currentYear && $month <= $currentMonth);
    
    if ($isPast) {
        // 過去のデータは実績を計算
        $startDate = "$selectedYear-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
        $endDate = date('Y-m-t', strtotime($startDate));
        
        $stmt = $db->prepare("
            SELECT SUM(s.monthly_fee) as total
            FROM subscriptions s
            WHERE s.user_id = :user_id
            AND s.start_date <= :end_date
            AND (s.is_active = 1 OR s.updated_at > :end_date2)
        ");
        
        $stmt->execute([
            ':user_id' => $userId,
            ':end_date' => $endDate,
            ':end_date2' => $endDate
        ]);
        
        $result = $stmt->fetch();
        $amount = $result['total'] ?? 0;
    } else {
        // 未来のデータは現在のアクティブなサブスクリプションの合計
        $amount = $monthlyTotal;
    }
    
    $monthlyTrend[] = [
        'month' => $month . '月',
        'amount' => $amount,
        'isPast' => $isPast
    ];
}

$pageTitle = '分析';
$currentPage = 'analysis';

$additionalStyles = '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';

ob_start();
?>

<div class="analysis-header">
    <h1>費用分析</h1>
    <div class="year-selector">
        <button onclick="changeYear(<?= $selectedYear - 1 ?>)" class="btn btn-sm btn-secondary">
            <i class="fas fa-chevron-left"></i>
        </button>
        <span class="year-display"><?= $selectedYear ?>年</span>
        <button onclick="changeYear(<?= $selectedYear + 1 ?>)" class="btn btn-sm btn-secondary">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>
</div>

<div class="analysis-grid">
    <div class="chart-card large">
        <h3 class="chart-title">月額費用の推移</h3>
        <div class="chart-container">
            <canvas id="trendChart"></canvas>
        </div>
    </div>
    
    <div class="chart-card">
        <h3 class="chart-title">カテゴリ別費用</h3>
        <div class="chart-container">
            <canvas id="categoryChart"></canvas>
        </div>
    </div>
    
    <div class="chart-card">
        <h3 class="chart-title">支払い方法別統計</h3>
        <div class="chart-container">
            <canvas id="paymentChart"></canvas>
        </div>
    </div>
    
    <div class="insights-card">
        <h3 class="chart-title">インサイト</h3>
        <div class="insight-items">
            <?php
            // 支出の増減を計算
            $lastMonthAmount = $monthlyTrend[max(0, $currentMonth - 2)]['amount'] ?? 0;
            $currentMonthAmount = $monthlyTotal;
            $changePercent = $lastMonthAmount > 0 ? round((($currentMonthAmount - $lastMonthAmount) / $lastMonthAmount) * 100, 1) : 0;
            ?>
            <div class="insight-item">
                <i class="fas fa-chart-line <?= $changePercent >= 0 ? 'text-warning' : 'text-success' ?>"></i>
                <div>
                    <h4>支出の変化</h4>
                    <p>先月比で<?= abs($changePercent) ?>%<?= $changePercent >= 0 ? '増加' : '減少' ?>しています</p>
                </div>
            </div>
            
            <?php
            // 最大カテゴリを計算
            $maxCategory = '';
            $maxAmount = 0;
            foreach ($subscriptionsByCategory as $category) {
                $categoryTotal = 0;
                foreach ($category['subscriptions'] as $sub) {
                    if ($sub['is_active']) {
                        $categoryTotal += $sub['monthly_fee'];
                    }
                }
                if ($categoryTotal > $maxAmount) {
                    $maxAmount = $categoryTotal;
                    $maxCategory = $category['name'];
                }
            }
            $maxPercent = $monthlyTotal > 0 ? round(($maxAmount / $monthlyTotal) * 100) : 0;
            ?>
            <div class="insight-item">
                <i class="fas fa-tag text-primary"></i>
                <div>
                    <h4>最大カテゴリ</h4>
                    <p><?= htmlspecialchars($maxCategory) ?>が全体の<?= $maxPercent ?>%を占めています</p>
                </div>
            </div>
            
            <?php
            // 更新頻度を計算
            $cycleCount = ['monthly' => 0, 'yearly' => 0, 'other' => 0];
            foreach ($subscriptions as $sub) {
                if ($sub['is_active']) {
                    if ($sub['renewal_cycle'] == 'monthly') {
                        $cycleCount['monthly']++;
                    } elseif ($sub['renewal_cycle'] == 'yearly') {
                        $cycleCount['yearly']++;
                    } else {
                        $cycleCount['other']++;
                    }
                }
            }
            $totalActive = array_sum($cycleCount);
            $monthlyPercent = $totalActive > 0 ? round(($cycleCount['monthly'] / $totalActive) * 100) : 0;
            ?>
            <div class="insight-item">
                <i class="fas fa-calendar-check text-warning"></i>
                <div>
                    <h4>更新頻度</h4>
                    <p>月更新のサービスが全体の<?= $monthlyPercent ?>%です</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.analysis-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.year-selector {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: var(--surface);
    padding: 0.5rem 1rem;
    border-radius: 0.75rem;
    box-shadow: var(--shadow-sm);
}

.year-display {
    font-weight: 600;
    font-size: 1.125rem;
    min-width: 80px;
    text-align: center;
}

.analysis-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.chart-card, .insights-card {
    background: var(--surface);
    padding: 1.5rem;
    border-radius: 1rem;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
}

.chart-card.large {
    grid-column: span 2;
}

.chart-container {
    position: relative;
    height: 300px;
}

.chart-card.large .chart-container {
    height: 350px;
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

@media (max-width: 768px) {
    .chart-card.large {
        grid-column: span 1;
    }
    
    .analysis-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// 年切り替え
function changeYear(year) {
    window.location.href = `?year=${year}`;
}

// Monthly Trend Chart
const trendCtx = document.getElementById('trendChart').getContext('2d');
const trendData = <?= json_encode($monthlyTrend) ?>;

new Chart(trendCtx, {
    type: 'bar',
    data: {
        labels: trendData.map(d => d.month),
        datasets: [{
            label: '月額費用',
            data: trendData.map(d => d.amount),
            backgroundColor: trendData.map(d => d.isPast ? '#6366f1' : '#a5b4fc'),
            borderRadius: 5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const isPast = trendData[context.dataIndex].isPast;
                        const label = isPast ? '実績' : '予測';
                        return `${label}: ¥${context.parsed.y.toLocaleString()}`;
                    }
                }
            }
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
        if (sub.is_active) {
            total += parseFloat(sub.monthly_fee);
        }
    });
    if (total > 0) {
        categoryTotals[category.name] = total;
        categoryColors[category.name] = category.color;
    }
});

new Chart(categoryCtx, {
    type: 'doughnut',
    data: {
        labels: Object.keys(categoryTotals),
        datasets: [{
            data: Object.values(categoryTotals),
            backgroundColor: Object.keys(categoryTotals).map(name => categoryColors[name]),
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
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

// 支払い方法別の統計を計算
<?php
$paymentStats = [];
$stmt = $db->prepare("
    SELECT 
        COALESCE(pm.name, 'デフォルト') as name, 
        COUNT(s.id) as count
    FROM subscriptions s
    LEFT JOIN payment_methods pm ON s.payment_method = pm.id
    WHERE s.user_id = :user_id AND s.is_active = 1
    GROUP BY COALESCE(pm.name, 'デフォルト')
");
$stmt->execute([':user_id' => $userId]);
$paymentData = $stmt->fetchAll();

foreach ($paymentData as $data) {
    $name = $data['name'] ?? 'その他';
    $paymentStats[$name] = $data['count'];
}
?>
const paymentStats = <?= json_encode($paymentStats) ?>;

new Chart(paymentCtx, {
    type: 'bar',
    data: {
        labels: Object.keys(paymentStats),
        datasets: [{
            label: 'サービス数',
            data: Object.values(paymentStats),
            backgroundColor: '#6366f1',
            borderRadius: 5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { 
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../templates/layout.php';
?>