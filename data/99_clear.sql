SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM `tblMeeting`;
DELETE FROM `tblMeetingPoint`;
DELETE FROM `tblMeetingParticipant`;
DELETE FROM `tblOrganizationFunction`;
DELETE FROM `tblTask`;
DELETE FROM `tblUser`;
DELETE FROM `tblOrganization`;
DELETE FROM `tblPerson`;

ALTER TABLE `tblMeeting` AUTO_INCREMENT = 1;
ALTER TABLE `tblMeetingPoint` AUTO_INCREMENT = 1;
ALTER TABLE `tblMeetingParticipant` AUTO_INCREMENT = 1;
ALTER TABLE `tblMeetingAttachment` AUTO_INCREMENT = 1;
ALTER TABLE `tblOrganization` AUTO_INCREMENT = 1;
ALTER TABLE `tblOrganizationFunction` AUTO_INCREMENT = 1;
ALTER TABLE `tblPerson` AUTO_INCREMENT = 1;
ALTER TABLE `tblTask` AUTO_INCREMENT = 1;
ALTER TABLE `tblUser` AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;
