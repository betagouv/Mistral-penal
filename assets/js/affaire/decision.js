import  DecisionCard from './decision-module';
import  MistralClient from '../utils/mistral-client';
import { syncElement } from '../serviceWorker/';

var mistralClient = new MistralClient();
var decisions = [];
//var syncManager = new SyncroManager();

$(document).ready(function() {
    /**
     * @author yanroussel
     * @date   2023-09-25
     *         Réactivation des boutons de mise à jour natinf si la décision
     *         est supprimée
     */
    $(document).on('click','.btn-delete-decision', function() {
      const id = this.id.replace("btn-delete-decision-","");
      /** affichage des éléments dont la natinf est détachée */
      decisions.forEach(function(decisionCard) {
        const decision = decisionCard.decision;
        if(decision.id == id) {
          decision.affaireNatinfs.forEach(function(item) {
            $(".fr-natinf-card[data-affaire-natinf="+item.id+"]").find(".fr-btn").removeClass("hide");
          });
        }
      });
    });
    /** UPDATE natinfs si changement */
    document.querySelector("#affaireNatinfs-codes").addEventListener("updateNatinfs", (event) => {
        let data = { id: $('#decision_affaire').val() };
        let url = Routing.generate('affaire_decision_affaire_natinfs', data);
        mistralClient.getItem(url, data).then( (response) => {
            for (let key in response.affairePersonnes) {
                $('#btn-nouvelle-decision-'+ key).attr('data-natinfs', response.affairePersonnes[key]);
            }
            $('#affaireNatinfs-codes').attr('data-codes', JSON.stringify(response.codes));
        });

        mistralClient.getDecisionForm().then( (response) => {
            $('#natinfs-container-builder').html(response);
            initModal();
        });
    });

    

    $.each( $("button[id^=btn-nouvelle-decision-]"), function () {
        $(this).click(function() {
            $('#decision_affaire').val($(this).attr('data-affaire'));
            $('#decision_affairePersonne').val($(this).attr('data-affairePersonneId'));
            resetModal($(this));
        });
    })

    // Création des modules decision-card
    $.each( $("div[id^=decision-card-]"), function () {
        const decision = $(this).data('decision');
        const decisionModule = new DecisionCard(mistralClient, decision);
        $(this).append(decisionModule.render());
        decisionModule.addListeners();
        decisions.push(decisionModule);
    })


    initModal();

    document.querySelector("#decisions-container").addEventListener("update-decision", (event) => {
        const response = event.detail.response;
        if(event.detail.url.indexOf("decisions/", 1) != -1){
            for(let i=0; i<decisions.length; i++){
                if(decisions[i].decision.id == response.data.id){
                    decisions[i].update(response.data);
                    $("#decision-card-"+decisions[i].decision.id).fadeOut(800, function(){
                        $(this).html(decisions[i].render()).fadeIn(400, function(){
                            $.fn.reOrderIndex(decisions[i].decision.affairePersonne.id);
                            decisions[i].addListeners();
                        }).delay(2000);
                    });
                    break;
                }
            }
        }

        //CREATE//
        if(event.detail.url.indexOf("decisions/", 1) == -1){
            const decisionModule = new DecisionCard(mistralClient, response.data);
            const newCard = document.createElement("div");
            newCard.setAttribute("id", "decision-card-"+response.data.id);
            newCard.setAttribute("class", "fr-col-12");
            newCard.appendChild(decisionModule.render());
            $('#decision-container-'+response.data.affairePersonne.id).append(newCard);
            decisionModule.addListeners();
            decisions.push(decisionModule);
        }

    });
});


function initModal(){
    // Pour sélectionner/déselectionner #checkbox-all-natinfs et enable/disable submit
    $("input[id^=decision_affaireNatinfs_]").each(function(){
        $(this).click(function() {
            $('#checkbox-all-natinfs').prop('checked', false);
            validate();
        });
    })

    /** POST Decision */
    $('#decision_submit').click(function (e) {
        showSpinner();
        e.preventDefault();
        e.stopPropagation();

        postDecision();
    });

    // listener pour fermer le dropdown natinf
    $('#natinfs-btn').click(function(){
        const elt = document.getElementById("myDropdown");
        elt.classList.toggle("show");
        if(elt.classList.contains("show")){
            window.addEventListener('click', clickOutsideDropdown);
        }
    });

    // Pour sélectionner/déselectionner tous les natinfs
    $('#checkbox-all-natinfs').click(function(){
        let checked = $(this).prop('checked');
        $("input[id^=decision_affaireNatinfs_]").each(function() {
            $('#decision_affaireNatinfs_'+this.value).prop('checked', checked);
        });
        validate();
    })

    // listener sur les éléments obligatoires du form decision
    // pour enable le bouton submit
    $("#decision_peines").bind( "keyup", keyUpHandler);
    $("#decision_numeroMinute").bind( "keyup", keyUpHandler);
    $("#decision_decisionPrevention").bind( "change", keyUpHandler);

    
    // bouton submit decision disabled par défaut
    $("#decision_submit").prop("disabled",true);
    
}


async function postDecision(){
    if(!document.querySelector('form[name="decision"]').reportValidity()){
        hideSpinner();
        return;
    };

    let data = $('form[name="decision"]').serialize();
    const affaireId = parseInt($('#affaire_id').val());
    const decisionId = ($('#decision_id').val() != undefined && $('#decision_id').val() != '') ? $('#decision_id').val():null;

    syncElement(affaireId, 'decision', decisionId, data, true, ()=>{});
}

const keyUpHandler = function() {
    validate();
};

function validate() {
    let cansubmit = false;
    $("input[id^=decision_affaireNatinfs_]:checked").each(function() {
        if(this.disabled == false){
            cansubmit = true;
        }
    });

    // if($( "#decision_peines" ).val() == ''){
    //     cansubmit = false;
    // }

    if($("#decision_decisionPrevention option:selected").val() == ''){
        cansubmit = false;
    }

    const $numeroMinute = document.querySelector('#decision_numeroMinute')

    if ($numeroMinute) {
        if ($numeroMinute.hasAttribute('pattern')) {
            const pattern = $numeroMinute.getAttribute('pattern')
            const regex = new RegExp(pattern)

            if ($numeroMinute.value !== '' && !regex.test($numeroMinute.value)) {
                cansubmit = false
            }
        } else {
            console.error('#decision_numeroMinute n\'a pas d\'attribut "pattern"')
        }
    } else {
        console.error('#decision_numeroMinute n\'existe pas')
    }


    $("#decision_submit").prop("disabled", !cansubmit);
}

$.fn.reOrderIndex = function(id){
    let index = 1;
    $("#decision-container-"+id).find('div[id^=decision-card-]').each(function(){
        $(this).find('.decision-number').text(index);
        index++;
    });
};

function clickOutsideDropdown(event) {
     if (!event.target.matches('.dropdown')) {
        document.getElementById("myDropdown").classList.remove('show');
        window.removeEventListener('click', clickOutsideDropdown);
        updateNatinfsSelected();
    }
}

function updateNatinfsSelected(){

    const codes = $('#affaireNatinfs-codes').data('codes')

    var selected = [];
    $("input[id^=decision_affaireNatinfs_]:checked").each(function() {
        if(this.disabled == false){
            selected.push($(this).val());
        }
    });
    let label = '';

    for(let i=0; i<selected.length; i++){
        label += codes['k'+selected[i]];
        if(i<selected.length-1){
            label += ' - ';
        }
    }

    if(label == ''){
        label = 'Sélectionnez les NATINFS concernées';
    }


    $('#natinfs-btn').text(label);
}


function resetModal(element){
    $('#decision_id').removeAttr("value");
    $('#decision_decisionPrevention').prop('selectedIndex',0);
    $('#decision_decisionSanction').prop('selectedIndex',0);
    $('#decision_modulationPeine').prop('selectedIndex',0);
    $("#decision_peines").attr('placeholder', 'Détaillez ici les peines principales et complémentaires à afficher dans la note d\'audience et le rôle');
    $("#decision_peines").val('');
    $("#decision_numeroMinute").val('');

    let peraffaireNatinfsStr = element?element[0].dataset.natinfs:null;
    const affaireNatinfs = peraffaireNatinfsStr.split(',');
    if( affaireNatinfs.length > 1) affaireNatinfs.pop();

    $("input[id^=decision_affaireNatinfs_]").each(function() {
        $('#decision_affaireNatinfs_'+this.value).prop('checked', false);
        $('#decision_affaireNatinfs_'+this.value).prop('disabled', false);
        $('#fieldset_decision_affaireNatinfs_'+this.value).show();
        if(affaireNatinfs.includes(''+this.value) != true ){
            $('#fieldset_decision_affaireNatinfs_'+this.value).hide();
            $('#decision_affaireNatinfs_'+this.value).prop('disabled', true);
        }
    });
    $('#checkbox-all-natinfs').prop('checked', false);
    $('#natinfs-btn').text('Sélectionnez les NATINFS concernées');
    $("#decision_submit").prop("disabled", true);
}
