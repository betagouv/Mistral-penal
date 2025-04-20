/*
 * MIT License
 * 
 * Copyright (c) 2025 Startup d'Etat MISTRAL PENAL - incubateur du Ministère de la Justice 
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';
import '../node_modules/@gouvfr/dsfr/dist/utility/icons/icons.css';

require('@gouvfr/dsfr/dist/dsfr/dsfr.module.min.js');

// require('@gouvfr/dsfr/dist/dsfr/dsfr.nomodule.min.js');

const $ = require('jquery');
window.jQuery = $;
window.$ = $;


const routes = require('../public/js/fos_js_routes.json');
import Routing from '../vendor/friendsofsymfony/jsrouting-bundle/Resources/public/js/router.min';
Routing.setRoutingData(routes);
window.Routing = Routing;

require('webpack-jquery-ui');
import './pugx/autocompleter-jqueryui';

$(document).ready(function() {

  /**
   * gestion des autocomplétions
   *
   */
  /**
   * @author yanroussel
   * @description ajout de l'information de chargement lors d'une recherche
   */
  $(document)
    .ajaxStart(function() {
      let $focused = $(":focus");
      if($focused.hasClass("fr-autocomplete"))
        $focused.parent().parent().find(".load-autocomplete").removeClass("hide");
    })
    .ajaxStop(function() {
      let $focused = $(":focus");
      if($focused.hasClass("fr-autocomplete"))
        $focused.parent().parent().find(".load-autocomplete").addClass("hide");
    })
  ;

  /**
   * @author yanroussel
    * @description Option de vidage d'une autocomplétion via un bouton X
    */
  $(".remove-autocomplete").bind("click", function() {
    $(this).parent().find("input").val("");
  });

  /**
   * @author yanroussel
   * @description Autocomplétion dédiée aux communes
   */
  bindCommune(".autocomplete_commune");

  $(window).on('resize', function() {
    mainWindowInCenter();
  });

  mainWindowInCenter();

});

function mainWindowInCenter() {
  $('.menu-left').hide();
  $('.menu-left').css({
    position: 'fixed',
    left: $('#nav-left').width() / 2 - $('.menu-left').width() / 2,
  });
  $('.menu-left').show();
}

window.bindCommune = function(selector) {
  $(selector).autocompleter({
    url_list: Routing.generate('_api_communes/api-srj/v1_get_collection'),
    url_get: Routing.generate('_api_communes/api-srj/v1/{id}_get'),
    url_init: Routing.generate('_api_/communes/{id}{._format}_get'),
    format_callback: function(data) {
      if(data.codePostal)
        return data.libelle+' ('+data.codePostal.substr(-5,2)+')';
      else
        return data.libelle;
    },
    on_select_callback: function($this, event, ui, settings) {
      let url = settings.url_get;
      url = ((url.substring(-1) === '/') ? url : url + '/') + $this.val();
      $.ajax({
          url: url,
          success: function (name) { $this.val(name.id); }
      });
    }
  });
}

window.showSpinner = function () {
  document.getElementById("spinner").classList.add("show");
}
window.hideSpinner = function () {
  document.getElementById("spinner").classList.remove("show");
}

window.closeModale = function (elementId){
  let selector = "#fr-modal-"+elementId;
  if(elementId == "natinf") selector = "#modale-requal-disqual-natinf";
  let element = $(selector)[0]; // Reference à l'element du DOM
  dsfr(element).modal.conceal(); // Méthode pour fermer manuellement la modale
}

/** gestion des écritures pour les entiers */
$(document).on('keypress',".int-field",function(evt) {
  let ASCIICode = (evt.which) ? evt.which : evt.keyCode;
  return (ASCIICode <= 31) || (ASCIICode >= 48 && ASCIICode <= 57);
})
