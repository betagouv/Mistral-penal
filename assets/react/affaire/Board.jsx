import React,{ useEffect, useState, useRef } from 'react';
import { DndProvider, useDrag, useDrop } from "react-dnd";
import {HTML5Backend} from "react-dnd-html5-backend";
import '../../styles/audience/board.css';
import { CardList } from './CardList';

export const Board = ({audience,affaireId}) => {

  const [loading,setLoading]=useState(false);
  const [board,setBoard]=useState(audience.affaires);

  useEffect(() => {
    if(true === loading)
      return;
    setLoading(true);
  },[]);

  return (
    <DndProvider backend={HTML5Backend}>
    {loading &&
      <CardList audienceId={audience.id} board={board} affaireId={affaireId} />
    }
    {!loading &&
      <div>Chargement en cours</div>
    }
    </DndProvider>
  );
}
