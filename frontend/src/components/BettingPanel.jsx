import React, { useState } from 'react';
import styled from 'styled-components';
import { formatPeso } from '../utils/formatPeso';

const Panel = styled.div`
  background: var(--color-surface);
  border-radius: var(--radius);
  padding: 16px;
  width: 100%;
  max-width: 400px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  border: 1px solid rgba(255,255,255,0.1);
`;

const Display = styled.div`
  font-family: var(--font-mono);
  font-size: 24px;
  text-align: right;
  padding: 10px;
  background: rgba(0,0,0,0.3);
  border-radius: 8px;
  border: 1px solid rgba(255,255,255,0.2);
  color: var(--color-gold);
`;

const QuickChips = styled.div`
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
`;

const ChipBtn = styled.button`
  background: #1976d2;
  color: white;
  font-weight: bold;
  padding: 12px 0;
  border-radius: 8px;
  font-size: 16px;
  box-shadow: 0 4px 0 #1565c0;
  
  &:active {
    box-shadow: 0 0 0 #1565c0;
    transform: translateY(4px);
  }
`;

const Actions = styled.div`
  display: flex;
  gap: 8px;
`;

const ActionBtn = styled.button`
  flex: 1;
  padding: 14px;
  border-radius: 8px;
  font-weight: 800;
  text-transform: uppercase;
  font-size: 16px;
  color: white;
  
  background: ${props => props.$primary ? '#4caf50' : '#f44336'};
  box-shadow: 0 4px 0 ${props => props.$primary ? '#388e3c' : '#d32f2f'};
  
  &:active {
    box-shadow: 0 0 0;
    transform: translateY(4px);
  }

  &:disabled {
    background: #555;
    box-shadow: 0 4px 0 #333;
    opacity: 0.7;
    cursor: not-allowed;
  }
`;

export const BettingPanel = ({ onConfirmRaise, disabled }) => {
  const [pendingAmount, setPendingAmount] = useState(0);

  const addAmount = (val) => {
    setPendingAmount(prev => prev + val);
  };

  const handleConfirm = () => {
    if (pendingAmount > 0) {
      onConfirmRaise(pendingAmount);
      setPendingAmount(0);
    }
  };

  return (
    <Panel>
      <Display>{formatPeso(pendingAmount)}</Display>
      
      <QuickChips>
        <ChipBtn disabled={disabled} onClick={() => addAmount(5)}>+5</ChipBtn>
        <ChipBtn disabled={disabled} onClick={() => addAmount(10)}>+10</ChipBtn>
        <ChipBtn disabled={disabled} onClick={() => addAmount(15)}>+15</ChipBtn>
        <ChipBtn disabled={disabled} onClick={() => addAmount(20)}>+20</ChipBtn>
      </QuickChips>
      
      <Actions>
        <ActionBtn disabled={disabled} onClick={() => setPendingAmount(0)}>Reset</ActionBtn>
        <ActionBtn $primary disabled={disabled || pendingAmount === 0} onClick={handleConfirm}>
          Bet / Raise
        </ActionBtn>
      </Actions>
    </Panel>
  );
};
