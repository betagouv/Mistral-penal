/**
 * @author yanroussel
 *         Section dédiée aux codes spécifiques additionnels
 */
import AffaireNatinf from '../affaire/natinf/affaireNatinf.class.js';

/**
 * @author yanroussel
 *         Gestion du panneau d'affichage de la NATINF
 */
export const updateNatinf = (data, panelId) => {

    const affaireNatinf = new AffaireNatinf(
      "#affaire_natinf_requal_disqual_",
      '#declencheur-requal-disqual-natinf',
      '#natinf_requal_disqual_personnes',
      'natinf_personne_requal_disqual'
    );

    affaireNatinf.panel=panelId;
    affaireNatinf.updatePanel(data);

    const updateNatinfsEvent = new Event('updateNatinfs', {
      bubbles: true,
      cancelable: true,
      composed: false
    })

    document.querySelector("#affaireNatinfs-codes").dispatchEvent(updateNatinfsEvent);
}
