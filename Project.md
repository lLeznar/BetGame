# Project: Family Showdown (Digital Poker Ledger)

## Overview

A real-time, 5-Card Draw (Showdown/Straight Poker) web application designed for family use. The system replaces physical chips with a strict digital ledger. One trusted family member acts as the "Banker" handling physical cash (PHP), while the application tracks all balances, bets, debts, and payouts.

## Tech Stack (The Vault)

- **Frontend:** React (for real-time, reactive UI and state management)
- **Backend:** Laravel (for API routing, validation, and business logic)
- **Database:** PostgreSQL (Strict ACID compliance, `JSONB` for game states, signed decimal columns for money)
- **Real-Time Engine:** Laravel Reverb / WebSockets (Instant broadcasting of bets, folds, and reveals)

## Core Game Mechanics

1. **The Deal:** Exactly 5 cards dealt per player. No trading, no drawing.
2. **The Goal:** Standard poker hand hierarchy (Royal Flush down to High Card).
3. **The Ledger:** A strict double-entry transaction table. Players cannot alter their balances; only the Banker can add physical cash to the digital system.

## Distinct Features & Chaos Rules

- **Adjustable Debt Trap:** Players can bet into the negative. Controlled by a strict `max_debt_limit` in the `JSONB` game settings. The UI will aggressively highlight negative balances in red.
- **Rollover Pots:** Any uncalled or leftover money at the end of a round automatically seeds the pot for the next game round.
- **Instant Side Bets:** Independent of the main pot. Evaluated and paid out the exact millisecond cards are dealt.
  - _Black Pair:_ Dealt a pair of Spades or Clubs.
  - _One Eye:_ Dealt a one-eyed face card (J♠, J♥, K♦).
  - _Aces:_ Dealt at least one Ace.
- **Additive Quick Bets:** Mobile-friendly quick-bet buttons (+5, +10, +15, +20 pesos) that add to a pending raise input field. The bet is not fired until the user hits a master "CONFIRM RAISE" button.
- **Auto-Math "Call" Button:** The system automatically calculates the delta between the table's highest bet and the player's current contribution, deducting only the difference.

## Database Guardrails (Non-Negotiable)

- All wallet balances must be `SIGNED` to allow for authorized negative balances.
- Moving money to a Rollover Pot or processing a bet must be wrapped in a strict `DB::transaction`. If one part fails, the whole payload rolls back.
- Total pot logic is dynamically calculated by summing all active bets for the current `game_id`, not stored as a single volatile column.
