<?php
namespace App;

use PDO;

require_once __DIR__ . '/ServiceIcons.php';

class Subscription {
    private $db;
    
    public function __construct() {
        $this->db = \Database::getInstance()->getConnection();
    }
    
    public function create($userId, $data) {
        $category = $this->detectCategory($data['service_name']);
        $logoUrl = $data['logo_url'] ?? ServiceIcons::getIconUrl($data['service_name']);
        
        $stmt = $this->db->prepare("
            INSERT INTO subscriptions (user_id, service_name, logo_url, monthly_fee, currency, renewal_cycle, category, start_date, next_renewal_date, payment_method, payment_method_id) 
            VALUES (:user_id, :service_name, :logo_url, :monthly_fee, :currency, :renewal_cycle, :category, :start_date, :next_renewal_date, :payment_method, :payment_method_id)
        ");
        
        return $stmt->execute([
            ':user_id' => $userId,
            ':service_name' => $data['service_name'],
            ':logo_url' => $logoUrl,
            ':monthly_fee' => $data['monthly_fee'],
            ':currency' => $data['currency'] ?? 'JPY',
            ':renewal_cycle' => $data['renewal_cycle'] ?? 'monthly',
            ':category' => $category,
            ':start_date' => $data['start_date'],
            ':next_renewal_date' => $data['next_renewal_date'],
            ':payment_method' => $data['payment_method'] ?? 'credit_card',
            ':payment_method_id' => $data['payment_method_id'] ?? null
        ]);
    }
    
    public function update($id, $userId, $data) {
        $category = $this->detectCategory($data['service_name']);
        $logoUrl = $data['logo_url'] ?? ServiceIcons::getIconUrl($data['service_name']);
        
        $stmt = $this->db->prepare("
            UPDATE subscriptions 
            SET service_name = :service_name, 
                logo_url = :logo_url,
                monthly_fee = :monthly_fee, 
                currency = :currency,
                renewal_cycle = :renewal_cycle,
                category = :category,
                payment_method = :payment_method,
                payment_method_id = :payment_method_id,
                start_date = :start_date,
                next_renewal_date = :next_renewal_date,
                is_active = :is_active
            WHERE id = :id AND user_id = :user_id
        ");
        
        return $stmt->execute([
            ':id' => $id,
            ':user_id' => $userId,
            ':service_name' => $data['service_name'],
            ':logo_url' => $logoUrl,
            ':monthly_fee' => $data['monthly_fee'],
            ':currency' => $data['currency'] ?? 'JPY',
            ':renewal_cycle' => $data['renewal_cycle'] ?? 'monthly',
            ':category' => $category,
            ':payment_method' => $data['payment_method'] ?? 'credit_card',
            ':payment_method_id' => $data['payment_method_id'] ?? null,
            ':start_date' => $data['start_date'],
            ':next_renewal_date' => $data['next_renewal_date'],
            ':is_active' => $data['is_active'] ?? true
        ]);
    }
    
    public function delete($id, $userId) {
        $stmt = $this->db->prepare("DELETE FROM subscriptions WHERE id = :id AND user_id = :user_id");
        return $stmt->execute([':id' => $id, ':user_id' => $userId]);
    }
    
    public function getById($id, $userId) {
        $stmt = $this->db->prepare("SELECT * FROM subscriptions WHERE id = :id AND user_id = :user_id");
        $stmt->execute([':id' => $id, ':user_id' => $userId]);
        return $stmt->fetch();
    }
    
    public function getByUserId($userId, $activeOnly = false) {
        $sql = "
            SELECT s.*
            FROM subscriptions s
            WHERE s.user_id = :user_id
        ";
        if ($activeOnly) {
            $sql .= " AND s.is_active = 1";
        }
        $sql .= " ORDER BY s.next_renewal_date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }
    
    public function getMonthlyTotal($userId, $currency = 'JPY') {
        $subscriptions = $this->getByUserId($userId);
        $total = 0;
        
        foreach ($subscriptions as $sub) {
            if ($sub['currency'] === $currency) {
                $total += $sub['monthly_fee'];
            } else {
                $total += $this->convertCurrency($sub['monthly_fee'], $sub['currency'], $currency);
            }
        }
        
        return $total;
    }
    
    public function getUpcomingRenewals($userId, $days = 3) {
        $stmt = $this->db->prepare("
            SELECT * FROM subscriptions 
            WHERE user_id = :user_id 
            AND is_active = 1 
            AND next_renewal_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
            ORDER BY next_renewal_date ASC
        ");
        $stmt->execute([':user_id' => $userId, ':days' => $days]);
        return $stmt->fetchAll();
    }
    
    private function convertCurrency($amount, $from, $to) {
        if ($from === $to) return $amount;
        
        $cacheKey = "exchange_rate_{$from}_{$to}";
        $rate = $this->getCachedExchangeRate($cacheKey);
        
        if (!$rate) {
            $rate = $this->fetchExchangeRate($from, $to);
            $this->cacheExchangeRate($cacheKey, $rate);
        }
        
        return $amount * $rate;
    }
    
    private function fetchExchangeRate($from, $to) {
        $url = API_EXCHANGE_URL;
        $response = @file_get_contents($url);
        
        if ($response === false) {
            return 1;
        }
        
        $data = json_decode($response, true);
        if (isset($data['rates'][$from]) && isset($data['rates'][$to])) {
            return $data['rates'][$to] / $data['rates'][$from];
        }
        
        return 1;
    }
    
    private function getCachedExchangeRate($key) {
        return null;
    }
    
    private function cacheExchangeRate($key, $rate) {
        return true;
    }
    
    public function detectCategory($serviceName) {
        $stmt = $this->db->prepare("
            SELECT c.name 
            FROM service_categories sc 
            JOIN categories c ON sc.category_id = c.id 
            WHERE :service_name LIKE CONCAT('%', sc.service_pattern, '%')
            LIMIT 1
        ");
        $stmt->execute([':service_name' => $serviceName]);
        $result = $stmt->fetch();
        
        return $result ? $result['name'] : 'その他';
    }
    
    public function getByCategory($userId) {
        // まず全てのアクティブなサブスクリプションを取得
        $stmt = $this->db->prepare("
            SELECT s.*
            FROM subscriptions s
            WHERE s.user_id = :user_id AND s.is_active = 1
            ORDER BY s.service_name
        ");
        $stmt->execute([':user_id' => $userId]);
        $subscriptions = $stmt->fetchAll();
        
        // カテゴリ一覧を取得
        $stmt = $this->db->prepare("SELECT name, color FROM categories ORDER BY name");
        $stmt->execute();
        $categories = [];
        foreach ($stmt->fetchAll() as $cat) {
            $categories[$cat['name']] = $cat['color'];
        }
        
        $grouped = [];
        
        // 各サブスクリプションをカテゴリ分け
        foreach ($subscriptions as $sub) {
            // サービス名からカテゴリを検出
            $category = $this->detectCategory($sub['service_name']);
            $sub['category'] = $category;
            
            // カテゴリごとにグループ化
            if (!isset($grouped[$category])) {
                $color = isset($categories[$category]) ? $categories[$category] : '#95a5a6';
                $grouped[$category] = [
                    'name' => $category,
                    'color' => $color,
                    'subscriptions' => []
                ];
            }
            
            $sub['category_color'] = $grouped[$category]['color'];
            $grouped[$category]['subscriptions'][] = $sub;
        }
        
        return $grouped;
    }
    
    public function calculateNextRenewal($startDate, $cycle) {
        $date = new \DateTime($startDate);
        $today = new \DateTime();
        
        while ($date <= $today) {
            switch ($cycle) {
                case 'weekly':
                    $date->add(new \DateInterval('P7D'));
                    break;
                case 'monthly':
                    $date->add(new \DateInterval('P1M'));
                    break;
                case 'quarterly':
                    $date->add(new \DateInterval('P3M'));
                    break;
                case 'semiannually':
                    $date->add(new \DateInterval('P6M'));
                    break;
                case 'yearly':
                    $date->add(new \DateInterval('P1Y'));
                    break;
            }
        }
        
        return $date->format('Y-m-d');
    }
}