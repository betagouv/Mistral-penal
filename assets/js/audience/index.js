require('../../styles/audience/modal.css');
import Calendar from "../../react/Calendar";
import Modal from "../../react/Modal"
import { createRoot } from 'react-dom/client';
import React from "react";

$(document).ready(function() {

    $('.current-month,.current-audience').change(function() {
        document.forms['calendar_filter'].submit();
    });

    const date = $('#calendar_filter_month :selected').val();
    const service = $("#calendar_filter_service").val();
    const entryArray = date.split("-");

    $('#calendar-import-btn').on('click', function(){
        displayImportationMessage('loading');
        updateGlobal(entryArray[0], entryArray[1], service);
    }.bind(this));

    const container = document.getElementById('calendar-container');
    const root = createRoot(container);
    root.render(
        <Calendar audiences={JSON.parse(audiences)} month={entryArray[1]} year={entryArray[0]} />
    );

    const containerModal = document.getElementById('calendar-modal');
    const rootModal = createRoot(containerModal);
    rootModal.render(
        <Modal
            downloadLink={''}
            id={0}
            url={''}
            jour={1}
            mois={1}
            annee={2024}
            debut={'00:00'}
            miseAJour={'2024-01-01'}
            serviceLabel={'Chambre'}
            idKsp={0}
            finAudience={null}
        />
    );

    window.updateModal = function (id, url, jour, mois, annee, debut, miseAJour, serviceLabel, idKsp, finAudience){
        const downloadLink = Routing.generate('app_audiences_documents_export', {'id': id});

        rootModal.render(
            <Modal
                downloadLink={downloadLink}
                id={id}
                url={url}
                jour={jour}
                mois={mois}
                annee={annee}
                debut={debut}
                miseAJour={miseAJour}
                serviceLabel={serviceLabel}
                idKsp={idKsp}
                finAudience={finAudience}
            />
        );

        let selector = "#fr-modal-calendar-audience";
        let element = $(selector)[0]; // Reference à l'element du DOM
        dsfr(element).modal.disclose(); // Méthode pour fermer manuellement la modale
    }
});
