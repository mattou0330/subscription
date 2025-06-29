-- Fix double-encoded Japanese text in categories table
UPDATE categories SET name = 'エンターテイメント' WHERE id = 1;
UPDATE categories SET name = '仕事・ビジネス' WHERE id = 2;
UPDATE categories SET name = '学習・教育' WHERE id = 3;
UPDATE categories SET name = 'ニュース・情報' WHERE id = 4;
UPDATE categories SET name = 'クラウドストレージ' WHERE id = 5;
UPDATE categories SET name = '音楽' WHERE id = 6;
UPDATE categories SET name = '動画配信' WHERE id = 7;
UPDATE categories SET name = 'ソフトウェア' WHERE id = 8;
UPDATE categories SET name = 'その他' WHERE id = 9;