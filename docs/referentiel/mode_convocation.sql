INSERT INTO webapp.mode_convocation(id, code, libelle, mnemo)VALUES(nextval('webapp.mode_convocation_id_seq'), 'CCPV', 'CCPV','') ON CONFLICT DO NOTHING;
INSERT INTO webapp.mode_convocation(id, code, libelle, mnemo)VALUES(nextval('webapp.mode_convocation_id_seq'), 'PVCI', 'PVCI','') ON CONFLICT DO NOTHING;
INSERT INTO webapp.mode_convocation(id, code, libelle, mnemo)VALUES(nextval('webapp.mode_convocation_id_seq'), 'COPJ', 'COPJ','') ON CONFLICT DO NOTHING;
INSERT INTO webapp.mode_convocation(id, code, libelle, mnemo)VALUES(nextval('webapp.mode_convocation_id_seq'), 'ORTC', 'ORTC','') ON CONFLICT DO NOTHING;
INSERT INTO webapp.mode_convocation(id, code, libelle, mnemo)VALUES(nextval('webapp.mode_convocation_id_seq'), 'RENV', 'Renvoi','') ON CONFLICT DO NOTHING;
INSERT INTO webapp.mode_convocation(id, code, libelle, mnemo)VALUES(nextval('webapp.mode_convocation_id_seq'), 'REFC', 'Refus CRPC','') ON CONFLICT DO NOTHING;