export function updateGlobal(year, month, service) {
  $.when(updateCalendar(year, month, 1, service)).done(function(_) {
      location.reload();
  });
}

window.updateGlobal = updateGlobal;
/**
 * Mise à jour du calendrier en allant récupérer les informations depuis Cassiopée
 *
 */
function updateCalendar(year, month, day,serviceId) {
  year+="";
  month+="";
  day+="";
  let data = {
    'serviceId': serviceId,
    'date_debut': year.padStart(4, '2000')+'-'+month.padStart(2, '0')+'-'+day.padStart(2, '0')
  };
  displayImportationMessage('loading', 'Récupération du calendrier depuis Cassiopée en cours (référence '+day+'/'+month+'/'+year+' sur 15 jours). Veuillez patienter.');
  let url = Routing.generate('_api_/audiences/v1/importation_get_collection', data);
  return $.get(url).done(function (res) {
    return res;
  }).fail(function(err) {
    displayImportationMessage('error');
    return err;
  }
  );
}
