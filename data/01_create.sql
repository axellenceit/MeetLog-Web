SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE `tblOrganization` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Logo` varchar(255) NOT NULL,
  `Name1` varchar(255) NOT NULL,
  `Name2` varchar(255) DEFAULT NULL,
  `Homepage` varchar(255) DEFAULT NULL,
  `EMail` varchar(255) DEFAULT NULL,
  `Phone` varchar(255) DEFAULT NULL,
  `Fax` varchar(255) DEFAULT NULL,
  `Street` varchar(255) DEFAULT NULL,
  `ZipCode` varchar(50) DEFAULT NULL,
  `City` varchar(255) DEFAULT NULL,
  `Country` varchar(50) DEFAULT NULL,
  `RegistryCourt` varchar(50) DEFAULT NULL,
  `RegistryEntry` varchar(50) DEFAULT NULL,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE `tblPerson` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `FirstName` varchar(100) DEFAULT NULL,
  `LastName` varchar(100) DEFAULT NULL,
  `Mail` varchar(255) DEFAULT NULL,
  `Phone` varchar(255) DEFAULT NULL,
  `Street` varchar(255) DEFAULT NULL,
  `ZipCode` varchar(255) DEFAULT NULL,
  `City` varchar(255) DEFAULT NULL,
  `Country` int(11) DEFAULT NULL,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tblPerson_Mail` (`Mail`),
  KEY `idx_tblPerson_Name` (`LastName`,`FirstName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE `tblCountry` (
  `countryId` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `isoAlpha2` char(2) NOT NULL,
  `isoAlpha3` char(3) DEFAULT NULL,
  `isoNumeric` char(3) DEFAULT NULL,
  `nameEn` varchar(120) NOT NULL,
  `nameDe` varchar(120) DEFAULT NULL,
  `isActive` tinyint(1) NOT NULL DEFAULT 1,
  `sortOrder` smallint(5) unsigned NOT NULL DEFAULT 0,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `updatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`countryId`),
  UNIQUE KEY `uq_tblCountry_isoAlpha2` (`isoAlpha2`),
  UNIQUE KEY `uq_tblCountry_isoAlpha3` (`isoAlpha3`),
  UNIQUE KEY `uq_tblCountry_isoNumeric` (`isoNumeric`),
  KEY `idx_tblCountry_nameEn` (`nameEn`),
  KEY `idx_tblCountry_nameDe` (`nameDe`),
  KEY `idx_tblCountry_isActive_sortOrder` (`isActive`,`sortOrder`,`nameDe`,`nameEn`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tblLanguage` (
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
  UNIQUE KEY `uq_tblLanguage_LocaleCode` (`LocaleCode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tblUser` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Person` int(11) DEFAULT NULL,
  `Password` varchar(255) NOT NULL,
  `DefaultOrganization` int(11) NOT NULL,
  `Photo` varchar(255) DEFAULT NULL,
  `Lang` varchar(3) NOT NULL DEFAULT 'de',
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsAdmin` tinyint(1) DEFAULT 0,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tblUser_Person` (`Person`),
  KEY `fk_Organization` (`DefaultOrganization`),
  KEY `idx_tblUser_Person` (`Person`),
  CONSTRAINT `fk_Organization` FOREIGN KEY (`DefaultOrganization`) REFERENCES `tblOrganization` (`id`),
  CONSTRAINT `fk_tblUser_Person` FOREIGN KEY (`Person`) REFERENCES `tblPerson` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE `tblMeeting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `OrganizationID` int(11) NOT NULL,
  `CreatorUserID` int(11) DEFAULT NULL,
  `Title` varchar(255) NOT NULL,
  `Date` date DEFAULT NULL,
  `StartTime` time DEFAULT NULL,
  `EndTime` time DEFAULT NULL,
  `Location` varchar(255) DEFAULT NULL,
  `Status` enum('planned','live','finished','cancelled') DEFAULT 'planned',
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsPublished` tinyint(1) NOT NULL DEFAULT 0,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `PublicCode` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tblMeeting_OrganizationID` (`OrganizationID`),
  KEY `idx_tblMeeting_CreatorUserID` (`CreatorUserID`),
  KEY `idx_tblMeeting_Date` (`Date`),
  CONSTRAINT `fk_tblMeeting_Organization` FOREIGN KEY (`OrganizationID`) REFERENCES `tblOrganization` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_tblMeeting_User` FOREIGN KEY (`CreatorUserID`) REFERENCES `tblUser` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE `tblMeetingPoint` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `MeetingID` int(11) NOT NULL,
  `Position` int(11) NOT NULL DEFAULT 1,
  `ParentPosition` int(11) DEFAULT NULL,
  `Title` varchar(255) NOT NULL,
  `Description` text DEFAULT NULL,
  `Type` varchar(20) NOT NULL DEFAULT 'information',
  `Result` text DEFAULT NULL,
  `ResultApproval` int(11) DEFAULT NULL,
  `ResultAbstention` int(11) DEFAULT NULL,
  `ResultRejection` int(11) DEFAULT NULL,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tblMeetingPoint_MeetingID` (`MeetingID`),
  KEY `idx_tblMeetingPoint_Position` (`MeetingID`,`Position`),
  CONSTRAINT `fk_tblMeetingPoint_Meeting` FOREIGN KEY (`MeetingID`) REFERENCES `tblMeeting` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_tblMeetingPoint_Type` CHECK (`Type` in ('information','discussion','voting'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE `tblOrganizationFunction` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Organization` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Description` varchar(255) DEFAULT NULL,
  `SortOrder` int(11) DEFAULT 0,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `MaySign` tinyint(1) NOT NULL DEFAULT 0,
  `MayManageOrganization` tinyint(1) NOT NULL DEFAULT 0,
  `MayManageMeetings` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_OrgFunction_Organization_Name` (`Organization`,`Name`),
  KEY `fk_OrgFunction_Organization` (`Organization`),
  CONSTRAINT `fk_OrgFunction_Organization` FOREIGN KEY (`Organization`) REFERENCES `tblOrganization` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE `tblOrganizationMember` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Organization` int(11) NOT NULL,
  `Person` int(11) DEFAULT NULL,
  `User` int(11) DEFAULT NULL,
  `Function` int(11) DEFAULT NULL,
  `Note` varchar(255) DEFAULT NULL,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tblOrganizationMember_OrgPerson` (`Organization`,`Person`),
  KEY `fk_OrgMember_Organization` (`Organization`),
  KEY `fk_OrgMember_User` (`User`),
  KEY `fk_OrgMember_Function` (`Function`),
  KEY `idx_tblOrganizationMember_Person` (`Person`),
  CONSTRAINT `fk_OrgMember_Function` FOREIGN KEY (`Function`) REFERENCES `tblOrganizationFunction` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_OrgMember_Organization` FOREIGN KEY (`Organization`) REFERENCES `tblOrganization` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_OrgMember_User` FOREIGN KEY (`User`) REFERENCES `tblUser` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tblOrganizationMember_Person` FOREIGN KEY (`Person`) REFERENCES `tblPerson` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE `tblMeetingParticipant` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Meeting` int(11) NOT NULL,
  `Person` int(11) DEFAULT NULL,
  `OrganizationMember` int(11) DEFAULT NULL,
  `User` int(11) DEFAULT NULL,
  `Function` int(11) DEFAULT NULL,
  `IsInvited` tinyint(1) NOT NULL DEFAULT 1,
  `IsPresent` tinyint(1) NOT NULL DEFAULT 0,
  `IsExcused` tinyint(1) NOT NULL DEFAULT 0,
  `IsAbsent` tinyint(1) NOT NULL DEFAULT 0,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `MaySign` tinyint(1) NOT NULL DEFAULT 0,
  `IsChair` tinyint(1) NOT NULL DEFAULT 0,
  `IsMinuteTaker` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_tblMeetingParticipant_Meeting` (`Meeting`),
  KEY `idx_tblMeetingParticipant_OrganizationMember` (`OrganizationMember`),
  KEY `idx_tblMeetingParticipant_User` (`User`),
  KEY `idx_tblMeetingParticipant_Function` (`Function`),
  KEY `idx_tblMeetingParticipant_Person` (`Person`),
  CONSTRAINT `fk_tblMeetingParticipant_Meeting` FOREIGN KEY (`Meeting`) REFERENCES `tblMeeting` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tblMeetingParticipant_Function` FOREIGN KEY (`Function`) REFERENCES `tblOrganizationFunction` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tblMeetingParticipant_OrganizationMember` FOREIGN KEY (`OrganizationMember`) REFERENCES `tblOrganizationMember` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tblMeetingParticipant_Person` FOREIGN KEY (`Person`) REFERENCES `tblPerson` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_tblMeetingParticipant_User` FOREIGN KEY (`User`) REFERENCES `tblUser` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE `tblMeetingAttachment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Meeting` int(11) NOT NULL,
  `FileName` varchar(255) NOT NULL,
  `OriginalFileName` varchar(255) NOT NULL,
  `FilePath` varchar(255) DEFAULT NULL,
  `MimeType` varchar(100) DEFAULT NULL,
  `FileSize` bigint(20) DEFAULT NULL,
  `Description` varchar(255) DEFAULT NULL,
  `SortOrder` int(11) NOT NULL DEFAULT 0,
  `PrintWithInvitation` enum('Y','N') NOT NULL DEFAULT 'N',
  `PrintWithMinutes` enum('Y','N') NOT NULL DEFAULT 'N',
  `UploadedBy` int(11) DEFAULT NULL,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tblMeetingAttachment_Meeting` (`Meeting`),
  KEY `idx_tblMeetingAttachment_UploadedBy` (`UploadedBy`),
  CONSTRAINT `fk_tblMeetingAttachment_Meeting` FOREIGN KEY (`Meeting`) REFERENCES `tblMeeting` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tblMeetingAttachment_User` FOREIGN KEY (`UploadedBy`) REFERENCES `tblUser` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE `tblTask` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `MeetingID` int(11) NOT NULL,
  `CreatorUserID` int(11) DEFAULT NULL,
  `AssigneeUserID` int(11) DEFAULT NULL,
  `Title` varchar(255) NOT NULL,
  `Description` text DEFAULT NULL,
  `Deadline` date DEFAULT NULL,
  `IsDone` tinyint(1) NOT NULL DEFAULT 0,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tblTask_Meeting_Deadline` (`MeetingID`,`Deadline`),
  KEY `idx_tblTask_Assignee_Open` (`AssigneeUserID`,`IsDone`,`Deadline`),
  KEY `fk_tblTask_Creator` (`CreatorUserID`),
  CONSTRAINT `fk_tblTask_Meeting` FOREIGN KEY (`MeetingID`) REFERENCES `tblMeeting` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tblTask_Creator` FOREIGN KEY (`CreatorUserID`) REFERENCES `tblUser` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tblTask_Assignee` FOREIGN KEY (`AssigneeUserID`) REFERENCES `tblUser` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_tblTask_IsDone` CHECK (`IsDone` in (0,1))
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
