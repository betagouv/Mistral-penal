export function updateAudience(id, idKsp, jour, mois, annee) {
  event.preventDefault();
  displayImportationMessage(
    "loading",
    "Synchronisation des audiences depuis Cassiopée en cours. Veuillez patienter.",
  );
  importAudience(id, idKsp, jour, mois, annee);
}

window.displayImportationMessage = function (status, message = "") {
  switch (status) {
    case "loading":
      $("#message-container").show();
      $("#importation-message").html(message);
      $("#icon-info").addClass("info-blue-icon");
      $("#icon-info").removeClass("info-green-icon");
      break;
    case "done":
      $("#message-container").show();
      $("#importation-message").html("Synchronisation terminée  ");
      $("#icon-info").removeClass("info-blue-icon");
      $("#icon-info-message").addClass("info-green-icon");
      break;
    case "none":
      $("#message-container").hide();
      break;
    case "error":
      $("#message-container").show();
      $("#importation-message").html(
        "Un problème est survenu ! Veuillez vous déconnecter de MISTRAL puis retenter après reconnexion",
      );
      if (message != "") {
        $("#importation-message").html(message);
      }
      break;
  }
};

/**
 * Import des informations d'une personne de l'affaire courante
 *
 * @param {string} idKsp Identifiant de l'audience
 * @param {int} numeroParquet Numéro parquet de l'affaire
 * @param {array} idPersonneKsps Tableau des identifiants KSP des personnes
 * @param {array} numeroParquets Tableau des numéros parquets restant à traiter
 */
function recursiveImportAffairePersonne(
  idKsp,
  numeroParquet,
  idPersonneKsps,
  numeroParquets,
) {
  const idPersonneKsp = idPersonneKsps.pop();
  if (idPersonneKsp) {
    let data = {
      id_ksp: idKsp,
      include_info_affaire: 0,
      numero_parquet: numeroParquet,
      include_info_personne: 0,
      id_person_ksp: idPersonneKsp,
    };

    writeMessage(
      "Traitement de la personne '" +
        idPersonneKsp +
        "' de l'affaire " +
        numeroParquet +
        " (" +
        numeroParquets.length +
        " affaires encore à traiter après/" +
        idPersonneKsps.length +
        " personnes à traiter après)",
    );
    let url = Routing.generate(
      "_api_/audiences/v1/importation_get_collection",
      data,
    );
    $.get(url).done(function (res) {
      recursiveImportAffairePersonne(
        idKsp,
        numeroParquet,
        idPersonneKsps,
        numeroParquets,
      );
    });
  } else {
    recursiveImportAffaire(idKsp, numeroParquets);
  }
}

function recursiveImportAffaire(idKsp, numeroParquets) {
  const numeroParquet = numeroParquets.pop();
  if (numeroParquet) {
    let data = {
      id_ksp: idKsp,
      include_info_affaire: 0,
      numero_parquet: numeroParquet,
      include_info_personne: 0,
    };

    writeMessage(
      "Traitement de l'affaire " +
        numeroParquet +
        " (" +
        numeroParquets.length +
        " encore à traiter après)",
    );
    let url = Routing.generate(
      "_api_/audiences/v1/importation_get_collection",
      data,
    );
    let idPersonneKsps = [];
    $.get(url).done(function (res) {
      res.affaires.forEach(function (affaire) {
        if (numeroParquet == affaire.numeroParquet) {
          affaire.affairePersonnes.forEach(function (affairePersonne) {
            const idKsp = affairePersonne.personne.idKsp;
            if (idKsp) idPersonneKsps[idPersonneKsps.length] = idKsp;
          });

          recursiveImportAffairePersonne(
            idKsp,
            numeroParquet,
            idPersonneKsps,
            numeroParquets,
          );
        }
      });
    });
  } else {
    location.reload();
  }
}

function writeMessage(msg) {
  displayImportationMessage("loading", msg);
}
function importAudience(id, idKsp, jour, mois, annee) {
  let data = { id: id, id_ksp: idKsp, include_info_affaire: 0 };
  if (jour) {
    const fullMonth = annee + "-" + mois;
    let url = Routing.generate("app_import_iteratif", {
      id: id,
      day: jour,
      fullmonth: fullMonth,
    });
    window.location.assign(url);
    return;
  } else {
    let url = Routing.generate(
      "_api_/audiences/v1/importation_get_collection",
      data,
    );
    writeMessage("Recherche des affaires associées à l'audience");

    $.get(url)
      .done(function (res) {
        let numeroParquets = [];
        res.affaires.forEach(function (affaire) {
          numeroParquets[numeroParquets.length] = affaire.numeroParquet;
        });
        writeMessage(numeroParquets.length + " affaires trouvées");

        recursiveImportAffaire(idKsp, numeroParquets);
      })
      .fail(function (err) {
        displayImportationMessage("error");
      });
  }
}

window.updateAudience = updateAudience;
