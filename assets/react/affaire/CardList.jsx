import React, {useCallback,useState,useEffect} from 'react';
import { Card } from './Card';
import update from 'immutability-helper';

export const CardList = ({board,affaireId,audienceId}) => {
  const [cards, setCards]=useState(board);
  const [timestamp, setTimestamp]=useState(Date.now());

  const moveCard = useCallback((dragIndex, hoverIndex) => {
    let newCards = cards;
    const tmp = cards[dragIndex];
    newCards[dragIndex] = cards[hoverIndex];
    newCards[hoverIndex] = tmp;
    setCards(newCards);
    setTimestamp(Date.now());
    const tab=[];
    newCards.forEach((item) => tab.push(item.id));
    const url = Routing.generate('_api_/audiences/{id}/positions_mise_a_jour_get_collection',{id: audienceId, affaire_ids: tab.join('-')});
    fetch(url);
  }, []);

  const container = cards;

  return (
    <>
    {container.map((affaire, index) => {
      return (
        <Card
          key={index}
          index={index}
          moveCard={moveCard}
          affaire={affaire}
          isSelected={affaire.id==affaireId}/>
      )
    })}
    </>
  );
}
