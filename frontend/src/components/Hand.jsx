import React from 'react';
import styled from 'styled-components';
import { Card } from './Card';

const HandContainer = styled.div`
  display: flex;
  gap: ${props => props.$fan ? '-20px' : '8px'};
  justify-content: center;
  align-items: center;
  padding: 10px;
  
  /* Fan effect */
  & > div {
    transition: transform 0.3s ease;
  }
  
  ${props => props.$fan && `
    &:hover > div {
      transform: translateY(-15px);
      margin: 0 5px;
    }
  `}
`;

export const Hand = ({ hand, faceDown = false, size = 'normal', interactive = false, fan = false }) => {
  if (!hand || hand.length === 0) return null;

  return (
    <HandContainer $fan={fan}>
      {hand.map((card, idx) => (
        <Card 
          key={idx} 
          card={card} 
          faceDown={faceDown} 
          size={size} 
          interactive={interactive} 
        />
      ))}
    </HandContainer>
  );
};
