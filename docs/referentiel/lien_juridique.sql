INSERT INTO webapp.lien_juridique(id, code, libelle, mnemo)VALUES(nextval('webapp.lien_juridique_id_seq'), '7', 'pas de lien juridique','') ON CONFLICT DO NOTHING;
INSERT INTO webapp.lien_juridique(id, code, libelle, mnemo)VALUES(nextval('webapp.lien_juridique_id_seq'), '1', 'Représentant légal','') ON CONFLICT DO NOTHING;
INSERT INTO webapp.lien_juridique(id, code, libelle, mnemo)VALUES(nextval('webapp.lien_juridique_id_seq'), '3', 'Tiers','') ON CONFLICT DO NOTHING;
INSERT INTO webapp.lien_juridique(id, code, libelle, mnemo)VALUES(nextval('webapp.lien_juridique_id_seq'), '2', 'Intervenant','') ON CONFLICT DO NOTHING;
INSERT INTO webapp.lien_juridique(id, code, libelle, mnemo)VALUES(nextval('webapp.lien_juridique_id_seq'), '6', 'Administrateur Ad-hoc','') ON CONFLICT DO NOTHING;
INSERT INTO webapp.lien_juridique(id, code, libelle, mnemo)VALUES(nextval('webapp.lien_juridique_id_seq'), '4', 'Déclarant','') ON CONFLICT DO NOTHING;
INSERT INTO webapp.lien_juridique(id, code, libelle, mnemo)VALUES(nextval('webapp.lien_juridique_id_seq'), '9', 'Mandataire judiciaire','') ON CONFLICT DO NOTHING;