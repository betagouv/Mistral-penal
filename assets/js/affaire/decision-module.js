const template = document.createElement('template');
template.innerHTML = `
    <div class="fr-col-12 decision-card fr-mb-1w">
        <div class="fr-grid-row">
            <div class="fr-col-1">
                <div id="decision-number" class="decision-number"></div>
            </div>
            <div class="fr-col-11">
                <b><span class="decision-prevention"></span> - </b>
                <b><span class="decision-sanction"></span></b>
            </div>

            <div class="fr-col-1"></div>
            <div class="fr-col-8">
                <span class="decision-modulation"></span><br/>
                <span class="decision-peines"></span><br/>
                <div class="decision-natinfs fr-mt-1w"></div>
            </div>
            <div class="fr-col-3 fr-pl-8w">
                <button
                    class="btn-delete-decision"
                    style="width:50px; height:40px; border: 1px solid #B34000;color:#B34000">
                    <i class="ri-delete-bin-line" style="font-size: 1.5em !important; line-height: 0.6666em; vertical-align: -.075em;"></i>
                </button>

                <button 
                    class="fr-btn btn-edit-decision" 
                    data-fr-opened="false" aria-controls="fr-modal-decision">
                    <i class="ri-edit-line"></i>
                </button>
            </div>
            
        </div>
    </div>
`;

class DecisionCard {
    decision = null;
    client = null;
    context = null;

    constructor(mistralClient, decision){
        this.client = mistralClient;
        this.decision = decision;

        let index = $('#decision-container-'+this.decision.affairePersonne.id).children().length + 1;
        if($('#decision-card-'+this.decision.id).data('index') != undefined){
            index = $('#decision-card-'+this.decision.id).data('index');
        }
        template.content.querySelector('.decision-sanction').innerText = "";
        template.content.querySelector('.decision-modulation').innerText = "";
        template.content.querySelector('#decision-number').innerText = index;
        template.content.querySelector('.btn-delete-decision').setAttribute('id', 'btn-delete-decision-'+this.decision.id);
        template.content.querySelector('.btn-edit-decision').setAttribute('id', 'btn-edit-decision-'+this.decision.id);
        this.update(this.decision);
    } 

    setDecision(data){
        this.decision = data;
    }

    getDecision(){
        return this.decision;
    }

    update(data){
        this.decision = data;
        template.content.querySelector('.decision-prevention').innerText = data.decisionPrevention.libelle;
        if(data.decisionSanction){
            template.content.querySelector('.decision-sanction').innerText = data.decisionSanction.libelle;
        }
        if(data.modulationPeine){
            template.content.querySelector('.decision-modulation').innerText = data.modulationPeine.libelle;
        }
        template.content.querySelector('.decision-peines').innerText = data.peines;
        template.content.querySelector('.btn-delete-decision').setAttribute('id', 'btn-delete-decision-'+this.decision.id);
        template.content.querySelector('.btn-edit-decision').setAttribute('id', 'btn-edit-decision-'+this.decision.id);
        
        let natinfs = '';
        for(let i = 0 ; i < data.affaireNatinfs.length; i++){
            natinfs += '<span class="decision-natinf">'+data.affaireNatinfs[i].natinf.code+'</span> ';
        }
        template.content.querySelector('.decision-natinfs').innerHTML = natinfs;
    }

    refreshModal(){
        $('#decision_id').val(this.decision.id);
        $('#decision_affairePersonne').val(this.decision.affairePersonne.id);
        $("#decision_decisionPrevention").val(this.decision.decisionPrevention.id);
        if(this.decision.decisionSanction){
            $("#decision_decisionSanction").val(this.decision.decisionSanction.id);
        }
        if(this.decision.modulationPeine){
            $("#decision_modulationPeine").val(this.decision.modulationPeine.id);
        }
        $("#decision_peines").val(this.decision.peines);
        $("#decision_numeroMinute").val(this.decision.numeroMinute);

        const natinfsStr = $('#btn-nouvelle-decision-'+this.decision.affairePersonne.id).data('natinfs');
        const natinfs = natinfsStr.split(',');
        if( natinfs.length > 1) natinfs.pop();

        const affaireNatinfs = this.decision.affaireNatinfs;
        let label = '';
        $("input[id^=decision_affaireNatinfs_]").each(function() {
            $('#fieldset_decision_affaireNatinfs_'+this.value).show();
            $('#decision_affaireNatinfs_'+this.value).prop('checked', false);
            $('#decision_affaireNatinfs_'+this.value).prop('disabled', false);

            // verification si on affaiche tous les affaireNatinfs pour cette decision/personne
            if(natinfs.includes(this.value) != true ){
                $('#fieldset_decision_affaireNatinfs_'+this.value).hide();
                $('#decision_affaireNatinfs_'+this.value).prop('disabled', true);
            }

            
            for(let i=0; i<affaireNatinfs.length; i++){
                if(affaireNatinfs[i].id == this.value){
                    $('#decision_affaireNatinfs_'+this.value).prop('checked', true);
                }
            }
        });

        for(let i=0; i<affaireNatinfs.length; i++){
            label += affaireNatinfs[i].natinf.code;
            if(i<affaireNatinfs.length-1){
                label += ' - ';
            }
        }

        if(label == ''){
            label = 'Sélectionnez les NATINFS concernées';
        }

        $('#natinfs-btn').text(label);
    }

    delete(){
        showSpinner();
        return new Promise((successCallback, failureCallback) => {
            this.client.deleteDecision(this.decision.id).then(()=>{
                hideSpinner();
                $("#decision-card-"+this.decision.id).remove();
                $.fn.reOrderIndex(this.decision.affairePersonne.id);
            });
        });
    }

    addListeners(){ 
        $('#btn-delete-decision-'+this.decision.id).on('click', () => {
            this.delete();
        });

        $('#btn-edit-decision-'+this.decision.id).on('click', () => {
            this.refreshModal();
        });
    }

    render (){
        return  template.content.cloneNode(true);
    }
}

export default DecisionCard;