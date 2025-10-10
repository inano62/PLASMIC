# PLASMIC – Multi-Sector Online Booking & Payment Platform (α)

## 🌐 概要
PLASMICは、士業・訪問医療・美容クリニックなど、**相談業務をオンライン化**したい事業者向けの  
「**LP + 予約 + Stripe決済 + Meet（ビデオ通話）**」を統合する軽量なWebプラットフォームです。  

初期フェーズでは **WordPress** によるローンチの速さを重視し、  
将来的には **React / Laravel / Rust** でコア機能を再構成するSaaS基盤を構想しています。

---

## 🛠 技術スタック
| カテゴリ | 技術 |
|----------|------|
| フロント | React (Vite), Tailwind, Vanilla JS, WordPress (LP) |
| バックエンド | Laravel (Sanctum), PHP, Node.js (MERN一部実験) |
| インフラ | Docker Compose, Traefik / Nginx |
| 決済 | Stripe API / Stripe Connect |
| 通話 | Google Meet API連携（予約自動リンク生成） |
| その他 | GitHub Actions（デプロイ自動化実験中）, Mailpit（開発用SMTP） |

---

## 🧭 機能概要
- LPからの **相談予約・事前決済**  
- Stripe決済完了時に **Meetリンクを自動発行**  
- 予約・決済・通話の流れを最短で実現  
- WP→Reactへの**段階的リプレイス可能**な構造  
- Stripe Connect対応（将来的に複数事業者管理を想定）

---

## 🧪 ロードマップ
- [x] LP構築（WordPress）
- [x] Stripe決済（1回払い）
- [x] Google Meet連携（予約→リンク発行）
- [x] React Widgets試作
- [ ] Laravel API（SaaS基盤）
- [ ] MERN/Socket.io（チャット・予約管理）
- [ ] Rustによる高速画像処理モジュール

---

## 💻 ローカル開発
```bash
git clone https://github.com/xxx/plasmic.git
cd plasmic
docker compose up -d
# http://localhost:8080 (WordPress)
# http://localhost:5173 (React Dev)
