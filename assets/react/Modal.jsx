import React, {Component} from 'react';

import {updateAudience} from '../js/import/audience';

const texts = {
    "ready" : {
        "img" : "success.svg",
        "title" : "Votre audience est prête!",
        "editButton" : "Éditer dans Mistral",
        "downloadButton" : "Documents d'audience (ZIP)",
        "showRefresh" : true,
        "description" : "Vous pouvez exporter le rôle et l'ensemble des notes d'audience ou éditer votre audience directement dans Mistral pour gagner encore plus de temps !"
    },
    "error" : {
        "img" : "error.svg",
        "title" : "Erreur d'import",
        "editButton" : "Annuler",   
        "downloadButton" : "Relancer l'import",
        "showRefresh" : false,
        "description" : "Nous n'avons pas pu importer les dossiers de l'audience. Vérifiez votre réseau et rééssayez."
    },
    "ended" : {
        "img" : "data-visualization.svg",
        "title" : "Cette audience est terminée",
        "editButton" : "Voir dans Mistral",
        "downloadButton" : "Documents d'audience (ZIP)",
        "showRefresh" : false,
        "description" : "Vous pouvez exporter le rôle et l'ensemble des notes d'audience ou éditer votre audience directement dans Mistral pour gagner encore plus de temps !"
    },
    "downloaded" : {
        "img" : "success.svg",
        "title" : "Votre ZIP est téléchargé",
        "editButton" : "Tenir l’audience dans Mistral",
        "downloadButton" : "Relancer l’export (ZIP)",
        "showRefresh" : false,
        "description" : "Retrouvez votre dossier ZIP dans le dossier téléchargement de votre ordinateur."
    },
    "import": {
        "img" : "system.svg",
        "title" : "Import des dossiers en cours...",
        "editButton" : "Annuler",
        "downloadButton" : "",
        "showRefresh" : false,
        "description" : "Nous récupérons les dossiers de l'audience dans Cassiopee. Ne fermez pas cette fenêtre jusqu'à la fin de l'import. Les dossiers seront conservés sur Mistral pendant 20 jours après la date de l'audience."
    }
}

class Modal extends Component {
    constructor( props ) {
        super();

        this.state = {
            img : texts.ready.img,
            downloadLink:props.downloadLink,
            id: props.id,
            url: props.url,
            jour: props.jour,
            mois: props.mois,
            annee: props.annee,
            debut: props.debut,
            miseAJour: props.miseAJour,
            serviceLabel: props.serviceLabel,
            idKsp: props.idKsp,
            showRefresh: texts.ready.showRefresh,
            title : texts.ready.title,
            titleEditButton : texts.ready.editButton,
            titleDownloadButton : texts.ready.downloadButton,
            description : texts.ready.description,
            finAudience : props.finAudience
        };

        this.handleEdit = this.handleEdit.bind(this);
        this.handleUpdate = this.handleUpdate.bind(this);
        this.handleDownload = this.handleDownload.bind(this);
        this.updateTexts = this.updateTexts.bind(this);

    }

    componentDidUpdate(prevProps){
        const { downloadLink, id, url, jour, mois, annee, debut, miseAJour, serviceLabel, idKsp, finAudience } = this.props;
        if(
            miseAJour!= this.state.miseAJour 
            || serviceLabel != this.state.serviceLabel
            || mois != this.state.mois
            || idKsp != this.state.idKsp
            ){

            let newtexts = texts.ready;
            if(finAudience) newtexts = texts.ended;

            this.setState (
                {
                    downloadLink:downloadLink,
                    img: newtexts.img,
                    id: id,
                    url: url,
                    jour: jour,
                    mois: mois,
                    annee: annee,
                    debut: debut,
                    miseAJour: miseAJour,
                    serviceLabel: serviceLabel,
                    idKsp: idKsp,
                    showRefresh: newtexts.showRefresh,
                    title : newtexts.title,
                    titleEditButton : newtexts.editButton,
                    titleDownloadButton : newtexts.downloadButton,
                    description : newtexts.description,
                    finAudience : finAudience
                }
            );
        }
    }

    handleClose(){
        let selector = "#fr-modal-calendar-audience";
        let element = $(selector)[0]; // Reference à l'element du DOM
        dsfr(element).modal.conceal(); // Méthode pour fermer manuellement la modale
    }

    handleEdit(){
        window.location.replace(this.state.url);
    }

    handleDownload(){
        setTimeout( this.updateTexts('downloaded'), 100);
    }

    updateTexts(key){
        this.setState({
            title : texts[key].title,
            titleEditButton : texts[key].editButton,
            titleDownloadButton: texts[key].downloadButton,
            description : texts[key].description,
            showRefresh : texts[key].showRefresh
        })
    }

    handleUpdate(){
        updateAudience(this.state.id, this.state.idKsp,this.state.jour, this.state.mois, this.state.annee);
        this.handleClose();
    }

    getDate(){
        const date = new Date(this.state.annee+"-"+this.state.mois+"-"+this.state.jour);
        let options = {
            weekday: "long",
            year: "numeric",
            month: "long",
            day: "numeric",
          };

        return new Intl.DateTimeFormat("fr-FR", options).format(date);
    }

    render() {

        return (
            <dialog 
                aria-labelledby="fr-modal-decision-title" 
                id="fr-modal-calendar-audience" 
                className="fr-modal" 
                role="dialog"
                open
            >
                <div className="fr-container fr-container--fluid fr-container-md">
                    <div className="fr-grid-row fr-grid-row--center">
                        <div className="fr-col-12 fr-col-md-8">
                            <div className="fr-modal__body">
                                <div className="fr-modal__header">
                                    <button onClick={this.handleClose} className="fr-btn--tertiary-no-outline fr-btn--close fr-btn" aria-controls="fr-modal-2">Fermer</button>
                                </div>
                                <div className="fr-modal__content">
                                    <div className="fr-grid-row ">
                                        <div className="fr-col-2 fr-p-1w">
                                        <img
                                            src={"/build/images/"+this.state.img}
                                            alt="success"
                                            height="80px"
                                            width="80px"

                                        />
                                        </div>
                                        <div className="fr-col-10 fr-pt-4w">
                                            <h1 id="fr-modal-title-modal-3" className="fr-modal-audience-title">
                                                {this.state.title}
                                            </h1>
                                        </div>
                                    </div>
                                    <div className="fr-grid-row ">
                                        <div className="fr-col-12 fr-modal-audience-date fr-pl-1w">
                                            {this.getDate()} - {this.state.debut}
                                        </div>

                                        <div className="fr-col-12 fr-modal-audience-service fr-pt-1w fr-pl-1w">
                                             {this.state.serviceLabel}
                                        </div>

                                        <div className="fr-col-6 fr-pt-1w fr-pl-1w">
                                            <span className="fr-modal-audience-update">{this.state.miseAJour}</span>
                                        </div>
                                        <div className="fr-col-6 fr-pr-1w">
                                            {
                                                this.state.showRefresh? (
                                                    <button onClick={this.handleUpdate} className="fr-btn fr-btn--icon-left fr-btn--secondary align-right" aria-controls="fr-modal-2">
                                                        <i className="ri-refresh-line fr-pr-1w"></i>
                                                        Importer depuis Cassiopée
                                                    </button>
                                                ) : null
                                            }
                                        </div>

                                        <div className="fr-col-12 fr-px-2v">
                                            <img
                                                    src="/build/images/separator.svg"
                                                    alt="separator"
                                                    height="24px"
                                                    width="24px"

                                            />
                                        </div>

                                        <div className="fr-col-12 fr-px-2v fr-modal-audience-description">
                                            {this.state.description}
                                        </div>
                                        <div className="fr-col-12 fr-pt-3w">
                                        </div>

                                        <div className="fr-col-6 fr-p-1w">

                                            <button onClick={this.handleEdit} className="fr-btn fr-btn--icon-left fr-btn--secondary">
                                                <i className="ri-edit-line fr-pr-1w"></i>
                                                {this.state.titleEditButton}
                                            </button>
                                            
                                        </div>
                                        <div className="fr-col-6 fr-p-1w">
                                            <a href={this.state.downloadLink} download="export">
                                                <button onClick={this.handleDownload} className="fr-btn fr-btn--icon-left align-right" style={{}}>
                                                    <i className="ri-file-download-line fr-pr-1w"></i>
                                                    {this.state.titleDownloadButton}
                                                </button>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </dialog>

        );
    }
  }

  export default Modal;