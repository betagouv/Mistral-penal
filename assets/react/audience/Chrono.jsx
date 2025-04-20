import React from 'react';
import {Component} from 'react';

class Chrono extends Component {
    constructor( props ) {
        super();
        this.startState  = "\u00A0 Démarrer l'audience \u00A0";
        this.pauseState  = "Interrompre l'audience";
        this.resumeState = "\u00A0 Reprendre l'audience \u00A0";

        this.state = {
            buttonSession: this.startState,
            sessionDisabled: true,
            closeDisabled: true,
            endAudience: null,
            startAudience: null,
            inputStartAudience: '',
            inputEndAudience: '',
        }

        this.mistralClient = props.mistralClient;
        this.audienceId = props.audienceId;
        this.session = null;
        this.closed = false;

        this.handleClick = this.handleClick.bind(this);
        this.closeAudience = this.closeAudience.bind(this);
        this.updateTime = this.updateTime.bind(this);
        this.save = this.save.bind(this);

        this.checkSession();
    }

    componentDidMount(){

        const beforeUnloadListener = (event) => {
            event.preventDefault();
            if(this.session == null){
                return;
            }
            const data = {
                'audienceId':this.audienceId,
                'sessionId':this.session.id
            }

            const url = Routing.generate('audience_session_update_item', data);
            this.mistralClient.postItem(url, null);
            return (event.returnValue = "");
        };
        // berk
        // addEventListener("beforeunload", beforeUnloadListener, { capture: true });
    }

    checkSession(){
        const data = { 'audienceId':this.audienceId }
        const url = Routing.generate('session_audience_started', data);
        this.mistralClient.getItem(url).then((response) => {
            if(response == undefined){
                this.setState(
                    {
                        sessionDisabled: false,
                        buttonSession: this.startState
                    }
                );
                return;
            }

            this.session = response.data.session;
            this.state.endAudience = response.data.finAudience;
            this.state.startAudience = response.data.debutAudience;
            if(this.state.endAudience != null){
                this.closed = true;
                this.setState(
                    {
                        sessionDisabled: true,
                        closeDisabled: true,
                        buttonSession: this.resumeState,
                        inputStartAudience: this.formatInput(this.state.startAudience),
                        inputEndAudience: this.formatInput(this.state.endAudience)
                    }
                );

                return;
            }

            if(this.session == null){
                this.setState(
                    {
                        sessionDisabled: false,
                        closeDisabled: false,
                        buttonSession: this.resumeState
                    }
                );

                return;
            }

            if(this.session != null){
                this.setState(
                    {
                        sessionDisabled: false,
                        closeDisabled: false,
                        buttonSession: this.pauseState
                    }
                );
            }
        }).catch((response)=>{
        });
    };

    handleClick(e){
        e.preventDefault();
        e.stopPropagation();

        if(this.session == null){
            this.setState(
                {
                    sessionDisabled: true,
                }
            );
            const data = { 'audienceId':this.audienceId }
            const url = Routing.generate('audience_session_post_item', data);
            this.mistralClient.postItem(url, null).then((response) => {
                this.session = response.data;
                this.setState(
                    {
                        sessionDisabled: false,
                        closeDisabled: false,
                        buttonSession: this.pauseState
                    }
                );
            }).catch((response)=>{
            });
            return;
        }

        if(this.session.finSession == null){
            this.setState(
                {
                    sessionDisabled: true,
                    closeDisabled: true,
                    buttonSession: this.resumeState
                }
            );
            this.closeSession();
        }
    }

    closeSession(){
        const data = {
            'audienceId':this.audienceId,
            'sessionId':this.session.id
        }
        const url = Routing.generate('audience_session_update_item', data);
        this.mistralClient.postItem(url, null).then(() => {
            this.session = null;
            if(this.closed == false){
                this.setState(
                    {
                        sessionDisabled: false,
                        closeDisabled: false,
                    }
                );
            }
        }).catch(() => {
        });
    }

    closeAudience(e){
        e.preventDefault();
        e.stopPropagation();
        this.closed = true;
        this.setState(
            {
                sessionDisabled: true,
                closeDisabled: true,
                buttonSession: this.startState
            }
        );
        const data = {
            'audienceId':this.audienceId,
        }
        const url = Routing.generate('audience_close', data);
        this.mistralClient.getItem(url, null).then((response) => {
            this.setState(
                {
                    startAudience: response.data.debutAudience,
                    endAudience: response.data.finAudience,
                    inputStartAudience: this.formatInput(response.data.debutAudience),
                    inputEndAudience: this.formatInput(response.data.finAudience)
                }
            );
        }).catch((response)=>{

        });

    }

    save(event){
        const dateDebut = new Date();
        dateDebut.setHours(this.state.inputStartAudience.split(':')[0]);
        dateDebut.setMinutes(this.state.inputStartAudience.split(':')[1]);
        dateDebut.setSeconds(0);
        dateDebut.toLocaleString('fr-FR', {
            timeZone: 'Europe/Paris',
        });

        let dateFin = new Date();
        dateFin.setHours(this.state.inputEndAudience.split(':')[0]);
        dateFin.setMinutes(this.state.inputEndAudience.split(':')[1]);
        dateFin.setSeconds(0);
        dateFin.toLocaleString('fr-FR', {
            timeZone: 'Europe/Paris',
        });

        if(dateDebut > dateFin) {
          /**
           * Si la date de fin est inférieure à la date du jour,
           * alors on ajoute J+1 à la date de fin d'audience.
           *
           */
          dateFin.setDate(dateFin.getDate() + 1);
        }

        const data = {
            debutAudience:dateDebut.toUTCString(),
            finAudience:dateFin.toUTCString()
        }
        
        const url = Routing.generate('audience_start_end_save', {audienceId:this.audienceId});
        this.mistralClient.postItem(url, data).then((response) => {
        }).catch((response)=>{
            this.state.endAudience = response.responseJSON.data.finAudience;
            this.state.startAudience = response.responseJSON.data.debutAudience;
            this.setState(
                {
                    inputStartAudience: this.formatInput(this.state.startAudience),
                    inputEndAudience: this.formatInput(this.state.endAudience)
                }
            );
        });
    }

    getBtnIcon(){
        if(this.state.buttonSession == this.startState){
            return "fr-icon-play-circle-line";
        }
        if(this.state.buttonSession == this.pauseState){
            return "fr-icon-pause-circle-line"; 
        }
        if(this.state.buttonSession == this.resumeState){
            return "fr-icon-play-circle-line";
        }
    }

    updateTime(event){
        this.setState(
            {
                [event.target.name] : $('[name="'+event.target.name+'"]').val()
            }
        )
    }

    recap(){
        console.log('recap');
    }

    formatInput(value){
        if(value == ''){
            return "00:00";
        }
        const date = new Date(value);
        date.toLocaleString('fr-FR', {
            timeZone: 'Europe/Paris',
        });

        return this.formatTime(date.getHours()) + ":" + this.formatTime(date.getMinutes());
    }

    formatTime(value){
        if(value<10) {
            return "0"+value;
        }
        return value;
    }

    getContent(){
        if(this.state.endAudience == null){
            return (
                <div className="fr-grid-row">
                    <div className="fr-col-12 fr-pt-2w chrono-container">
                        <button onClick={this.handleClick} className={"fr-btn "+ this.getBtnIcon()+"  fr-btn--icon-left chrono-btn "} title="Label bouton MD" disabled={this.state.sessionDisabled}>
                        {this.state.buttonSession}
                        </button>
                    </div>
                    <div className="fr-col-12 fr-pt-1v chrono-container">
                        <button onClick={this.closeAudience} className="fr-btn fr-btn--secondary fr-icon-stop-circle-fill fr-btn--icon-left fr-px-2w chrono-btn" title="Label bouton MD" disabled={this.state.closeDisabled}>
                        &ensp; Clôturer l'audience &ensp;
                        </button>
                    </div>
                </div>
            );
        }else{
            return (
                <div className="fr-grid-row">
                    <div className="fr-col-12 fr-pt-2w chrono-container">
                        <div className="fr-grid-row">
                            <div className="fr-col-2 fr-pt-1w fr-pr-2w">Début </div>
                            <div className="fr-col-4">
                                <input 
                                    type="time" 
                                    name="inputStartAudience" 
                                    onChange={this.updateTime} 
                                    className="fr-input" 
                                    value={this.state.inputStartAudience} 
                                    onBlur={this.save}
                                /> </div>
                            <div className="fr-col-2 fr-pt-1w fr-pr-2w">Fin </div>
                            <div className="fr-col-4">
                                <input 
                                    onChange={this.updateTime} 
                                    name="inputEndAudience" 
                                    type="time" 
                                    className="fr-input" 
                                    value={this.state.inputEndAudience} 
                                    onBlur={this.save}
                                /> </div>
                        </div>
                    </div>
                    {/* <div className="fr-col-12 fr-pt-1v chrono-container">
                        <button onClick={this.recap} className="fr-btn fr-btn fr-icon-eye-line fr-btn--icon-left" title="Label bouton MD">
                            &ensp; Récapitulatif &ensp;
                        </button>
                    </div> */}
                </div>
            )
        }
    }

    render() {
        return (
            <>
                {this.getContent(this.state.toggle)}
            </>
        );
    }
  }

  export default Chrono;
