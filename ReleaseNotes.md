# MeetLog

MeetLog ist eine webbasierte Anwendung zur strukturierten Vorbereitung, Durchführung und Nachbereitung von Besprechungen in Organisationen. Die erste Beta stellt den zentralen Funktionsumfang für Organisationen, Personen, Meetings, Tagesordnungen, Teilnehmer und Aufgaben bereit.

## Enthaltene Funktionen

* Verwaltung von Organisationen inklusive Kontaktdaten, Logo und Registerinformationen
* Verwaltung von Personen und Organisationsmitgliedern
* Benutzerkonten mit Administrationsrechten
* Rollen- und Berechtigungssystem für:
* Organisationsverwaltung
* Besprechungsverwaltung
* Unterschriftsberechtigung
* Erstellung und Bearbeitung von Besprechungen mit Datum, Uhrzeit, Ort und Status
* Besprechungsstatus:
  * geplant
  * aktiv
  * eingeladen
  * beendet
  * storniert
* Verwaltung strukturierter Tagesordnungspunkte
* Tagesordnungstypen für Information, Diskussion und Abstimmung
* Speicherung von Abstimmungsergebnissen und Beschlüssen
* Teilnehmerverwaltung mit Funktionen und Sitzungsrollen
* Erfassung von Anwesenheit, Entschuldigung und Abwesenheit
* Zuweisung von Sitzungsleitung, Protokollführung und Unterschriftsberechtigung
* Aufgabenverwaltung im Zusammenhang mit Besprechungen
* Aufgaben mit Beschreibung, Verantwortlichem, Fälligkeit und Erledigungsstatus
* Kalenderübersicht auf Basis von FullCalendar
* Versand personalisierter Besprechungseinladungen per E-Mail
* PDF-Anhänge für Einladungen
* Export von Besprechungen und einzelnen Einladungen als PDF
* Veröffentlichung abgeschlossener Besprechungen über öffentliche Links
* Mehrsprachige Benutzeroberfläche mit deutscher und englischer Übersetzung
* Vorbereitung weiterer Sprachen über die Sprachverwaltung
* Profilverwaltung inklusive Profilbild
* Mehrstufiger Installationsassistent
* Unterstützung für leere Installationen und Demo-Daten
* Konfiguration von MySQL/MariaDB und SMTP direkt während der Installation

## Technische Grundlage

MeetLog basiert auf PHP mit einer strukturierten Repository-Architektur. Für PDF-Erzeugung wird TCPDF verwendet, für den E-Mail-Versand PHPMailer. Die Oberfläche nutzt Tabler UI; die Kalenderansicht basiert auf FullCalendar. Das Datenmodell verwendet MySQL oder MariaDB mit UTF-8-Unterstützung und referenzieller Integrität.
