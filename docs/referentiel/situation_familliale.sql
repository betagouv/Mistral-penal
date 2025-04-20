INSERT INTO webapp.situation_familliale(id, code, libelle, mnemo)VALUES(nextval('webapp.situation_familliale_id_seq'), 'CEL', 'Célibataire','') ON CONFLICT DO NOTHING;
INSERT INTO webapp.situation_familliale(id, code, libelle, mnemo)VALUES(nextval('webapp.situation_familliale_id_seq'), 'MAR', 'Marié.e','') ON CONFLICT DO NOTHING;
INSERT INTO webapp.situation_familliale(id, code, libelle, mnemo)VALUES(nextval('webapp.situation_familliale_id_seq'), 'PAC', 'Pacsé.e','') ON CONFLICT DO NOTHING;
INSERT INTO webapp.situation_familliale(id, code, libelle, mnemo)VALUES(nextval('webapp.situation_familliale_id_seq'), 'DIV', 'Divorcé.e','') ON CONFLICT DO NOTHING;
INSERT INTO webapp.situation_familliale(id, code, libelle, mnemo)VALUES(nextval('webapp.situation_familliale_id_seq'), 'VEU', 'Veuf.ve','') ON CONFLICT DO NOTHING;
INSERT INTO webapp.situation_familliale(id, code, libelle, mnemo)VALUES(nextval('webapp.situation_familliale_id_seq'), 'AUT', 'Autre','') ON CONFLICT DO NOTHING;