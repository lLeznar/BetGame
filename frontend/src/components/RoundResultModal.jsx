import React from 'react';
import styled from 'styled-components';
import { formatPeso } from '../utils/formatPeso';

const Overlay = styled.div`
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.8);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
`;

const Modal = styled.div`
  background: var(--color-surface);
  border: 2px solid var(--color-gold);
  border-radius: var(--radius);
  padding: 30px;
  width: 100%;
  max-width: 500px;
  text-align: center;
  box-shadow: 0 10px 40px rgba(0,0,0,0.5);
`;

const Title = styled.h2`
  color: var(--color-gold);
  font-size: 32px;
  margin-bottom: 20px;
  text-transform: uppercase;
  letter-spacing: 2px;
`;

const WinnerRow = styled.div`
  background: rgba(255,255,255,0.1);
  padding: 15px;
  border-radius: 8px;
  margin-bottom: 10px;
  display: flex;
  justify-content: space-between;
  align-items: center;
`;

const PlayerName = styled.div`
  font-weight: bold;
  font-size: 18px;
`;

const HandLabel = styled.div`
  font-size: 14px;
  color: #aaa;
`;

const PayoutAmount = styled.div`
  font-family: var(--font-mono);
  font-size: 20px;
  font-weight: bold;
  color: #4caf50;
`;

const ActionBtn = styled.button`
  background: var(--color-gold);
  color: black;
  font-weight: 800;
  padding: 15px 30px;
  border-radius: 8px;
  font-size: 18px;
  margin-top: 20px;
  width: 100%;
  
  &:active { transform: scale(0.95); }
`;

export const RoundResultModal = ({ result, onClose, isBanker, onNextRound }) => {
  if (!result) return null;

  return (
    <Overlay>
      <Modal>
        <Title>Round Over</Title>
        
        {result.winners && result.winners.length > 0 ? (
          result.winners.map(winner => (
            <WinnerRow key={winner.id}>
              <div>
                <PlayerName>{winner.user.name}</PlayerName>
                {winner.hand_label && <HandLabel>{winner.hand_label}</HandLabel>}
              </div>
              <PayoutAmount>+{formatPeso(result.payouts[winner.id] || 0)}</PayoutAmount>
            </WinnerRow>
          ))
        ) : (
          <WinnerRow style={{ justifyContent: 'center' }}>
            <PlayerName>No Winners</PlayerName>
          </WinnerRow>
        )}

        {result.pot_total > 0 && (!result.winners || result.winners.length === 0) && (
          <div style={{ marginTop: 10, color: '#aaa' }}>
             Pot rolls over to next round: {formatPeso(result.pot_total)}
          </div>
        )}

        <ActionBtn onClick={onClose}>Close</ActionBtn>
        
        {isBanker && onNextRound && (
          <ActionBtn onClick={() => { onClose(); onNextRound(); }} style={{ marginTop: 10, background: '#1976d2', color: 'white' }}>
            Setup Next Round
          </ActionBtn>
        )}
      </Modal>
    </Overlay>
  );
};
