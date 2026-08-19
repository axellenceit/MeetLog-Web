-- Demo-Daten für meetlog
-- Passwort für alle Demo-User: demo1234
-- Hinweis: Aktuell auf 1 Organisation reduziert. Sobald die App Mehrfach-Organisationen
-- unterstützt, können hier weitere Organisationen samt zugehörigen Daten ergänzt werden.

SET FOREIGN_KEY_CHECKS = 0;

INSERT INTO `tblOrganisation` (`id`, `Logo`, `Name1`, `Name2`, `Homepage`, `EMail`, `Telefon`, `Telefax`, `Straße`, `PLZ`, `Ort`, `Land`, `Registergericht`, `Registereintrag`, `istAktiviert`) VALUES
(1, '', 'Musterverein Feuerwehr e.V.', NULL, 'https://www.musterverein-feuerwehr.de', 'info@musterverein-feuerwehr.de', '+49 30 1234567', NULL, 'Feuerwehrstraße 1', '10115', 'Berlin', 'DE', 'Amtsgericht Berlin', 'VR 12345', 1);

INSERT INTO `tblPerson` (`id`, `Vorname`, `Nachname`, `Mail`, `Telefon`, `Strasse`, `PLZ`, `Ort`, `Land`, `istAktiv`, `istGeloescht`) VALUES
(1, 'Anna', 'Admin', 'anna.admin@example.com', '+49 170 1111111', 'Hauptstraße 1', '10115', 'Berlin', 1, 1, 0),
(2, 'Bernd', 'Berger', 'bernd.berger@example.com', '+49 170 2222222', 'Nebenstraße 2', '10115', 'Berlin', 1, 1, 0),
(3, 'Clara', 'Conrad', 'clara.conrad@example.com', '+49 170 3333333', 'Ringstraße 3', '10115', 'Berlin', 1, 1, 0),
(4, 'Daniel', 'Dorn', 'daniel.dorn@example.com', '+49 170 4444444', 'Gartenweg 4', '10115', 'Berlin', 1, 1, 0),
(5, 'Eva', 'Engel', 'eva.engel@example.com', '+49 170 5555555', 'Waldweg 5', '10115', 'Berlin', 1, 1, 0);

INSERT INTO `tblUser` (`id`, `Person`, `Pass`, `StandardOrganisation`, `Foto`, `lang`, `istAktiv`, `istAdmin`, `istGeloescht`) VALUES
(1, 1, '$2y$12$2C5yvnwlSelwSzU2Y.l8Y.fdQ3W9I5gNo2.cfolRzpOCuWvlWJsiy', 1, NULL, 'de', 1, 1, 0),
(2, 2, '$2y$12$2C5yvnwlSelwSzU2Y.l8Y.fdQ3W9I5gNo2.cfolRzpOCuWvlWJsiy', 1, NULL, 'de', 1, 0, 0),
(3, 3, '$2y$12$2C5yvnwlSelwSzU2Y.l8Y.fdQ3W9I5gNo2.cfolRzpOCuWvlWJsiy', 1, NULL, 'de', 1, 0, 0),
(4, 4, '$2y$12$2C5yvnwlSelwSzU2Y.l8Y.fdQ3W9I5gNo2.cfolRzpOCuWvlWJsiy', 1, NULL, 'de', 1, 0, 0),
(5, 5, '$2y$12$2C5yvnwlSelwSzU2Y.l8Y.fdQ3W9I5gNo2.cfolRzpOCuWvlWJsiy', 1, NULL, 'de', 1, 0, 0);

INSERT INTO `tblOrganisationsFunktionen` (`id`, `Organisation`, `Bezeichnung`, `Beschreibung`, `Sortierung`, `istAktiv`, `DarfUnterschreiben`, `DarfOrganisationVerwalten`, `DarfBesprechungenVerwalten`) VALUES
(1, 1, 'Vorsitzende/r', 'Vorsitzende(r) des Vereins', 1, 1, 1, 1, 1),
(2, 1, 'Schriftführer/in', 'Führt das Protokoll', 2, 1, 0, 0, 1),
(3, 1, 'Mitglied', 'Einfaches Vereinsmitglied', 3, 1, 0, 0, 0);

INSERT INTO `tblOrganisationsMitglieder` (`id`, `Organisation`, `Person`, `User`, `Funktion`, `Bemerkung`, `istAktiv`, `istGeloescht`) VALUES
(1, 1, 1, 1, 1, NULL, 1, 0),
(2, 1, 2, 2, 2, NULL, 1, 0),
(3, 1, 3, 3, 3, NULL, 1, 0),
(4, 1, 4, 4, 3, NULL, 1, 0),
(5, 1, 5, 5, 3, NULL, 1, 0);

INSERT INTO `tblBesprechung` (`id`, `OrganisationID`, `ErstellerUserID`, `Titel`, `Datum`, `Beginn`, `Ende`, `Ort`, `Status`, `istAktiviert`, `istVeröffentlicht`, `public_code`) VALUES
(1, 1, 1, 'Jahreshauptversammlung 2026', '2026-03-15', '18:00:00', '20:00:00', 'Vereinsheim, Feuerwehrstraße 1', 'geplant', 1, 1, 'demo-jhv-2026'),
(2, 1, 1, 'Vorstandssitzung März', '2026-01-10', '19:00:00', '20:30:00', 'Vereinsheim, Feuerwehrstraße 1', 'beendet', 1, 0, NULL),
(3, 1, 3, 'Planung Sommerfest', '2026-02-05', '17:00:00', '18:30:00', 'Vereinsheim, Feuerwehrstraße 1', 'geplant', 1, 0, NULL);

INSERT INTO `tblBesprechungspunkt` (`id`, `BesprechungID`, `Position`, `ElternPosition`, `Titel`, `Beschreibung`, `Typ`, `Ergebnis`, `Ergebnis_Zustimmung`, `Ergebnis_Enthaltung`, `Ergebnis_Ablehnung`, `istAktiviert`) VALUES
(1, 1, 1, NULL, 'Begrüßung und Feststellung der Beschlussfähigkeit', 'Begrüßung der Mitglieder durch die/den Vorsitzende(n).', 'information', NULL, NULL, NULL, NULL, 1),
(2, 1, 2, NULL, 'Bericht des Vorstands', 'Rückblick auf das vergangene Geschäftsjahr.', 'information', NULL, NULL, NULL, NULL, 1),
(3, 1, 3, NULL, 'Entlastung des Vorstands', 'Abstimmung über die Entlastung des Vorstands.', 'abstimmung', NULL, NULL, NULL, NULL, 1),
(4, 2, 1, NULL, 'Planung des Sommerfests', 'Diskussion über Termin und Budget.', 'diskussion', NULL, NULL, NULL, NULL, 1),
(5, 2, 2, NULL, 'Neuanschaffung Ausrüstung', 'Abstimmung über die Beschaffung neuer Ausrüstung.', 'abstimmung', 'Antrag angenommen', 4, 1, 0, 1),
(6, 3, 1, NULL, 'Termin und Location', 'Festlegung von Termin und Location für das Sommerfest.', 'diskussion', NULL, NULL, NULL, NULL, 1);

INSERT INTO `tblBesprechungsteilnehmer` (`id`, `Besprechung`, `Person`, `OrganisationsMitglied`, `User`, `Funktion`, `istEingeladen`, `istAnwesend`, `istEntschuldigt`, `istAbwesend`, `istAktiv`, `darfUnterschreiben`, `istSitzungsleitung`, `istProtokollfuehrer`) VALUES
(1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 1, 1, 1, 0),
(2, 1, 2, 2, 2, 2, 1, 0, 0, 0, 1, 0, 0, 1),
(3, 1, 5, 5, 5, 3, 1, 0, 0, 0, 1, 0, 0, 0),
(4, 2, 1, 1, 1, 1, 1, 1, 0, 0, 1, 1, 1, 0),
(5, 2, 2, 2, 2, 2, 1, 1, 0, 0, 1, 0, 0, 1),
(6, 2, 5, 5, 5, 3, 1, 0, 1, 0, 1, 0, 0, 0),
(7, 3, 3, 3, 3, 3, 1, 0, 0, 0, 1, 1, 1, 0),
(8, 3, 4, 4, 4, 3, 1, 0, 0, 0, 1, 0, 0, 0);

INSERT INTO `tblTask` (`id`, `BesprechungID`, `ErstellerUserID`, `VerantwortlicherUserID`, `Titel`, `Beschreibung`, `Deadline`, `istErledigt`) VALUES
(1, 2, 1, 2, 'Angebote für neue Ausrüstung einholen', 'Mindestens drei Vergleichsangebote einholen.', '2026-02-28', 0),
(2, 2, 1, 5, 'Protokoll versenden', 'Protokoll der Vorstandssitzung an alle Mitglieder versenden.', '2026-01-20', 1),
(3, 1, 1, 1, 'Einladung zur JHV verschicken', 'Einladungen inkl. Tagesordnung an alle Mitglieder verschicken.', '2026-02-15', 0),
(4, 3, 3, 4, 'Location reservieren', 'Location für das Sommerfest reservieren.', '2026-02-20', 0);

SET FOREIGN_KEY_CHECKS = 1;
