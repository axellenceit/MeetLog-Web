-- Reset script for initial database setup
INSERT INTO `tblOrganization` (`id`, `Logo`, `Name1`, `Name2`, `Homepage`, `EMail`, `Phone`, `Fax`, `Street`, `ZipCode`, `City`, `Country`, `RegistryCourt`, `RegistryEntry`, `IsActive`) VALUES
(1, '', 'Musterverein Feuerwehr e.V.', NULL, 'https://www.musterverein-feuerwehr.de', 'info@musterverein-feuerwehr.de', '+49 30 1234567', NULL, 'Feuerwehrstraße 1', '10115', 'Berlin', 'DE', 'Amtsgericht Berlin', 'VR 12345', 1);

INSERT INTO `tblPerson` (`id`, `FirstName`, `LastName`, `Mail`, `Phone`, `Street`, `ZipCode`, `City`, `Country`, `IsActive`, `IsDeleted`) VALUES
(1, 'Anna', 'Admin', 'anna.admin@example.com', '+49 170 1111111', 'Hauptstraße 1', '10115', 'Berlin', 1, 1, 0);

INSERT INTO `tblUser` (`id`, `Person`, `Password`, `DefaultOrganization`, `Photo`, `Lang`, `IsActive`, `IsAdmin`, `IsDeleted`) VALUES
(1, 1, '$2y$12$2C5yvnwlSelwSzU2Y.l8Y.fdQ3W9I5gNo2.cfolRzpOCuWvlWJsiy', 1, NULL, 'de', 1, 1, 0);

INSERT INTO `tblOrganizationFunction` (`id`, `Organization`, `Name`, `Description`, `SortOrder`, `IsActive`, `MaySign`, `MayManageOrganization`, `MayManageMeetings`) VALUES
(1, 1, 'Vorsitzende/r', 'Vorsitzende(r) des Vereins', 1, 1, 1, 1, 1);

INSERT INTO `tblOrganizationMember` (`id`, `Organization`, `Person`, `User`, `Function`, `Note`, `IsActive`, `IsDeleted`) VALUES
(1, 1, 1, 1, 1, NULL, 1, 0);