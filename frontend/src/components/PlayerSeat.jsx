import React from 'react';
import styled, { css, keyframes } from 'styled-components';
import { formatPeso } from '../utils/formatPeso';

const pulse = keyframes`
  0% { box-shadow: 0 0 0 0 rgba(255, 23, 68, 0.7); }
  70% { box-shadow: 0 0 0 10px rgba(255, 23, 68, 0); }
  100% { box-shadow: 0 0 0 0 rgba(255, 23, 68, 0); }
`;

const SeatContainer = styled.div`
  background: var(--color-surface);
  border-radius: var(--radius);
  padding: 12px;
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 120px;
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.1);
  transition: all 0.3s ease;
  
  ${props => props.$isMe && css`
    border-color: var(--color-gold);
    box-shadow: 0 0 15px rgba(212, 175, 55, 0.2);
  `}

  ${props => props.$hasDebt && css`
    border-color: var(--color-red-debt);
    animation: ${pulse} 2s infinite;
  `}

  ${props => props.$isFolded && css`
    opacity: 0.5;
    filter: grayscale(1);
  `}
`;

const Avatar = styled.div`
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: ${props => props.$color || '#1976d2'};
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 18px;
  margin-bottom: 8px;
`;

const Name = styled.div`
  font-weight: 600;
  font-size: 14px;
  margin-bottom: 4px;
`;

const Balance = styled.div`
  font-family: var(--font-mono);
  font-size: 14px;
  color: ${props => props.$hasDebt ? 'var(--color-red-debt)' : '#4caf50'};
  font-weight: bold;
`;

const Badge = styled.div`
  background: ${props => props.$color || 'rgba(255,255,255,0.2)'};
  font-size: 10px;
  padding: 2px 6px;
  border-radius: 4px;
  margin-top: 4px;
  text-transform: uppercase;
  font-weight: bold;
  letter-spacing: 1px;
`;

export const PlayerSeat = ({ player, isMe = false }) => {
  if (!player || !player.user) return null;

  const initial = player.user.name ? player.user.name.charAt(0).toUpperCase() : '?';
  const hasDebt = parseFloat(player.wallet_balance) < 0;

  return (
    <SeatContainer $isMe={isMe} $hasDebt={hasDebt} $isFolded={player.is_folded}>
      <Avatar $color={isMe ? 'var(--color-gold)' : '#1976d2'}>
        {initial}
      </Avatar>
      <Name>{player.user.name} {isMe && '(You)'}</Name>
      <Balance $hasDebt={hasDebt}>
        {formatPeso(player.wallet_balance)}
      </Balance>
      {player.is_folded && <Badge $color="#9e9e9e">Folded</Badge>}
      {player.user.is_banker && <Badge $color="#d4af37" style={{ color: 'black' }}>Banker</Badge>}
    </SeatContainer>
  );
};
