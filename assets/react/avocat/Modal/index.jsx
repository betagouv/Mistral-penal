import React from 'react';
import AvocatType from '../../avocat/';

export const ModalContent = function() {
  return (
    <div className="fr-modal__content">
      <AvocatType />
    </div>
  );
}

export const ModalHeader = function({title, closeLabel}) {
  return (
    <div className="fr-modal__header">
        <span className="natinf-title">{title}</span>
        <button
          onClick={(event) => event.preventDefault()}
          className="fr-btn--close fr-btn"
          aria-controls="modale-avocat"
          title={closeLabel}>
          {closeLabel}
        </button>
    </div>
  );
}

const ModalFooter = ({submitLabel}) => {
  return (
    <div className="fr-modal__footer">
      <div className="fr-btns-group fr-btns-group--right fr-btns-group--inline-reverse fr-btns-group--inline-lg fr-btns-group--icon-left">
        <button
          name="add_avocat"
          type="submit"
          className="fr-btn fr-icon-checkbox-circle-line fr-btn--icon-left fr-btn--secondary"
        >
        {submitLabel}
        </button>
      </div>
    </div>
  );
}
export const AvocatModal = ({title,closing,submitLabel,handleSubmit}) => {
  return (
    <div className="fr-modal__body">
      <form method="POST" id="avocat-form" action={Routing.generate('_api_/avocats{._format}_post')} onSubmit={handleSubmit}>
        <ModalHeader title={title} closeLabel={"fermer"}/>
        <ModalContent />
        <ModalFooter submitLabel={submitLabel} />
      </form>
    </div>
  )
}
