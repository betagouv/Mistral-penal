import React, {useState, createContext, useContext} from 'react';
import PersonneType from '../../personne/';
import RepresentantLegalType from '../../representantLegal/';
import {refresh_rl_container_by_id} from '../../../js/affaire/representant_legal';
import {writeSuccessNotification,writeNotification} from '../../../js/serviceWorker/notification';

export const ModalContext = createContext();

export const ModalProvider = ({children}) => {
  const [personne, setPersonne]=useState(null);
  const [adresse, setAdresse]=useState(null);
  const [representantLegal, setRepresentantLegal]=useState(null);

  return (
    <ModalContext.Provider value={{adresse, personne, representantLegal,
      setAdresse, setPersonne, setRepresentantLegal}}>
    {children}
    </ModalContext.Provider>
  );
}

export const ModalHeader = function({title, closeLabel}) {
  return (
    <div className="fr-modal__header">
        <span className="natinf-title">{title}</span>
        <button
          className="fr-btn--close fr-btn"
          aria-controls="modale-representant-legal"
          title={closeLabel}>
          {closeLabel}
        </button>
        <div className="auto-record-status" style={{display:'none'}}>
          <p className="fr-badge fr-badge--new fr-badge auto-record-badge-new">ENREGISTREMENT EN COURS</p>
        </div>
    </div>
  );
}

export const ModalContent = function({rand,personneId,representantLegalId,start, ending}) {
  return (
    <div className="fr-modal__content">
      <RepresentantLegalType
        rand={rand}
        id={representantLegalId}
      />
      <PersonneType
        rand={rand}
        id={personneId}
        start={start}
        ending={ending}
      />
    </div>
  )
}

export const Modal = function({title, closeLabel, submitLabel, rand, representantLegalId, personneId, start, ending, closing}) {
  return (
    <ModalProvider>
      <div className="fr-modal__body">
        <ModalHeader title={title} closeLabel={closeLabel} />
        <ModalContent rand={rand} representantLegalId={representantLegalId} personneId={personneId} start={start} ending={ending} />
        <ModalFooter submitLabel={submitLabel} closeLabel={closeLabel}/>
      </div>
    </ModalProvider>
  );
}
export const ModalFooter = function({submitLabel, closeLabel}) {

  const {personne,adresse,representantLegal} = useContext(ModalContext);
  const [activateBtn,setActivateBtn]=useState(true);
  async function updatePersonne(p) {
    const url = Routing.generate('_api_/personnes/{id}{._format}_put', {id: p.id});

    if(p['nationalite']) {
      if(p.nationalite["@id"])
        p.nationalite=p.nationalite["@id"];
    }
    else
      delete p.nationalite;

    if(p['civilite']) {
      if(p.civilite["@id"])
        p.civilite=p.civilite["@id"];
    }
    else
      delete p.civilite;

    if(!p['communeNaissance'])
      delete p.communeNaissance;

    return await fetch(url,{
      method:'PUT',
      body:JSON.stringify(p),
      headers: { "Content-type": "application/json; charset=UTF-8", }
    })
    .then(response => response.json())
    .then((data) => data)
    .catch((e) => console.error(e))
    ;
  }

  async function updateAdresse(a) {
    const url = Routing.generate('_api_/adresses/{id}{._format}_put', {id: a.id});
    if(!a['pays'])
      delete a.pays;
    return await fetch(url,{
      method:'PUT',
      body:JSON.stringify(a),
      headers: { "Content-type": "application/json; charset=UTF-8", }
    })
    .then(response => response.json())
    .then((data) => data)
    .catch((e) => console.error(e))
    ;
  }

  async function updateRepresentantLegal(rl) {
    const url = Routing.generate('_api_/representant_legals/{id}{._format}_put', {id: rl.id});

    if(!rl['lienSocial'])
      delete rl.lienSocial;
    if(!rl['lienJuridique'])
      delete rl.lienJuridique;

    return await fetch(url,{
      method:'PUT',
      body:JSON.stringify(rl),
      headers: { "Content-type": "application/json; charset=UTF-8", }
    })
    .then(response => response.json())
    .then((data) => data)
    .catch((e) => console.error(e))
    ;
  }

  function handleClick() {
    setActivateBtn(false);
    writeNotification("EN COURS");
    Promise.all([
      updatePersonne(personne),
      updateAdresse(adresse),
      updateRepresentantLegal(representantLegal)
    ]).then(([dataPersonne,dataAdresse,dataRl]) => {
      setActivateBtn(true);
      const representeId = dataRl.represente.id;
      refresh_rl_container_by_id(representeId);
      setTimeout(() => {writeSuccessNotification("ENREGISTRE");$(".fr-btn--close").click();}, 1000);
    })
      .catch((e) => console.error(e))
    ;
  }

  return (
    <div className="fr-modal__footer">
      <ul
        className="fr-btns-group fr-btns-group--right fr-btns-group--inline-reverse fr-btns-group--inline-lg fr-btns-group--icon-left"
      >
          <li>
              <button
                className="fr-btn fr-icon-checkbox-line"
                onClick={handleClick}
                disabled={!activateBtn}
              >
              {submitLabel}
              </button>
              <button
                className="fr-btn--close fr-btn"
                aria-controls="modale-representant-legal"
                title={closeLabel}
                disabled={!activateBtn}
              >
                {closeLabel}
              </button>
          </li>
      </ul>
    </div>
  );
}
