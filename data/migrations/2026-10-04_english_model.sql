-- ============================================================================
-- MeetLog – Migration: Vereinheitlichung des Datenmodells auf Englisch
-- ============================================================================
-- Migriert eine bestehende Datenbank vom deutschen auf das englische Schema.
-- Vorher unbedingt ein Backup erstellen!
--
--   Tabellen:
--     tblOrganisation            -> tblOrganization
--     tblBesprechung             -> tblMeeting
--     tblBesprechungspunkt       -> tblMeetingPoint
--     tblBesprechungsteilnehmer  -> tblMeetingParticipant
--     tblBesprechungsanhang      -> tblMeetingAttachment
--     tblOrganisationsFunktionen -> tblOrganizationFunction
--     tblOrganisationsMitglieder -> tblOrganizationMember
--     tblCountries               -> tblCountry
--     tblLanguages               -> tblLanguage
--
--   Enum-Werte:
--     tblMeeting.Status:      geplant->planned, beendet->finished, storniert->cancelled
--     tblMeetingPoint.Type:   diskussion->discussion, abstimmung->voting
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- 1. Tabellen umbenennen
-- ---------------------------------------------------------------------------
RENAME TABLE
  `tblOrganisation` TO `tblOrganization`,
  `tblBesprechung` TO `tblMeeting`,
  `tblBesprechungspunkt` TO `tblMeetingPoint`,
  `tblBesprechungsteilnehmer` TO `tblMeetingParticipant`,
  `tblBesprechungsanhang` TO `tblMeetingAttachment`,
  `tblOrganisationsFunktionen` TO `tblOrganizationFunction`,
  `tblOrganisationsMitglieder` TO `tblOrganizationMember`,
  `tblCountries` TO `tblCountry`,
  `tblLanguages` TO `tblLanguage`;

-- ---------------------------------------------------------------------------
-- 2. Alte Fremdschlüssel-Constraints entfernen (werden in Schritt 6 mit
--    englischen Namen neu angelegt)
-- ---------------------------------------------------------------------------
ALTER TABLE `tblUser`
  DROP FOREIGN KEY `fk_Organisation`,
  DROP FOREIGN KEY `fk_tblUser_Person`;

ALTER TABLE `tblMeeting`
  DROP FOREIGN KEY `fk_tblBesprechung_Organisation`,
  DROP FOREIGN KEY `fk_tblBesprechung_User`;

ALTER TABLE `tblMeetingPoint`
  DROP FOREIGN KEY `fk_tblBesprechungspunkt_Besprechung`;

ALTER TABLE `tblOrganizationFunction`
  DROP FOREIGN KEY `fk_OrgFunktion_Organisation`;

ALTER TABLE `tblOrganizationMember`
  DROP FOREIGN KEY `fk_OrgMitglied_Funktion`,
  DROP FOREIGN KEY `fk_OrgMitglied_Organisation`,
  DROP FOREIGN KEY `fk_OrgMitglied_User`,
  DROP FOREIGN KEY `fk_tblOrganisationsMitglieder_Person`;

ALTER TABLE `tblMeetingParticipant`
  DROP FOREIGN KEY `fk_tblBesprechungsteilnehmer_Besprechung`,
  DROP FOREIGN KEY `fk_tblBesprechungsteilnehmer_Funktion`,
  DROP FOREIGN KEY `fk_tblBesprechungsteilnehmer_OrganisationsMitglied`,
  DROP FOREIGN KEY `fk_tblBesprechungsteilnehmer_Person`,
  DROP FOREIGN KEY `fk_tblBesprechungsteilnehmer_User`;

ALTER TABLE `tblMeetingAttachment`
  DROP FOREIGN KEY `fk_tblBesprechungsanhang_Besprechung`,
  DROP FOREIGN KEY `fk_tblBesprechungsanhang_User`;

ALTER TABLE `tblTask`
  DROP FOREIGN KEY `fk_tblTask_Besprechung`,
  DROP FOREIGN KEY `fk_tblTask_Ersteller`,
  DROP FOREIGN KEY `fk_tblTask_Verantwortlicher`;

-- ---------------------------------------------------------------------------
-- 3. Spalten umbenennen
-- ---------------------------------------------------------------------------
ALTER TABLE `tblOrganization`
  CHANGE COLUMN `Telefon` `Phone` varchar(255) DEFAULT NULL,
  CHANGE COLUMN `Telefax` `Fax` varchar(255) DEFAULT NULL,
  CHANGE COLUMN `Straße` `Street` varchar(255) DEFAULT NULL,
  CHANGE COLUMN `PLZ` `ZipCode` varchar(50) DEFAULT NULL,
  CHANGE COLUMN `Ort` `City` varchar(255) DEFAULT NULL,
  CHANGE COLUMN `Land` `Country` varchar(50) DEFAULT NULL,
  CHANGE COLUMN `Registergericht` `RegistryCourt` varchar(50) DEFAULT NULL,
  CHANGE COLUMN `Registereintrag` `RegistryEntry` varchar(50) DEFAULT NULL,
  CHANGE COLUMN `istAktiviert` `IsActive` tinyint(1) NOT NULL DEFAULT 1;

ALTER TABLE `tblPerson`
  CHANGE COLUMN `Vorname` `FirstName` varchar(100) DEFAULT NULL,
  CHANGE COLUMN `Nachname` `LastName` varchar(100) DEFAULT NULL,
  CHANGE COLUMN `Telefon` `Phone` varchar(255) DEFAULT NULL,
  CHANGE COLUMN `Strasse` `Street` varchar(255) DEFAULT NULL,
  CHANGE COLUMN `PLZ` `ZipCode` varchar(255) DEFAULT NULL,
  CHANGE COLUMN `Ort` `City` varchar(255) DEFAULT NULL,
  CHANGE COLUMN `Land` `Country` int(11) DEFAULT NULL,
  CHANGE COLUMN `istAktiv` `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  CHANGE COLUMN `istGeloescht` `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  CHANGE COLUMN `ErstelltAm` `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  CHANGE COLUMN `GeaendertAm` `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp();

ALTER TABLE `tblCountry`
  CHANGE COLUMN `istAktiviert` `isActive` tinyint(1) NOT NULL DEFAULT 1;

ALTER TABLE `tblUser`
  CHANGE COLUMN `Pass` `Password` varchar(255) NOT NULL,
  CHANGE COLUMN `StandardOrganisation` `DefaultOrganization` int(11) NOT NULL,
  CHANGE COLUMN `Foto` `Photo` varchar(255) DEFAULT NULL,
  CHANGE COLUMN `lang` `Lang` varchar(3) NOT NULL DEFAULT 'de',
  CHANGE COLUMN `istAktiv` `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  CHANGE COLUMN `istAdmin` `IsAdmin` tinyint(1) DEFAULT 0,
  CHANGE COLUMN `istGeloescht` `IsDeleted` tinyint(1) NOT NULL DEFAULT 0;

ALTER TABLE `tblMeeting`
  CHANGE COLUMN `OrganisationID` `OrganizationID` int(11) NOT NULL,
  CHANGE COLUMN `ErstellerUserID` `CreatorUserID` int(11) DEFAULT NULL,
  CHANGE COLUMN `Titel` `Title` varchar(255) NOT NULL,
  CHANGE COLUMN `Datum` `Date` date DEFAULT NULL,
  CHANGE COLUMN `Beginn` `StartTime` time DEFAULT NULL,
  CHANGE COLUMN `Ende` `EndTime` time DEFAULT NULL,
  CHANGE COLUMN `Ort` `Location` varchar(255) DEFAULT NULL,
  CHANGE COLUMN `istAktiviert` `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  CHANGE COLUMN `istVeröffentlicht` `IsPublished` tinyint(1) NOT NULL DEFAULT 0,
  CHANGE COLUMN `erstelltAm` `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  CHANGE COLUMN `geaendertAm` `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  CHANGE COLUMN `public_code` `PublicCode` varchar(32) DEFAULT NULL;

ALTER TABLE `tblMeetingPoint`
  CHANGE COLUMN `BesprechungID` `MeetingID` int(11) NOT NULL,
  CHANGE COLUMN `ElternPosition` `ParentPosition` int(11) DEFAULT NULL,
  CHANGE COLUMN `Titel` `Title` varchar(255) NOT NULL,
  CHANGE COLUMN `Beschreibung` `Description` text DEFAULT NULL,
  CHANGE COLUMN `Typ` `Type` varchar(20) NOT NULL DEFAULT 'information',
  CHANGE COLUMN `Ergebnis` `Result` text DEFAULT NULL,
  CHANGE COLUMN `Ergebnis_Zustimmung` `ResultApproval` int(11) DEFAULT NULL,
  CHANGE COLUMN `Ergebnis_Enthaltung` `ResultAbstention` int(11) DEFAULT NULL,
  CHANGE COLUMN `Ergebnis_Ablehnung` `ResultRejection` int(11) DEFAULT NULL,
  CHANGE COLUMN `istAktiviert` `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  CHANGE COLUMN `erstelltAm` `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  CHANGE COLUMN `geaendertAm` `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp();

ALTER TABLE `tblOrganizationFunction`
  CHANGE COLUMN `Organisation` `Organization` int(11) NOT NULL,
  CHANGE COLUMN `Bezeichnung` `Name` varchar(100) NOT NULL,
  CHANGE COLUMN `Beschreibung` `Description` varchar(255) DEFAULT NULL,
  CHANGE COLUMN `Sortierung` `SortOrder` int(11) DEFAULT 0,
  CHANGE COLUMN `istAktiv` `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  CHANGE COLUMN `DarfUnterschreiben` `MaySign` tinyint(1) NOT NULL DEFAULT 0,
  CHANGE COLUMN `DarfOrganisationVerwalten` `MayManageOrganization` tinyint(1) NOT NULL DEFAULT 0,
  CHANGE COLUMN `DarfBesprechungenVerwalten` `MayManageMeetings` tinyint(1) NOT NULL DEFAULT 0;

ALTER TABLE `tblOrganizationMember`
  CHANGE COLUMN `Organisation` `Organization` int(11) NOT NULL,
  CHANGE COLUMN `Funktion` `Function` int(11) DEFAULT NULL,
  CHANGE COLUMN `Bemerkung` `Note` varchar(255) DEFAULT NULL,
  CHANGE COLUMN `istAktiv` `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  CHANGE COLUMN `istGeloescht` `IsDeleted` tinyint(1) NOT NULL DEFAULT 0;

ALTER TABLE `tblMeetingParticipant`
  CHANGE COLUMN `Besprechung` `Meeting` int(11) NOT NULL,
  CHANGE COLUMN `OrganisationsMitglied` `OrganizationMember` int(11) DEFAULT NULL,
  CHANGE COLUMN `Funktion` `Function` int(11) DEFAULT NULL,
  CHANGE COLUMN `istEingeladen` `IsInvited` tinyint(1) NOT NULL DEFAULT 1,
  CHANGE COLUMN `istAnwesend` `IsPresent` tinyint(1) NOT NULL DEFAULT 0,
  CHANGE COLUMN `istEntschuldigt` `IsExcused` tinyint(1) NOT NULL DEFAULT 0,
  CHANGE COLUMN `istAbwesend` `IsAbsent` tinyint(1) NOT NULL DEFAULT 0,
  CHANGE COLUMN `istAktiv` `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  CHANGE COLUMN `darfUnterschreiben` `MaySign` tinyint(1) NOT NULL DEFAULT 0,
  CHANGE COLUMN `istSitzungsleitung` `IsChair` tinyint(1) NOT NULL DEFAULT 0,
  CHANGE COLUMN `istProtokollfuehrer` `IsMinuteTaker` tinyint(1) DEFAULT 0;

ALTER TABLE `tblMeetingAttachment`
  CHANGE COLUMN `Besprechung` `Meeting` int(11) NOT NULL,
  CHANGE COLUMN `Dateiname` `FileName` varchar(255) NOT NULL,
  CHANGE COLUMN `OriginalDateiname` `OriginalFileName` varchar(255) NOT NULL,
  CHANGE COLUMN `Dateipfad` `FilePath` varchar(255) DEFAULT NULL,
  CHANGE COLUMN `MimeTyp` `MimeType` varchar(100) DEFAULT NULL,
  CHANGE COLUMN `Dateigroesse` `FileSize` bigint(20) DEFAULT NULL,
  CHANGE COLUMN `Beschreibung` `Description` varchar(255) DEFAULT NULL,
  CHANGE COLUMN `Sortierung` `SortOrder` int(11) NOT NULL DEFAULT 0,
  CHANGE COLUMN `DruckenBeiEinladung` `PrintWithInvitation` enum('Y','N') NOT NULL DEFAULT 'N',
  CHANGE COLUMN `DruckenBeiProtokoll` `PrintWithMinutes` enum('Y','N') NOT NULL DEFAULT 'N',
  CHANGE COLUMN `HochgeladenVon` `UploadedBy` int(11) DEFAULT NULL,
  CHANGE COLUMN `istAktiviert` `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  CHANGE COLUMN `erstelltAm` `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  CHANGE COLUMN `geaendertAm` `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp();

ALTER TABLE `tblTask`
  CHANGE COLUMN `BesprechungID` `MeetingID` int(11) NOT NULL,
  CHANGE COLUMN `ErstellerUserID` `CreatorUserID` int(11) DEFAULT NULL,
  CHANGE COLUMN `VerantwortlicherUserID` `AssigneeUserID` int(11) DEFAULT NULL,
  CHANGE COLUMN `Titel` `Title` varchar(255) NOT NULL,
  CHANGE COLUMN `Beschreibung` `Description` text DEFAULT NULL,
  CHANGE COLUMN `istErledigt` `IsDone` tinyint(1) NOT NULL DEFAULT 0,
  CHANGE COLUMN `erstelltAm` `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  CHANGE COLUMN `geaendertAm` `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp();

-- ---------------------------------------------------------------------------
-- 4. Enum-Werte migrieren
-- ---------------------------------------------------------------------------
-- tblMeeting.Status: Übergangsweise alle Werte erlauben, dann mappen
ALTER TABLE `tblMeeting`
  MODIFY COLUMN `Status` enum('geplant','live','beendet','storniert','planned','finished','cancelled') DEFAULT 'geplant';

UPDATE `tblMeeting` SET `Status` = 'planned'   WHERE `Status` = 'geplant';
UPDATE `tblMeeting` SET `Status` = 'finished'  WHERE `Status` = 'beendet';
UPDATE `tblMeeting` SET `Status` = 'cancelled' WHERE `Status` = 'storniert';

ALTER TABLE `tblMeeting`
  MODIFY COLUMN `Status` enum('planned','live','finished','cancelled') DEFAULT 'planned';

-- tblMeetingPoint.Type: CHECK-Constraint neu setzen, Werte mappen
ALTER TABLE `tblMeetingPoint` DROP CONSTRAINT `chk_tblBesprechungspunkt_Typ`;

UPDATE `tblMeetingPoint` SET `Type` = 'discussion' WHERE `Type` = 'diskussion';
UPDATE `tblMeetingPoint` SET `Type` = 'voting'     WHERE `Type` = 'abstimmung';

ALTER TABLE `tblMeetingPoint`
  ADD CONSTRAINT `chk_tblMeetingPoint_Type` CHECK (`Type` in ('information','discussion','voting'));

-- tblTask: CHECK-Constraint auf neuen Spaltennamen
ALTER TABLE `tblTask` DROP CONSTRAINT `chk_tblTask_istErledigt`;
ALTER TABLE `tblTask`
  ADD CONSTRAINT `chk_tblTask_IsDone` CHECK (`IsDone` in (0,1));

-- ---------------------------------------------------------------------------
-- 5. Indizes umbenennen (kosmetisch, MariaDB >= 10.5.2)
-- ---------------------------------------------------------------------------
ALTER TABLE `tblCountry`
  RENAME INDEX `uq_tblCountries_isoAlpha2` TO `uq_tblCountry_isoAlpha2`,
  RENAME INDEX `uq_tblCountries_isoAlpha3` TO `uq_tblCountry_isoAlpha3`,
  RENAME INDEX `uq_tblCountries_isoNumeric` TO `uq_tblCountry_isoNumeric`,
  RENAME INDEX `idx_tblCountries_nameEn` TO `idx_tblCountry_nameEn`,
  RENAME INDEX `idx_tblCountries_nameDe` TO `idx_tblCountry_nameDe`,
  RENAME INDEX `idx_tblCountries_isActive_sortOrder` TO `idx_tblCountry_isActive_sortOrder`;

ALTER TABLE `tblLanguage`
  RENAME INDEX `uq_tblLanguages_LocaleCode` TO `uq_tblLanguage_LocaleCode`;

ALTER TABLE `tblUser`
  RENAME INDEX `fk_Organisation` TO `fk_Organization`;

ALTER TABLE `tblMeeting`
  RENAME INDEX `idx_tblBesprechung_OrganisationID` TO `idx_tblMeeting_OrganizationID`,
  RENAME INDEX `idx_tblBesprechung_ErstellerUserID` TO `idx_tblMeeting_CreatorUserID`,
  RENAME INDEX `idx_tblBesprechung_Datum` TO `idx_tblMeeting_Date`;

ALTER TABLE `tblMeetingPoint`
  RENAME INDEX `idx_tblBesprechungspunkt_BesprechungID` TO `idx_tblMeetingPoint_MeetingID`,
  RENAME INDEX `idx_tblBesprechungspunkt_Position` TO `idx_tblMeetingPoint_Position`;

ALTER TABLE `tblOrganizationFunction`
  RENAME INDEX `uk_OrgFunktion_Organisation_Bezeichnung` TO `uk_OrgFunction_Organization_Name`,
  RENAME INDEX `fk_OrgFunktion_Organisation` TO `fk_OrgFunction_Organization`;

ALTER TABLE `tblOrganizationMember`
  RENAME INDEX `uq_tblOrganisationsMitglieder_OrgPerson` TO `uq_tblOrganizationMember_OrgPerson`,
  RENAME INDEX `fk_OrgMitglied_Organisation` TO `fk_OrgMember_Organization`,
  RENAME INDEX `fk_OrgMitglied_User` TO `fk_OrgMember_User`,
  RENAME INDEX `fk_OrgMitglied_Funktion` TO `fk_OrgMember_Function`,
  RENAME INDEX `idx_tblOrganisationsMitglieder_Person` TO `idx_tblOrganizationMember_Person`;

ALTER TABLE `tblMeetingParticipant`
  RENAME INDEX `idx_tblBesprechungsteilnehmer_Besprechung` TO `idx_tblMeetingParticipant_Meeting`,
  RENAME INDEX `idx_tblBesprechungsteilnehmer_OrganisationsMitglied` TO `idx_tblMeetingParticipant_OrganizationMember`,
  RENAME INDEX `idx_tblBesprechungsteilnehmer_User` TO `idx_tblMeetingParticipant_User`,
  RENAME INDEX `idx_tblBesprechungsteilnehmer_Funktion` TO `idx_tblMeetingParticipant_Function`,
  RENAME INDEX `idx_tblBesprechungsteilnehmer_Person` TO `idx_tblMeetingParticipant_Person`;

ALTER TABLE `tblMeetingAttachment`
  RENAME INDEX `idx_tblBesprechungsanhang_Besprechung` TO `idx_tblMeetingAttachment_Meeting`,
  RENAME INDEX `idx_tblBesprechungsanhang_HochgeladenVon` TO `idx_tblMeetingAttachment_UploadedBy`;

ALTER TABLE `tblTask`
  RENAME INDEX `idx_tblTask_Besprechung_Deadline` TO `idx_tblTask_Meeting_Deadline`,
  RENAME INDEX `idx_tblTask_Verantwortlicher_Offen` TO `idx_tblTask_Assignee_Open`,
  RENAME INDEX `fk_tblTask_Ersteller` TO `fk_tblTask_Creator`;

-- ---------------------------------------------------------------------------
-- 6. Fremdschlüssel mit englischen Namen neu anlegen
-- ---------------------------------------------------------------------------
ALTER TABLE `tblUser`
  ADD CONSTRAINT `fk_Organization` FOREIGN KEY (`DefaultOrganization`) REFERENCES `tblOrganization` (`id`),
  ADD CONSTRAINT `fk_tblUser_Person` FOREIGN KEY (`Person`) REFERENCES `tblPerson` (`id`) ON UPDATE CASCADE;

ALTER TABLE `tblMeeting`
  ADD CONSTRAINT `fk_tblMeeting_Organization` FOREIGN KEY (`OrganizationID`) REFERENCES `tblOrganization` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tblMeeting_User` FOREIGN KEY (`CreatorUserID`) REFERENCES `tblUser` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `tblMeetingPoint`
  ADD CONSTRAINT `fk_tblMeetingPoint_Meeting` FOREIGN KEY (`MeetingID`) REFERENCES `tblMeeting` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `tblOrganizationFunction`
  ADD CONSTRAINT `fk_OrgFunction_Organization` FOREIGN KEY (`Organization`) REFERENCES `tblOrganization` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `tblOrganizationMember`
  ADD CONSTRAINT `fk_OrgMember_Function` FOREIGN KEY (`Function`) REFERENCES `tblOrganizationFunction` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_OrgMember_Organization` FOREIGN KEY (`Organization`) REFERENCES `tblOrganization` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_OrgMember_User` FOREIGN KEY (`User`) REFERENCES `tblUser` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tblOrganizationMember_Person` FOREIGN KEY (`Person`) REFERENCES `tblPerson` (`id`) ON UPDATE CASCADE;

ALTER TABLE `tblMeetingParticipant`
  ADD CONSTRAINT `fk_tblMeetingParticipant_Meeting` FOREIGN KEY (`Meeting`) REFERENCES `tblMeeting` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tblMeetingParticipant_Function` FOREIGN KEY (`Function`) REFERENCES `tblOrganizationFunction` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tblMeetingParticipant_OrganizationMember` FOREIGN KEY (`OrganizationMember`) REFERENCES `tblOrganizationMember` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tblMeetingParticipant_Person` FOREIGN KEY (`Person`) REFERENCES `tblPerson` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tblMeetingParticipant_User` FOREIGN KEY (`User`) REFERENCES `tblUser` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `tblMeetingAttachment`
  ADD CONSTRAINT `fk_tblMeetingAttachment_Meeting` FOREIGN KEY (`Meeting`) REFERENCES `tblMeeting` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tblMeetingAttachment_User` FOREIGN KEY (`UploadedBy`) REFERENCES `tblUser` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `tblTask`
  ADD CONSTRAINT `fk_tblTask_Meeting` FOREIGN KEY (`MeetingID`) REFERENCES `tblMeeting` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tblTask_Creator` FOREIGN KEY (`CreatorUserID`) REFERENCES `tblUser` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tblTask_Assignee` FOREIGN KEY (`AssigneeUserID`) REFERENCES `tblUser` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;
