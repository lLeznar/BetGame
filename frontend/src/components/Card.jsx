import React from 'react';
import styled, { css } from 'styled-components';

const CardContainer = styled.div`
  width: ${props => props.$size === 'large' ? '80px' : '60px'};
  height: ${props => props.$size === 'large' ? '112px' : '84px'};
  border-radius: 8px;
  background-color: ${props => props.$faceDown ? 'var(--color-card-back)' : 'var(--color-card-white)'};
  box-shadow: var(--shadow-card);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: ${props => props.$size === 'large' ? '8px' : '6px'};
  position: relative;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  user-select: none;
  
  ${props => props.$faceDown && css`
    border: 2px solid rgba(255,255,255,0.1);
    background-image: repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(255,255,255,0.05) 10px, rgba(255,255,255,0.05) 20px);
  `}

  ${props => !props.$faceDown && css`
    color: ${props.$color};
    border: 1px solid #ccc;
  `}

  ${props => props.$interactive && css`
    cursor: pointer;
    &:hover {
      transform: translateY(-10px);
      box-shadow: 0 10px 25px rgba(0,0,0,0.5);
      border-color: var(--color-gold);
    }
  `}
`;

const Rank = styled.div`
  font-size: ${props => props.$size === 'large' ? '24px' : '18px'};
  font-weight: 800;
  line-height: 1;
`;

const Suit = styled.div`
  font-size: ${props => props.$size === 'large' ? '20px' : '16px'};
  line-height: 1;
`;

const CenterSuit = styled.div`
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: ${props => props.$size === 'large' ? '40px' : '30px'};
  opacity: 0.15;
`;

const suitSymbols = {
  hearts: '♥',
  diamonds: '♦',
  clubs: '♣',
  spades: '♠'
};

const suitColors = {
  hearts: '#ff1744',
  diamonds: '#ff1744',
  clubs: '#212121',
  spades: '#212121'
};

export const Card = ({ card, faceDown = false, size = 'normal', interactive = false }) => {
  if (faceDown || !card) {
    return <CardContainer $faceDown={true} $size={size} $interactive={interactive} />;
  }

  const symbol = suitSymbols[card.suit];
  const color = suitColors[card.suit];

  return (
    <CardContainer $faceDown={false} $color={color} $size={size} $interactive={interactive}>
      <div>
        <Rank $size={size}>{card.rank}</Rank>
        <Suit $size={size}>{symbol}</Suit>
      </div>
      <CenterSuit $size={size}>{symbol}</CenterSuit>
      <div style={{ transform: 'rotate(180deg)' }}>
         <Rank $size={size}>{card.rank}</Rank>
         <Suit $size={size}>{symbol}</Suit>
      </div>
    </CardContainer>
  );
};
