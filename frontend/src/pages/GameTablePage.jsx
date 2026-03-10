import React, { useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import styled from 'styled-components';
import { useGame } from '../hooks/useGame';
import { useAuth } from '../hooks/useAuth';
import { PlayerSeat } from '../components/PlayerSeat';
import { PotDisplay } from '../components/PotDisplay';
import { Hand } from '../components/Hand';
import { BettingPanel } from '../components/BettingPanel';
import { ActionBar } from '../components/ActionBar';
import { BankerPanel } from '../components/BankerPanel';
import { RoundResultModal } from '../components/RoundResultModal';
import { SideBetBadge } from '../components/SideBetBadge';

const TableArea = styled.div`
  position: relative;
  width: 100%;
  max-width: 800px;
  height: 400px;
  margin: 40px auto;
  border: 15px solid #3e2723;
  border-radius: 200px;
  background: var(--color-felt-dark);
  box-shadow: inset 0 0 50px rgba(0,0,0,0.8), 0 20px 50px rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
`;

const SeatsWrapper = styled.div`
  position: absolute;
  top: -40px; left: -40px; right: -40px; bottom: -40px;
  pointer-events: none;
`;

const Header = styled.div`
  display: flex;
  justify-content: space-between;
  padding: 20px;
  align-items: center;
`;

const MyArea = styled.div`
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
  padding: 20px;
  margin-top: 40px;
  max-width: 800px;
  margin-left: auto;
  margin-right: auto;
`;

export const GameTablePage = () => {
  const { gameId } = useParams();
  const { user } = useAuth();
  const navigate = useNavigate();
  const { 
    game, 
    myHand, 
    fetchGameState, 
    placeBet, 
    call, 
    fold, 
    startRound, 
    settleRound, 
    addFunds,
    getCallAmount,
    joinGame
  } = useGame();
  
  const [callAmountDelta, setCallAmountDelta] = React.useState(0);
  const [showResult, setShowResult] = React.useState(null); // { winners, payouts, pot_total }
  const [sideBetHit, setSideBetHit] = React.useState(null); // { type, payout }

  // Initial fetch
  useEffect(() => {
    fetchGameState(gameId);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [gameId]);

  // Update call amount when betting phase changes or pot updates
  useEffect(() => {
    if (game?.phase === 'betting') {
        getCallAmount().then(setCallAmountDelta);
    }
  }, [game?.current_pot, game?.phase, getCallAmount]);

  if (!game) return <div style={{color: 'white', padding: 40, textAlign: 'center'}}>Loading Table...</div>;

  const myPlayerInfo = game.game_players?.find(p => p.user_id === user.id);
  const otherPlayers = game.game_players?.filter(p => p.user_id !== user.id) || [];
  
  // Arrange other players around the table dynamically (simplified for 2-6 players)
  const getSeatStyle = (index, total) => {
    // simplified positioning around an oval
    if (total === 1) return { top: '-20px', left: '50%', transform: 'translateX(-50%)' };
    if (total === 2) return index === 0 ? { top: '50%', left: '-20px', transform: 'translateY(-50%)' } : { top: '50%', right: '-20px', transform: 'translateY(-50%)' };
    // fallback for more
    const positions = [
        { top: '-20px', left: '50%', transform: 'translateX(-50%)' },
        { top: '30%', right: '-20px' },
        { top: '30%', left: '-20px' },
        { bottom: '30%', right: '-20px' },
        { bottom: '30%', left: '-20px' },
    ];
    return positions[index] || positions[0];
  };

  const isMyTurn = game.phase === 'betting' && myPlayerInfo && !myPlayerInfo.is_folded;

  return (
    <>
      <Header>
        <div style={{ color: '#aaa' }}>Table: <strong style={{ color: 'white' }}>#{game.id}</strong></div>
        <div style={{ display: 'flex', gap: 15 }}>
          <Link to={`/game/${game.id}/ledger`} style={{ color: 'var(--color-gold)', textDecoration: 'none', fontWeight: 'bold' }}>
            View Ledger
          </Link>
          <button onClick={() => navigate('/lobby')} style={{ color: '#f44336' }}>Leave Table</button>
        </div>
      </Header>

      <TableArea>
        <PotDisplay amount={game.current_pot || 0} />
        
        <SeatsWrapper>
          {otherPlayers.map((p, idx) => (
            <div key={p.id} style={{ position: 'absolute', pointerEvents: 'auto', ...getSeatStyle(idx, otherPlayers.length) }}>
              <PlayerSeat player={p} />
            </div>
          ))}
        </SeatsWrapper>

        {sideBetHit && (
          <SideBetBadge type={sideBetHit.type} payout={sideBetHit.payout} onComplete={() => setSideBetHit(null)} />
        )}
      </TableArea>

      {/* Current Player Area */}
      {myPlayerInfo ? (
        <MyArea>
          <PlayerSeat player={myPlayerInfo} isMe={true} />
          
          <div style={{ margin: '20px 0' }}>
            {myHand && myHand.length > 0 ? (
                <Hand hand={myHand} size="large" interactive={true} fan={true} />
            ) : (
                <div style={{ color: '#555', fontStyle: 'italic' }}>Waiting for cards...</div>
            )}
          </div>

          <ActionBar 
            disabled={!isMyTurn}
            onCall={call}
            onFold={fold}
            callAmount={callAmountDelta}
          />
          
          <BettingPanel 
            disabled={!isMyTurn}
            onConfirmRaise={(amount) => placeBet('raise', amount)}
          />
        </MyArea>
      ) : (
        <MyArea>
          <div style={{ textAlign: 'center', background: 'var(--color-surface)', padding: '30px', borderRadius: 'var(--radius)', border: '1px solid rgba(255,255,255,0.1)' }}>
            <h3>You are spectating</h3>
            <p style={{ color: '#aaa', margin: '10px 0 20px' }}>Join the table to start playing!</p>
            <div style={{ display: 'flex', gap: 10 }}>
                <input 
                    id="join-buyin" 
                    type="number" 
                    placeholder="Buy-in Amount" 
                    defaultValue="100" 
                    style={{ padding: '10px', borderRadius: '8px', border: 'none', width: '120px' }} 
                />
                <button 
                  onClick={() => {
                    const amount = document.getElementById('join-buyin').value;
                    joinGame(game.id, parseFloat(amount));
                  }}
                  style={{ background: 'var(--color-gold)', color: 'black', padding: '10px 20px', borderRadius: '8px', fontWeight: 'bold' }}
                >
                  Join Table
                </button>
            </div>
          </div>
        </MyArea>
      )}

      {user.is_banker && (
        <BankerPanel 
          gamePhase={game.phase} 
          players={game.game_players || []}
          onStartRound={startRound}
          onSettleRound={settleRound}
          onAddFunds={addFunds}
        />
      )}

      <RoundResultModal 
        result={showResult} 
        isBanker={user.is_banker} 
        onClose={() => setShowResult(null)} 
        onNextRound={startRound}
      />
      
    </>
  );
};
