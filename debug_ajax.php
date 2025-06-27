<?php
// Ajax通信のデバッグページ
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajax通信デバッグ</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .test-section {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .result {
            margin-top: 10px;
            padding: 10px;
            background-color: #f0f0f0;
            border-radius: 3px;
            white-space: pre-wrap;
        }
        .error {
            background-color: #fee;
            color: #d00;
        }
        .success {
            background-color: #efe;
            color: #060;
        }
        button {
            padding: 10px 20px;
            margin: 5px;
            cursor: pointer;
        }
        input, select {
            padding: 5px;
            margin: 5px;
        }
    </style>
</head>
<body>
    <h1>Ajax通信デバッグ</h1>
    
    <div class="test-section">
        <h2>1. 支払い方法一覧の取得テスト</h2>
        <button onclick="testPaymentMethods()">テスト実行</button>
        <div id="paymentMethodsResult" class="result"></div>
    </div>
    
    <div class="test-section">
        <h2>2. サブスクリプション作成テスト</h2>
        <form id="testCreateForm">
            <input type="text" id="service_name" placeholder="サービス名" value="テストサービス">
            <input type="number" id="monthly_fee" placeholder="月額料金" value="1000">
            <input type="date" id="start_date" value="<?= date('Y-m-d') ?>">
            <button type="button" onclick="testCreateSubscription()">テスト実行</button>
        </form>
        <div id="createResult" class="result"></div>
    </div>
    
    <div class="test-section">
        <h2>3. CSRFトークンの確認</h2>
        <button onclick="testCSRFToken()">トークン取得</button>
        <div id="csrfResult" class="result"></div>
    </div>
    
    <div class="test-section">
        <h2>4. エラーレスポンスの確認</h2>
        <button onclick="testErrorHandling()">エラーテスト</button>
        <div id="errorResult" class="result"></div>
    </div>

    <script>
    // コンソールログも画面に表示
    const originalLog = console.log;
    const originalError = console.error;
    
    console.log = function(...args) {
        originalLog.apply(console, args);
        logToScreen('LOG: ' + args.join(' '));
    };
    
    console.error = function(...args) {
        originalError.apply(console, args);
        logToScreen('ERROR: ' + args.join(' '), true);
    };
    
    function logToScreen(message, isError = false) {
        const div = document.createElement('div');
        div.className = 'result ' + (isError ? 'error' : '');
        div.textContent = new Date().toLocaleTimeString() + ' - ' + message;
        document.body.appendChild(div);
    }
    
    // 1. 支払い方法一覧のテスト
    function testPaymentMethods() {
        const resultDiv = document.getElementById('paymentMethodsResult');
        resultDiv.textContent = 'Loading...';
        
        fetch('public/payment-methods.php?ajax=1')
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                return response.text();
            })
            .then(text => {
                console.log('Raw response:', text);
                try {
                    const data = JSON.parse(text);
                    resultDiv.innerHTML = '<div class="success">成功:</div>' + JSON.stringify(data, null, 2);
                } catch (e) {
                    resultDiv.innerHTML = '<div class="error">JSONパースエラー:</div>' + text;
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<div class="error">通信エラー:</div>' + error.toString();
                console.error('Fetch error:', error);
            });
    }
    
    // 2. サブスクリプション作成テスト
    async function testCreateSubscription() {
        const resultDiv = document.getElementById('createResult');
        resultDiv.textContent = 'Loading...';
        
        // まずCSRFトークンを取得
        const csrfToken = await getCSRFToken();
        
        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('service_name', document.getElementById('service_name').value);
        formData.append('monthly_fee', document.getElementById('monthly_fee').value);
        formData.append('start_date', document.getElementById('start_date').value);
        formData.append('currency', 'JPY');
        formData.append('renewal_cycle', 'monthly');
        formData.append('payment_method', 'credit_card');
        
        fetch('public/api/subscription-create.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Create response status:', response.status);
            return response.text();
        })
        .then(text => {
            console.log('Create raw response:', text);
            try {
                const data = JSON.parse(text);
                resultDiv.innerHTML = '<div class="' + (data.success ? 'success' : 'error') + '">結果:</div>' + JSON.stringify(data, null, 2);
            } catch (e) {
                resultDiv.innerHTML = '<div class="error">JSONパースエラー:</div>' + text;
            }
        })
        .catch(error => {
            resultDiv.innerHTML = '<div class="error">通信エラー:</div>' + error.toString();
            console.error('Create error:', error);
        });
    }
    
    // 3. CSRFトークンの取得
    async function getCSRFToken() {
        try {
            // セッション開始ページからトークンを取得
            const response = await fetch('public/dashboard.php');
            const html = await response.text();
            const match = html.match(/name="csrf_token" value="([^"]+)"/);
            return match ? match[1] : '';
        } catch (e) {
            console.error('CSRF token fetch error:', e);
            return '';
        }
    }
    
    async function testCSRFToken() {
        const resultDiv = document.getElementById('csrfResult');
        resultDiv.textContent = 'Loading...';
        
        const token = await getCSRFToken();
        resultDiv.innerHTML = '<div class="success">CSRFトークン:</div>' + (token || 'トークンが見つかりませんでした');
    }
    
    // 4. エラーハンドリングのテスト
    function testErrorHandling() {
        const resultDiv = document.getElementById('errorResult');
        resultDiv.textContent = 'Testing various error scenarios...';
        
        // 不正なメソッドでリクエスト
        fetch('public/api/subscription-create.php', {
            method: 'GET'
        })
        .then(response => response.json())
        .then(data => {
            resultDiv.innerHTML = '<div class="error">GETメソッドエラー:</div>' + JSON.stringify(data, null, 2);
        })
        .catch(error => {
            resultDiv.innerHTML = '<div class="error">エラー:</div>' + error.toString();
        });
    }
    </script>
</body>
</html>