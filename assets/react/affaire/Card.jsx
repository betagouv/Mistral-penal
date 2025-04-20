import React, {useRef} from 'react';
import { useDrag, useDrop } from 'react-dnd';

export const Card = ({index,affaire,isSelected,board,setBoard,moveCard}) => {
  const ref = useRef(null);
  const url = Routing.generate('affaire_edit_general',{affaireId: affaire.id});
  const style = {};
  let classes = "fr-pb-1w fr-grid-row list-affaire affaire-line";
  if(isSelected)
    classes+=" affaire-selected";

  const [{ handlerId }, drop] = useDrop({
    accept: "Card",
    collect(monitor) {
      return { handlerId: monitor.getHandlerId()}
    },
    hover(item, monitor) {
      if(!ref.current) {
        return;
      }
      const dragIndex = item.index;
      const hoverIndex = index;

      if(dragIndex === hoverIndex)
        return;

      const hbr = ref.current?.getBoundingClientRect();
      const hoverMiddleY = (hbr.bottom - hbr.top) / 2;
      const clientOffset = monitor.getClientOffset();
      const hoverClientY = clientOffset.y - hbr.top;

      if(
        (dragIndex < hoverIndex && hoverClientY < hoverMiddleY)
        ||
        (dragIndex > hoverIndex && hoverClientY > hoverMiddleY)
      )
        return;

      moveCard(dragIndex, hoverIndex);

      item.index = hoverIndex;
    }
  });

  const [{ isDragging }, drag] = useDrag({
    type: "Card",
    item: () => {
      return {id: affaire.id,index: index};
    },
    collect: (monitor) => ({
      isDragging: monitor.isDragging()
    })
  });

  drag(drop(ref));
  const opacity = isDragging ? 0 : 1;
  return (
    <div className={classes} ref={ref} style={{ ...style, opacity}} data-hander-id={handlerId}>
      <div className="fr-col-12 fr-pt-1w"></div>
      <div className="fr-col-1 fr-pl-1v affaire-index">{index+1}</div>
      <div className="fr-col-11 fr-pl-1v dossier-label">N° {affaire.numeroDossier}</div>
      <div className="fr-col-12 fr-pt-1w"></div>
      <div className="fr-col-12">
        <div className="fr-grid-row">
          <div className="fr-col-1 fr-pl-1v">
            <div className="goulotte-gauche-personnes">
              <i className="ri-menu-line" style={{color:"#000091"}}></i>
            </div>
          </div>
          <div className="fr-col-11">
            <a
              style={{textDecoration: 'none',width: '100%', backgroundImage: 'none !important'}}
              href={url}
              data={affaire.id}
            >
              <Users label="Prévenu.e.s" type="prevenu" affaire={affaire} />
              <Users label="Victimes / Parties civiles" type="victime" affaire={affaire} />
              <Users label="Jugé.e.s" type="juge" affaire={affaire} hideOnEmpty={true}/>
            </a>
          </div>
        </div>
      </div>
    </div>
  )
}

const Users = ({label,type,affaire,hideOnEmpty=false}) => {
  let users = {};
  if(affaire[type+'s'])
    users = affaire[type+'s'];
  let userString = "";
  //display only the first 4 users
  for(const [key,user] of Object.entries(users).slice(0,4)) {
    const tmp = user[key];
    userString+=(userString.length ? "; " : "")+user['nomComplet'];
  }
  const test = (true===hideOnEmpty && userString.length)||(false===hideOnEmpty);
  return (
    test &&
    <div className="fr-col-11 fr-pl-1w">
      <div className="dossier-label">{label}</div>
      <div className="dossier-content" data-type={type} data-affaire-id={affaire.id}>
      {userString}
      </div>
    </div>
  );
}
