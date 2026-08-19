SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM `tblBesprechung`;
DELETE FROM `tblBesprechungspunkt`;
DELETE FROM `tblBesprechungsteilnehmer`;
DELETE FROM `tblOrganisationsFunktionen`;
DELETE FROM `tblTask`;
DELETE FROM `tblUser`;
DELETE FROM `tblOrganisation`;
DELETE FROM `tblPerson`;

ALTER TABLE `tblBesprechung` AUTO_INCREMENT = 1;
ALTER TABLE `tblBesprechungspunkt` AUTO_INCREMENT = 1;
ALTER TABLE `tblBesprechungsteilnehmer` AUTO_INCREMENT = 1;
ALTER TABLE `tblOrganisation` AUTO_INCREMENT = 1;
ALTER TABLE `tblOrganisationsFunktionen` AUTO_INCREMENT = 1;
ALTER TABLE `tblPerson` AUTO_INCREMENT = 1;
ALTER TABLE `tblTask` AUTO_INCREMENT = 1;
ALTER TABLE `tblUser` AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;