<?php
namespace App;

class ServiceIcons {
    /**
     * 有名サービスのアイコンURLマッピング
     */
    private static $serviceIcons = [
        // 動画ストリーミング
        'netflix' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/netflix/netflix-original.svg',
        'disney' => 'https://upload.wikimedia.org/wikipedia/commons/3/3e/Disney%2B_logo.svg',
        'disney+' => 'https://upload.wikimedia.org/wikipedia/commons/3/3e/Disney%2B_logo.svg',
        'disneyplus' => 'https://upload.wikimedia.org/wikipedia/commons/3/3e/Disney%2B_logo.svg',
        'amazon prime' => 'https://upload.wikimedia.org/wikipedia/commons/1/11/Amazon_Prime_Video_logo.svg',
        'prime video' => 'https://upload.wikimedia.org/wikipedia/commons/1/11/Amazon_Prime_Video_logo.svg',
        'hulu' => 'https://upload.wikimedia.org/wikipedia/commons/e/e4/Hulu_Logo.svg',
        'youtube' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/youtube/youtube-original.svg',
        'youtube premium' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/youtube/youtube-original.svg',
        'youtube music' => 'https://upload.wikimedia.org/wikipedia/commons/6/6a/Youtube_Music_icon.svg',
        'apple tv' => 'https://upload.wikimedia.org/wikipedia/commons/2/28/Apple_TV_Plus_Logo.svg',
        'apple tv+' => 'https://upload.wikimedia.org/wikipedia/commons/2/28/Apple_TV_Plus_Logo.svg',
        'u-next' => 'https://www.unext.co.jp/favicon.ico',
        'abema' => 'https://upload.wikimedia.org/wikipedia/commons/0/0f/Abema_logo.svg',
        'dazn' => 'https://upload.wikimedia.org/wikipedia/commons/a/a2/DAZN_logo.svg',
        
        // 音楽ストリーミング
        'spotify' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/spotify/spotify-original.svg',
        'apple music' => 'https://upload.wikimedia.org/wikipedia/commons/2/2a/Apple_Music_Icon.svg',
        'amazon music' => 'https://upload.wikimedia.org/wikipedia/commons/6/61/Amazon_Music_logo.svg',
        'line music' => 'https://music.line.me/favicon.ico',
        'awa' => 'https://s.awa.fm/favicon.ico',
        
        // クラウドストレージ
        'google drive' => 'https://upload.wikimedia.org/wikipedia/commons/1/12/Google_Drive_icon_%282020%29.svg',
        'google one' => 'https://upload.wikimedia.org/wikipedia/commons/5/53/Google_%22G%22_Logo.svg',
        'dropbox' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/dropbox/dropbox-original.svg',
        'icloud' => 'https://upload.wikimedia.org/wikipedia/commons/4/4a/ICloud_logo.svg',
        'icloud+' => 'https://upload.wikimedia.org/wikipedia/commons/4/4a/ICloud_logo.svg',
        'onedrive' => 'https://upload.wikimedia.org/wikipedia/commons/3/3c/Microsoft_Office_OneDrive_%282019%E2%80%93present%29.svg',
        'box' => 'https://upload.wikimedia.org/wikipedia/commons/5/57/Box%2C_Inc._logo.svg',
        
        // 生産性ツール
        'microsoft 365' => 'https://upload.wikimedia.org/wikipedia/commons/5/5f/Microsoft_Office_logo_%282019%E2%80%93present%29.svg',
        'office 365' => 'https://upload.wikimedia.org/wikipedia/commons/5/5f/Microsoft_Office_logo_%282019%E2%80%93present%29.svg',
        'adobe creative cloud' => 'https://upload.wikimedia.org/wikipedia/commons/4/4c/Adobe_Creative_Cloud_rainbow_icon.svg',
        'adobe' => 'https://upload.wikimedia.org/wikipedia/commons/8/8d/Adobe_Corporate_Logo.svg',
        'canva' => 'https://upload.wikimedia.org/wikipedia/commons/0/08/Canva_icon_2021.svg',
        'notion' => 'https://upload.wikimedia.org/wikipedia/commons/4/45/Notion_app_logo.png',
        'evernote' => 'https://upload.wikimedia.org/wikipedia/commons/a/a4/Evernote_Icon.png',
        'slack' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/slack/slack-original.svg',
        'zoom' => 'https://upload.wikimedia.org/wikipedia/commons/7/7b/Zoom_Communications_Logo.svg',
        'chatgpt' => 'https://upload.wikimedia.org/wikipedia/commons/0/04/ChatGPT_logo.svg',
        'chatgpt plus' => 'https://upload.wikimedia.org/wikipedia/commons/0/04/ChatGPT_logo.svg',
        
        // ゲーム
        'playstation' => 'https://upload.wikimedia.org/wikipedia/commons/0/00/PlayStation_logo.svg',
        'playstation plus' => 'https://upload.wikimedia.org/wikipedia/commons/0/00/PlayStation_logo.svg',
        'ps plus' => 'https://upload.wikimedia.org/wikipedia/commons/0/00/PlayStation_logo.svg',
        'xbox game pass' => 'https://upload.wikimedia.org/wikipedia/commons/f/f9/Xbox_one_logo.svg',
        'nintendo switch online' => 'https://upload.wikimedia.org/wikipedia/commons/0/04/Nintendo_Switch_logo%2C_without_text.svg',
        'nintendo' => 'https://upload.wikimedia.org/wikipedia/commons/0/0d/Nintendo.svg',
        
        // その他
        'amazon' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/amazonwebservices/amazonwebservices-original.svg',
        'kindle unlimited' => 'https://upload.wikimedia.org/wikipedia/commons/0/06/Amazon_Kindle_logo.svg',
        'audible' => 'https://upload.wikimedia.org/wikipedia/commons/f/fc/Audible_2017_logo.svg',
        'github' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/github/github-original.svg',
        'github copilot' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/github/github-original.svg',
    ];
    
    /**
     * サービス名からアイコンURLを取得
     */
    public static function getIconUrl($serviceName) {
        $serviceLower = strtolower(trim($serviceName));
        
        // 完全一致を確認
        if (isset(self::$serviceIcons[$serviceLower])) {
            return self::$serviceIcons[$serviceLower];
        }
        
        // 部分一致を確認
        foreach (self::$serviceIcons as $key => $url) {
            if (strpos($serviceLower, $key) !== false || strpos($key, $serviceLower) !== false) {
                return $url;
            }
        }
        
        return null;
    }
    
    /**
     * サービスの頭文字を取得（アイコンがない場合の代替表示用）
     */
    public static function getInitials($serviceName) {
        $words = preg_split('/\s+/', trim($serviceName));
        $initials = '';
        
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= mb_strtoupper(mb_substr($word, 0, 1));
                if (mb_strlen($initials) >= 2) break;
            }
        }
        
        return $initials ?: '?';
    }
    
    /**
     * カテゴリに基づくデフォルトの背景色
     */
    public static function getCategoryColor($category) {
        $colors = [
            'エンターテイメント' => '#e74c3c',
            '仕事効率化' => '#3498db',
            'クラウドストレージ' => '#9b59b6',
            '学習' => '#1abc9c',
            'ゲーム' => '#f39c12',
            'その他' => '#95a5a6'
        ];
        
        return $colors[$category] ?? '#95a5a6';
    }
}