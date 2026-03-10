# 🃏 Bet Game (Family Showdown)

[![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![React](https://img.shields.io/badge/React-20232A?style=for-the-badge&logo=react&logoColor=61DAFB)](https://reactjs.org)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-316192?style=for-the-badge&logo=postgresql&logoColor=white)](https://www.postgresql.org)
[![Vite](https://img.shields.io/badge/Vite-646CFF?style=for-the-badge&logo=vite&logoColor=white)](https://vitejs.dev)

A premium, real-time **5-Card Draw Digital Poker Ledger** web application tailored for family game nights. This system digitizes the poker experience, replacing physical chips with a robust, real-time ledger while keeping the social excitement of physical cash handling.

## 🚀 Key Features

- **🏦 Digital Banker System**: A designated "Banker" manages physical cash (PHP), while the app maintains a strict digital ledger.
- **⚡ Real-Time Action**: Instant updates for bets, folds, and reveals powered by **Laravel Reverb**.
- **💳 Adjustable Debt Trap**: Players can bet into the negative (authorized debt), with aggressive UI highlighting for transparency.
- **🎰 Instant Side Bets**: Earn bonuses on the deal:
    - **Black Pair**: Pair of Spades or Clubs.
    - **One Eye**: One-eyed face cards (J♠, J♥, K♦).
    - **Aces**: Any Ace dealt.
- **📉 Rollover Pots**: Leftover money automatically seeds the next round's pot.
- **📱 Mobile-First Design**: Additive quick-bet buttons (+5, +10, +25) and an auto-calculating "Call" system.

## 🛠️ Tech Stack

- **Frontend**: React (Vite) + Styled Components + Laravel Echo (WebSockets)
- **Backend**: Laravel 11 (PHP 8.2+) + Sanctum (Auth) + Reverb (Real-time)
- **Database**: PostgreSQL (ACID compliant for financial transactions)
- **Real-Time**: WebSockets for sub-second synchronization.

## 📦 Installation & Setup

### Prerequisites
- PHP 8.2+
- Node.js & NPM
- PostgreSQL 15+
- Composer

### Backend Setup
1. Navigate to the `/backend` folder.
2. Install dependencies: `composer install`
3. Configure your `.env` (copy from `.env.example`).
4. Generate key: `php artisan key:generate`
5. Run migrations: `php artisan migrate`
6. Start Reverb: `php artisan reverb:start`
7. Start Server: `php artisan serve`

### Frontend Setup
1. Navigate to the `/frontend` folder.
2. Install dependencies: `npm install`
3. Configure your `.env` for Vite.
4. Start Development Server: `npm run dev`

## 🛡️ Financial Integrity
The system employs **strict double-entry ledger logic**. All wallet modifications are wrapped in database transactions to ensure that if a bet or payout fails at any point, the entire operation is rolled back, preventing "ghost money" or balance mismatches.

---
Designed for 🏡 family fun and 🃏 competitive showdowns.
