SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE `tblOrganisation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Logo` varchar(255) NOT NULL,
  `Name1` varchar(255) NOT NULL,
  `Name2` varchar(255) DEFAULT NULL,
  `Homepage` varchar(255) DEFAULT NULL,
  `EMail` varchar(255) DEFAULT NULL,
  `Telefon` varchar(255) DEFAULT NULL,
  `Telefax` varchar(255) DEFAULT NULL,
  `Straße` varchar(255) DEFAULT NULL,
  `PLZ` varchar(50) DEFAULT NULL,
  `Ort` varchar(255) DEFAULT NULL,
  `Land` varchar(50) DEFAULT NULL,
  `Registergericht` varchar(50) DEFAULT NULL,
  `Registereintrag` varchar(50) DEFAULT NULL,
  `istAktiviert` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE `tblPerson` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Vorname` varchar(100) DEFAULT NULL,
  `Nachname` varchar(100) DEFAULT NULL,
  `Mail` varchar(255) DEFAULT NULL,
  `Telefon` varchar(255) DEFAULT NULL,
  `Strasse` varchar(255) DEFAULT NULL,
  `PLZ` varchar(255) DEFAULT NULL,
  `Ort` varchar(255) DEFAULT NULL,
  `Land` int(11) DEFAULT NULL,
  `istAktiv` tinyint(1) NOT NULL DEFAULT 1,
  `istGeloescht` tinyint(1) NOT NULL DEFAULT 0,
  `ErstelltAm` datetime NOT NULL DEFAULT current_timestamp(),
  `GeaendertAm` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tblPerson_Mail` (`Mail`),
  KEY `idx_tblPerson_Name` (`Nachname`,`Vorname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE `tblCountries` (
  `countryId` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `isoAlpha2` char(2) NOT NULL,
  `isoAlpha3` char(3) DEFAULT NULL,
  `isoNumeric` char(3) DEFAULT NULL,
  `nameEn` varchar(120) NOT NULL,
  `nameDe` varchar(120) DEFAULT NULL,
  `istAktiviert` tinyint(1) NOT NULL DEFAULT 1,
  `sortOrder` smallint(5) unsigned NOT NULL DEFAULT 0,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `updatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`countryId`),
  UNIQUE KEY `uq_tblCountries_isoAlpha2` (`isoAlpha2`),
  UNIQUE KEY `uq_tblCountries_isoAlpha3` (`isoAlpha3`),
  UNIQUE KEY `uq_tblCountries_isoNumeric` (`isoNumeric`),
  KEY `idx_tblCountries_nameEn` (`nameEn`),
  KEY `idx_tblCountries_nameDe` (`nameDe`),
  KEY `idx_tblCountries_isActive_sortOrder` (`istAktiviert`,`sortOrder`,`nameDe`,`nameEn`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tblLanguages` (
  `LanguageID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `IsoCode2` char(2) NOT NULL,
  `IsoCode3` char(3) DEFAULT NULL,
  `LocaleCode` varchar(10) DEFAULT NULL,
  `CountryCode2` char(2) DEFAULT NULL,
  `LanguageNameEn` varchar(100) NOT NULL,
  `LanguageNameNative` varchar(100) NOT NULL,
  `FlagEmoji` varchar(8) DEFAULT NULL,
  `FlagSvg` mediumtext DEFAULT NULL,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `SortOrder` smallint(5) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`LanguageID`),
  UNIQUE KEY `uq_tblLanguages_LocaleCode` (`LocaleCode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tblUser` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Person` int(11) DEFAULT NULL,
  `Pass` varchar(255) NOT NULL,
  `StandardOrganisation` int(11) NOT NULL,
  `Foto` varchar(255) DEFAULT NULL,
  `lang` varchar(3) NOT NULL DEFAULT 'de',
  `istAktiv` tinyint(1) NOT NULL DEFAULT 1,
  `istAdmin` tinyint(1) DEFAULT 0,
  `istGeloescht` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tblUser_Person` (`Person`),
  KEY `fk_Organisation` (`StandardOrganisation`),
  KEY `idx_tblUser_Person` (`Person`),
  CONSTRAINT `fk_Organisation` FOREIGN KEY (`StandardOrganisation`) REFERENCES `tblOrganisation` (`id`),
  CONSTRAINT `fk_tblUser_Person` FOREIGN KEY (`Person`) REFERENCES `tblPerson` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE `tblBesprechung` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `OrganisationID` int(11) NOT NULL,
  `ErstellerUserID` int(11) DEFAULT NULL,
  `Titel` varchar(255) NOT NULL,
  `Datum` date DEFAULT NULL,
  `Beginn` time DEFAULT NULL,
  `Ende` time DEFAULT NULL,
  `Ort` varchar(255) DEFAULT NULL,
  `Status` enum('geplant','live','beendet','storniert') DEFAULT 'geplant',
  `istAktiviert` tinyint(1) NOT NULL DEFAULT 1,
  `istVeröffentlicht` tinyint(1) NOT NULL DEFAULT 0,
  `erstelltAm` datetime NOT NULL DEFAULT current_timestamp(),
  `geaendertAm` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `public_code` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tblBesprechung_OrganisationID` (`OrganisationID`),
  KEY `idx_tblBesprechung_ErstellerUserID` (`ErstellerUserID`),
  KEY `idx_tblBesprechung_Datum` (`Datum`),
  CONSTRAINT `fk_tblBesprechung_Organisation` FOREIGN KEY (`OrganisationID`) REFERENCES `tblOrganisation` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_tblBesprechung_User` FOREIGN KEY (`ErstellerUserID`) REFERENCES `tblUser` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE `tblBesprechungspunkt` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `BesprechungID` int(11) NOT NULL,
  `Position` int(11) NOT NULL DEFAULT 1,
  `ElternPosition` int(11) DEFAULT NULL,
  `Titel` varchar(255) NOT NULL,
  `Beschreibung` text DEFAULT NULL,
  `Typ` varchar(20) NOT NULL DEFAULT 'information',
  `Ergebnis` text DEFAULT NULL,
  `Ergebnis_Zustimmung` int(11) DEFAULT NULL,
  `Ergebnis_Enthaltung` int(11) DEFAULT NULL,
  `Ergebnis_Ablehnung` int(11) DEFAULT NULL,
  `istAktiviert` tinyint(1) NOT NULL DEFAULT 1,
  `erstelltAm` datetime NOT NULL DEFAULT current_timestamp(),
  `geaendertAm` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tblBesprechungspunkt_BesprechungID` (`BesprechungID`),
  KEY `idx_tblBesprechungspunkt_Position` (`BesprechungID`,`Position`),
  CONSTRAINT `fk_tblBesprechungspunkt_Besprechung` FOREIGN KEY (`BesprechungID`) REFERENCES `tblBesprechung` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_tblBesprechungspunkt_Typ` CHECK (`Typ` in ('information','diskussion','abstimmung'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE `tblOrganisationsFunktionen` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Organisation` int(11) NOT NULL,
  `Bezeichnung` varchar(100) NOT NULL,
  `Beschreibung` varchar(255) DEFAULT NULL,
  `Sortierung` int(11) DEFAULT 0,
  `istAktiv` tinyint(1) NOT NULL DEFAULT 1,
  `DarfUnterschreiben` tinyint(1) NOT NULL DEFAULT 0,
  `DarfOrganisationVerwalten` tinyint(1) NOT NULL DEFAULT 0,
  `DarfBesprechungenVerwalten` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_OrgFunktion_Organisation_Bezeichnung` (`Organisation`,`Bezeichnung`),
  KEY `fk_OrgFunktion_Organisation` (`Organisation`),
  CONSTRAINT `fk_OrgFunktion_Organisation` FOREIGN KEY (`Organisation`) REFERENCES `tblOrganisation` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE `tblOrganisationsMitglieder` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Organisation` int(11) NOT NULL,
  `Person` int(11) DEFAULT NULL,
  `User` int(11) DEFAULT NULL,
  `Funktion` int(11) DEFAULT NULL,
  `Bemerkung` varchar(255) DEFAULT NULL,
  `istAktiv` tinyint(1) NOT NULL DEFAULT 1,
  `istGeloescht` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tblOrganisationsMitglieder_OrgPerson` (`Organisation`,`Person`),
  KEY `fk_OrgMitglied_Organisation` (`Organisation`),
  KEY `fk_OrgMitglied_User` (`User`),
  KEY `fk_OrgMitglied_Funktion` (`Funktion`),
  KEY `idx_tblOrganisationsMitglieder_Person` (`Person`),
  CONSTRAINT `fk_OrgMitglied_Funktion` FOREIGN KEY (`Funktion`) REFERENCES `tblOrganisationsFunktionen` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_OrgMitglied_Organisation` FOREIGN KEY (`Organisation`) REFERENCES `tblOrganisation` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_OrgMitglied_User` FOREIGN KEY (`User`) REFERENCES `tblUser` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tblOrganisationsMitglieder_Person` FOREIGN KEY (`Person`) REFERENCES `tblPerson` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE `tblBesprechungsteilnehmer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Besprechung` int(11) NOT NULL,
  `Person` int(11) DEFAULT NULL,
  `OrganisationsMitglied` int(11) DEFAULT NULL,
  `User` int(11) DEFAULT NULL,
  `Funktion` int(11) DEFAULT NULL,
  `istEingeladen` tinyint(1) NOT NULL DEFAULT 1,
  `istAnwesend` tinyint(1) NOT NULL DEFAULT 0,
  `istEntschuldigt` tinyint(1) NOT NULL DEFAULT 0,
  `istAbwesend` tinyint(1) NOT NULL DEFAULT 0,
  `istAktiv` tinyint(1) NOT NULL DEFAULT 1,
  `darfUnterschreiben` tinyint(1) NOT NULL DEFAULT 0,
  `istSitzungsleitung` tinyint(1) NOT NULL DEFAULT 0,
  `istProtokollfuehrer` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_tblBesprechungsteilnehmer_Besprechung` (`Besprechung`),
  KEY `idx_tblBesprechungsteilnehmer_OrganisationsMitglied` (`OrganisationsMitglied`),
  KEY `idx_tblBesprechungsteilnehmer_User` (`User`),
  KEY `idx_tblBesprechungsteilnehmer_Funktion` (`Funktion`),
  KEY `idx_tblBesprechungsteilnehmer_Person` (`Person`),
  CONSTRAINT `fk_tblBesprechungsteilnehmer_Besprechung` FOREIGN KEY (`Besprechung`) REFERENCES `tblBesprechung` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tblBesprechungsteilnehmer_Funktion` FOREIGN KEY (`Funktion`) REFERENCES `tblOrganisationsFunktionen` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tblBesprechungsteilnehmer_OrganisationsMitglied` FOREIGN KEY (`OrganisationsMitglied`) REFERENCES `tblOrganisationsMitglieder` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tblBesprechungsteilnehmer_Person` FOREIGN KEY (`Person`) REFERENCES `tblPerson` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_tblBesprechungsteilnehmer_User` FOREIGN KEY (`User`) REFERENCES `tblUser` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE `tblBesprechungsanhang` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Besprechung` int(11) NOT NULL,
  `Dateiname` varchar(255) NOT NULL,
  `OriginalDateiname` varchar(255) NOT NULL,
  `Dateipfad` varchar(255) DEFAULT NULL,
  `MimeTyp` varchar(100) DEFAULT NULL,
  `Dateigroesse` bigint(20) DEFAULT NULL,
  `Beschreibung` varchar(255) DEFAULT NULL,
  `Sortierung` int(11) NOT NULL DEFAULT 0,
  `DruckenBeiEinladung` enum('Y','N') NOT NULL DEFAULT 'N',
  `DruckenBeiProtokoll` enum('Y','N') NOT NULL DEFAULT 'N',
  `HochgeladenVon` int(11) DEFAULT NULL,
  `istAktiviert` tinyint(1) NOT NULL DEFAULT 1,
  `erstelltAm` datetime NOT NULL DEFAULT current_timestamp(),
  `geaendertAm` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tblBesprechungsanhang_Besprechung` (`Besprechung`),
  KEY `idx_tblBesprechungsanhang_HochgeladenVon` (`HochgeladenVon`),
  CONSTRAINT `fk_tblBesprechungsanhang_Besprechung` FOREIGN KEY (`Besprechung`) REFERENCES `tblBesprechung` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tblBesprechungsanhang_User` FOREIGN KEY (`HochgeladenVon`) REFERENCES `tblUser` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE `tblTask` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `BesprechungID` int(11) NOT NULL,
  `ErstellerUserID` int(11) DEFAULT NULL,
  `VerantwortlicherUserID` int(11) DEFAULT NULL,
  `Titel` varchar(255) NOT NULL,
  `Beschreibung` text DEFAULT NULL,
  `Deadline` date DEFAULT NULL,
  `istErledigt` tinyint(1) NOT NULL DEFAULT 0,
  `erstelltAm` datetime NOT NULL DEFAULT current_timestamp(),
  `geaendertAm` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tblTask_Besprechung_Deadline` (`BesprechungID`,`Deadline`),
  KEY `idx_tblTask_Verantwortlicher_Offen` (`VerantwortlicherUserID`,`istErledigt`,`Deadline`),
  KEY `fk_tblTask_Ersteller` (`ErstellerUserID`),
  CONSTRAINT `fk_tblTask_Besprechung` FOREIGN KEY (`BesprechungID`) REFERENCES `tblBesprechung` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tblTask_Ersteller` FOREIGN KEY (`ErstellerUserID`) REFERENCES `tblUser` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tblTask_Verantwortlicher` FOREIGN KEY (`VerantwortlicherUserID`) REFERENCES `tblUser` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_tblTask_istErledigt` CHECK (`istErledigt` in (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE `tblLog`(
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `UserId` int(11) NOT NULL,
  `Action` varchar(255) NOT NULL,
  `Timestamp` datetime NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_tblLog_User` FOREIGN KEY (`UserId`) REFERENCES `tblUser` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

SET FOREIGN_KEY_CHECKS = 1;