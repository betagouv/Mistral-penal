import React from 'react';
import {Component} from 'react';

class Audience extends Component {
    constructor( props ) {
        super();

        this.jour = props.jour;
        this.mois = props.mois;
        this.annee= props.annee;
        this.finAudience = props.finAudience;
        this.id = props.id;
        this.idKsp = props.idKsp;
        this.debut = props.debut;
        this.serviceLabel = props.serviceLabel;
        this.quantiteJugee = props.quantiteJugee;
        this.quantitePrevue = props.quantitePrevue;
        this.dateMiseAJour = props.dateMiseAJour;
        this.dateDernierImport = props.dateDernierImport;
        this.url = props.url;
        this.audienceType = props.audienceType;

        this.handleClick = this.handleClick.bind(this);
        this.handleUpdate = this.handleUpdate.bind(this);
      }

    handleClick(e) {
        e.preventDefault();
        e.stopPropagation();

        if(true===this.mustBeImported())
          updateAudience(this.id, this.idKsp,this.jour, this.mois, this.annee);
        else
          updateModal(
            this.id,this.url,this.jour,this.mois,this.annee,this.debut,
            this.getDernierImport(),this.serviceLabel,this.idKsp,this.finAudience
          );
    }

    handleUpdate(e) {
        e.preventDefault();
        e.stopPropagation();
        updateAudience(this.id, this.idKsp,this.jour, this.mois, this.annee);
    }

    getDernierImport(){
        let result="";
        if(this.dateMiseAJour){
            const date = new Date(this.dateMiseAJour);
            const formatted = Intl.DateTimeFormat("fr-FR", {
                year: '2-digit',  month: 'numeric',  day: 'numeric', hour: 'numeric',  minute: 'numeric'
            }).format(date);
            result =  "Édité le " + formatted;
        }

        if(this.dateDernierImport){
            const date = new Date(this.dateDernierImport);
            const formatted = Intl.DateTimeFormat("fr-FR", {
                year: '2-digit',  month: 'numeric',  day: 'numeric', hour: 'numeric',  minute: 'numeric'
            }).format(date);
            result = "Import " + formatted;
        }

        return result;
    }

    displayDernierImport(){
        let result="";
        let classText="cal-audience-date";

        result = this.getDernierImport();

        if(this.finAudience != null){
            classText = "cal-audience-date-green";
            result = "Terminé";
        }

        return (
            <div className={classText}>{result}</div>
        )
    }

    getClassDossier(){
        let classText="cal-audience-date";
        if(this.finAudience != null){
            classText = "cal-audience-infos-green";
        }

        return classText;
    }

    getClassCard(){
        if(this.finAudience){
            return "badge-calendar-terminated";
        }
        if(this.dateDernierImport){
            return "badge-calendar-imported";
        }

        return "badge-calendar";
    }

    getClassButton(){
        if(this.dateDernierImport){
            return "fr-btn--secondary";
        }
    }

    getAudienceType(){
        switch(this.audienceType){
            case "collegial":
                return "Collégial";
            case "juge unique":
                return "Juge unique";
            default:
                return this.audienceType;
        }
    }

    mustBeImported() {
      const strTmp = this.finAudience||this.dateDernierImport;
      return ((null === strTmp)||(strTmp.length===0));
    }

    showImport() {

      return (true===this.mustBeImported()) ? (
        <button className={ "fr-btn "+this.getClassButton()+ " fr-p-2v"} >
          <i onClick={null} className="ri-download-line" title="import de l'audience"></i>
        </button>
      ) : "";
    }

    render() {
        return (
            <>
                <div className={ "fr-grid-row "+ this.getClassCard() } onClick={this.handleClick}>
                    <div className="fr-col-12 fr-p-1w">
                        <span className="cal-audience-title">{this.debut}</span><br/>
                        <span className="cal-audience-title">{this.serviceLabel}</span>
                    </div>

                    <div className="fr-col-8 fr-px-2v">
                        <span className="cal-audience-infos">{this.getAudienceType()}</span><br/>
                        {this.quantitePrevue > 0 && 
                            <span className={this.getClassDossier()} >{this.quantitePrevue} dossier{this.quantitePrevue > 1 && 's'}</span>
                        }
                    </div>
                    <div className="fr-col-4 fr-pt-1w fr-pr-1v">
                        {this.showImport()}
                    </div>

                    <div className="fr-col-12 fr-p-1w">
                        {this.displayDernierImport()}

                    </div>
                </div>
            </>
        );
    }
  }

  export default Audience;
