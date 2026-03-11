import React, { useState } from 'react';
import styled from 'styled-components';

const Panel = styled.div`
  background: rgba(0,0,0,0.85);
  backdrop-filter: blur(10px);
  border-top: 2px solid var(--color-gold);
  padding: 20px;
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  display: flex;
  gap: 15px;
  z-index: 1000;
  justify-content: center;
  align-items: center;
  box-shadow: 0 -10px 40px rgba(0,0,0,0.5);
`;

const Btn = styled.button`
  background: #333;
  color: white;
  padding: 10px 20px;
  border-radius: 4px;
  font-weight: bold;
  border: 1px solid #555;
  
  &:hover:not(:disabled) {
    background: #555;
  }

  &:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }
`;

const SetupPhase = ({ players, onAddFunds }) => {
    const [selectedPlayer, setSelectedPlayer] = useState('');
    const [amount, setAmount] = useState('');

    const handleAdd = () => {
        if (selectedPlayer && amount && Number(amount) > 0) {
            onAddFunds(selectedPlayer, Number(amount));
            setAmount('');
        }
    }

    return (
        <div style={{ display: 'flex', gap: 10, alignItems: 'center' }}>
            <span style={{ color: 'var(--color-gold)' }}>Banker Controls:</span>
            <select value={selectedPlayer} onChange={e => setSelectedPlayer(e.target.value)} style={{ padding: 8 }}>
                <option value="">Select Player...</option>
                {players.map(p => <option key={p.id} value={p.id}>{p.user.name}</option>)}
            </select>
            <input 
                type="number" 
                value={amount} 
                onChange={e => setAmount(e.target.value)} 
                placeholder="Amount (₱)" 
                style={{ padding: 8, width: 100 }}
            />
            <Btn onClick={handleAdd} disabled={!selectedPlayer || !amount}>Add Funds</Btn>
        </div>
    )
}

export const BankerPanel = ({ 
  gamePhase, 
  players,
  onStartRound, 
  onSettleRound, 
  onAddFunds 
}) => {
  return (
    <Panel>
      {gamePhase === 'waiting' || gamePhase === 'finished' ? (
        <>
            <SetupPhase players={players} onAddFunds={onAddFunds} />
            <Btn onClick={onStartRound} style={{ background: '#4caf50', borderColor: '#388e3c' }}>
                Deal New Round
            </Btn>
        </>
      ) : null}

      {gamePhase === 'betting' ? (
        <Btn onClick={onSettleRound} style={{ background: '#f44336', borderColor: '#d32f2f' }}>
          Settle Round (Showdown)
        </Btn>
      ) : null}
      
      {/* dealing and showdown phases are mostly transient or handled by modals */}
    </Panel>
  );
};
