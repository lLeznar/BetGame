import React, { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import api from '../services/api';
import { formatPeso } from '../utils/formatPeso';

const badgeColors = {
  buy_in: { bg: '#1976d2', color: 'white' },
  payout: { bg: '#388e3c', color: 'white' },
  side_bet_payout: { bg: '#fbc02d', color: 'black' },
  bet: { bg: '#d32f2f', color: 'white' },
  ante: { bg: '#d32f2f', color: 'white' },
};

export const LedgerPage = () => {
  const { gameId } = useParams();
  const [transactions, setTransactions] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchLedger = async () => {
      try {
        const res = await api.get(`/games/${gameId}/transactions`);
        setTransactions(res.data);
      } catch (err) {
        console.error(err);
      } finally {
        setLoading(false);
      }
    };
    fetchLedger();
  }, [gameId]);

  if (loading) return (
    <div style={{ padding: 40, textAlign: 'center', color: 'white', fontFamily: 'var(--font-display)' }}>
      Loading Ledger...
    </div>
  );

  return (
    <div style={{ maxWidth: 860, margin: '0 auto', padding: 20, fontFamily: 'var(--font-display)' }}>
      <h1 style={{
        color: 'var(--color-gold)',
        marginBottom: 30,
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center'
      }}>
        <span>🃏 Game #{gameId} Ledger</span>
        <Link
          to={`/game/${gameId}`}
          style={{
            fontSize: 14,
            color: 'white',
            textDecoration: 'none',
            background: 'rgba(255,255,255,0.1)',
            padding: '8px 18px',
            borderRadius: 8,
            border: '1px solid rgba(255,255,255,0.15)',
            transition: 'background 0.2s'
          }}
        >
          ← Back to Table
        </Link>
      </h1>

      <div style={{
        background: 'var(--color-surface)',
        borderRadius: 'var(--radius)',
        overflow: 'hidden',
        boxShadow: 'var(--shadow-card)',
        backdropFilter: 'blur(10px)',
        border: '1px solid rgba(255,255,255,0.08)'
      }}>
        <table style={{ width: '100%', borderCollapse: 'collapse' }}>
          <thead>
            <tr>
              {['Time', 'Type', 'From', 'To', 'Amount', 'Memo'].map(h => (
                <th key={h} style={{
                  padding: '14px 16px',
                  textAlign: 'left',
                  background: 'rgba(255,255,255,0.05)',
                  color: '#aaa',
                  fontWeight: 600,
                  fontSize: 12,
                  textTransform: 'uppercase',
                  letterSpacing: '0.05em',
                  borderBottom: '1px solid rgba(255,255,255,0.1)'
                }}>{h}</th>
              ))}
            </tr>
          </thead>
          <tbody>
            {transactions.map((tx, i) => (
              <tr key={tx.id} style={{ background: i % 2 === 0 ? 'transparent' : 'rgba(255,255,255,0.02)' }}>
                <td style={{ padding: '12px 16px', color: '#666', fontSize: 12 }}>
                  {new Date(tx.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' })}
                </td>
                <td style={{ padding: '12px 16px' }}>
                  <span style={{
                    padding: '3px 8px',
                    borderRadius: 4,
                    fontSize: 11,
                    fontWeight: 700,
                    textTransform: 'uppercase',
                    letterSpacing: '0.05em',
                    background: (badgeColors[tx.type] || { bg: '#555' }).bg,
                    color: (badgeColors[tx.type] || { color: 'white' }).color,
                  }}>{tx.type.replace('_', ' ')}</span>
                </td>
                <td style={{ padding: '12px 16px', color: 'white' }}>
                  {tx.from_player ? tx.from_player.user?.name : <span style={{ color: '#444' }}>— Bank —</span>}
                </td>
                <td style={{ padding: '12px 16px', color: 'white' }}>
                  {tx.to_player ? tx.to_player.user?.name : <span style={{ color: '#444' }}>— Pot —</span>}
                </td>
                <td style={{
                  padding: '12px 16px',
                  fontFamily: 'var(--font-mono)',
                  fontWeight: 700,
                  color: (tx.type?.includes('payout') || tx.type === 'buy_in') ? '#4caf50' : '#ef5350'
                }}>
                  {formatPeso(tx.amount)}
                </td>
                <td style={{ padding: '12px 16px', fontSize: 13, color: '#888' }}>{tx.memo}</td>
              </tr>
            ))}
            {transactions.length === 0 && (
              <tr>
                <td colSpan={6} style={{ textAlign: 'center', padding: 40, color: '#444' }}>
                  No transactions recorded yet.
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
};
