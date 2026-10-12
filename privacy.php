<?php

declare(strict_types=1);

ob_start();

require_once __DIR__ . '/src/init.php';

require_once __DIR__ . '/version.php';

// Datenschutzerklaerung ist bewusst oeffentlich erreichbar (kein Login erforderlich).
// Die Seite ist zweisprachig (DE/EN) auf einer Seite und ignoriert den Sprachumschalter.

$appName = 'MeetLog';
$appVersion = $currentVersion;

$developerName = 'Axel Hoga';
$developerEmail = 'axel@axellence.cloud';
$developerGithub = 'https://github.com/axellenceit';

$webhookHost = 'process.axellence.cloud';
?>

<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Datenschutz / Privacy Policy - <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
    <link rel="icon" href="./favicon.ico" type="image/vnd.microsoft.icon">
</head>

<body>

    <div class="page">
        <div class="container-xl py-4">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="mb-3">
                        <a href="./index.php" class="link-secondary">&larr; Zurück zur Anwendung / Back to the application</a>
                    </div>

                    <div class="btn-group mb-3" role="group" aria-label="Sprache / Language">
                        <a href="#datenschutz-de" class="btn btn-outline-primary">Deutsch</a>
                        <a href="#privacy-en" class="btn btn-outline-primary">English</a>
                    </div>

                    <!-- ==================== DEUTSCH ==================== -->
                    <div class="card mb-4" id="datenschutz-de">
                        <div class="card-body">
                            <h2 class="mb-4">Datenschutzerklärung</h2>

                            <h3 class="h4">1. Verantwortlicher</h3>
                            <p class="mb-4">
                                Verantwortlicher für die Datenverarbeitung im Zusammenhang mit der Feedback- und
                                Fehlermeldefunktion dieser Anwendung (<?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8'); ?>,
                                Version <?= htmlspecialchars($appVersion, ENT_QUOTES, 'UTF-8'); ?>) ist:<br>
                                <strong><?= htmlspecialchars($developerName, ENT_QUOTES, 'UTF-8'); ?></strong><br>
                                E-Mail: <a href="mailto:<?= htmlspecialchars($developerEmail, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($developerEmail, ENT_QUOTES, 'UTF-8'); ?></a><br>
                                <a href="<?= htmlspecialchars($developerGithub, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><?= htmlspecialchars($developerGithub, ENT_QUOTES, 'UTF-8'); ?></a>
                            </p>

                            <h3 class="h4">2. Allgemeine Hinweise</h3>
                            <p class="mb-4">
                                Der Schutz Ihrer personenbezogenen Daten ist uns wichtig. Diese Erklärung informiert Sie
                                darüber, welche Daten bei der Nutzung der Feedback-Funktion (&bdquo;Fehler melden /
                                Verbesserung vorschlagen&ldquo;) erhoben, zu welchem Zweck sie verarbeitet und an wen sie
                                übermittelt werden. Es gelten die Bestimmungen der Datenschutz-Grundverordnung (DSGVO)
                                und des Bundesdatenschutzgesetzes (BDSG).
                            </p>

                            <h3 class="h4">3. Feedback-Funktion (Erstellung eines Entwickler-Issues)</h3>
                            <p>
                                Wenn Sie uns über die Feedback-Funktion eine Meldung senden, wird Ihre Meldung an den
                                Entwickler übertragen und dort als Issue im Projektmanagementsystem
                                (OneDev, betrieben unter <?= htmlspecialchars($webhookHost, ENT_QUOTES, 'UTF-8'); ?>) angelegt.
                                Dabei werden folgende Daten verarbeitet:
                            </p>
                            <div class="table-responsive mb-3">
                                <table class="table table-vcenter">
                                    <thead>
                                        <tr>
                                            <th>Daten</th>
                                            <th>Pflicht / freiwillig</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Art der Meldung (Fehler oder Verbesserung)</td>
                                            <td>Pflicht</td>
                                        </tr>
                                        <tr>
                                            <td>Kurzbeschreibung (Titel) und Detailbeschreibung</td>
                                            <td>Pflicht</td>
                                        </tr>
                                        <tr>
                                            <td>Name</td>
                                            <td><strong>freiwillig</strong> – dient ausschließlich der Nennung bzw. Rückmeldung</td>
                                        </tr>
                                        <tr>
                                            <td>E-Mail-Adresse</td>
                                            <td><strong>freiwillig</strong> – dient ausschließlich Rückfragen oder Rückmeldungen zu Ihrer Meldung</td>
                                        </tr>
                                        <tr>
                                            <td>Technische Metadaten: Instanz-URL (Referrer), App-Version, User-Agent
                                                (Browser und Betriebssystem), PHP-Version, Server-Software</td>
                                            <td>automatisch erhoben – zur Fehleranalyse erforderlich</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <p class="mb-4">
                                Die Übertragung erfolgt verschlüsselt (HTTPS) an den Webhook-Endpunkt
                                <code><?= htmlspecialchars($webhookHost, ENT_QUOTES, 'UTF-8'); ?></code>, der den Issue
                                im System des Entwicklers erstellt. Eine darüber hinausgehende Weitergabe an Dritte,
                                eine Nutzung zu Werbezwecken oder ein Verkauf der Daten findet nicht statt.
                            </p>

                            <h3 class="h4">4. Rechtsgrundlagen</h3>
                            <p class="mb-4">
                                Die Verarbeitung Ihrer Meldung einschließlich der freiwilligen Angaben (Name, E-Mail-Adresse)
                                erfolgt auf Grundlage Ihrer Einwilligung gemäß <strong>Art. 6 Abs. 1 lit. a DSGVO</strong>,
                                die Sie durch das Setzen des Bestätigungs-Kontrollkästchens im Feedback-Formular erteilen.
                                Die Verarbeitung der technischen Metadaten erfolgt zusätzlich auf Grundlage unseres
                                berechtigten Interesses an der Fehleranalyse und Verbesserung der Software gemäß
                                <strong>Art. 6 Abs. 1 lit. f DSGVO</strong>.
                            </p>

                            <h3 class="h4">5. Speicherdauer</h3>
                            <p class="mb-4">
                                Ihre Meldung wird im Issue-System des Entwicklers gespeichert, bis die Bearbeitung
                                abgeschlossen ist und keine Rückfragen mehr zu erwarten sind. Anschließend werden die
                                personenbezogenen Angaben gelöscht bzw. der Issue entfernt, sofern keine gesetzlichen
                                Aufbewahrungspflichten entgegenstehen. Sie können die Löschung Ihrer Angaben jederzeit
                                verlangen (siehe Ziffer 7).
                            </p>

                            <h3 class="h4">6. Widerruf der Einwilligung</h3>
                            <p class="mb-4">
                                Sie können Ihre Einwilligung jederzeit mit Wirkung für die Zukunft widerrufen, indem Sie
                                eine formlose E-Mail an
                                <a href="mailto:<?= htmlspecialchars($developerEmail, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($developerEmail, ENT_QUOTES, 'UTF-8'); ?></a>
                                senden. Die Rechtmäßigkeit der bis zum Widerruf erfolgten Verarbeitung bleibt davon
                                unberührt (Art. 7 Abs. 3 DSGVO).
                            </p>

                            <h3 class="h4">7. Ihre Rechte als betroffene Person</h3>
                            <p>Sie haben gegenüber dem Verantwortlichen folgende Rechte:</p>
                            <ul>
                                <li>Recht auf Auskunft über die verarbeiteten Daten (Art. 15 DSGVO)</li>
                                <li>Recht auf Berichtigung unrichtiger Daten (Art. 16 DSGVO)</li>
                                <li>Recht auf Löschung (Art. 17 DSGVO)</li>
                                <li>Recht auf Einschränkung der Verarbeitung (Art. 18 DSGVO)</li>
                                <li>Recht auf Datenübertragbarkeit (Art. 20 DSGVO)</li>
                                <li>Recht auf Widerspruch gegen die Verarbeitung (Art. 21 DSGVO)</li>
                            </ul>
                            <p class="mb-4">
                                Zur Ausübung Ihrer Rechte genügt eine formlose E-Mail an
                                <a href="mailto:<?= htmlspecialchars($developerEmail, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($developerEmail, ENT_QUOTES, 'UTF-8'); ?></a>.
                                Darüber hinaus haben Sie das Recht, sich bei einer Datenschutz-Aufsichtsbehörde zu
                                beschweren (Art. 77 DSGVO), z.&nbsp;B. bei der für Ihren Wohnsitz zuständigen
                                Landesbeauftragten für Datenschutz.
                            </p>

                            <h3 class="h4">8. Datensicherheit</h3>
                            <p class="mb-4">
                                Die Übertragung Ihrer Meldung erfolgt ausschließlich verschlüsselt über HTTPS.
                                Der Zugang zu den im Issue-System gespeicherten Meldungen ist auf den Verantwortlichen
                                beschränkt und durch Zugangsschutz gesichert.
                            </p>

                            <h3 class="h4">9. Server-Logdateien</h3>
                            <p class="mb-0">
                                Beim Aufruf dieser Seite werden durch den Webserver technisch bedingt Verbindungsdaten
                                (z.&nbsp;B. IP-Adresse, Datum und Uhrzeit des Aufrufs) in Logdateien verarbeitet. Diese
                                Verarbeitung erfolgt auf Grundlage von Art. 6 Abs. 1 lit. f DSGVO zur Sicherstellung des
                                ordnungsgemäßen Betriebs; die Logdaten werden nicht ausgewertet und nach Ablauf der
                                technisch erforderlichen Aufbewahrungsfrist gelöscht.
                            </p>
                        </div>
                    </div>

                    <!-- ==================== ENGLISH ==================== -->
                    <div class="card" id="privacy-en">
                        <div class="card-body">
                            <h2 class="mb-4">Privacy Policy</h2>
                            <p class="text-secondary mb-4">
                                (English version for your convenience. The German version above is the legally binding text.)
                            </p>

                            <h3 class="h4">1. Controller</h3>
                            <p class="mb-4">
                                The controller responsible for data processing in connection with the feedback and
                                bug reporting feature of this application (<?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8'); ?>,
                                version <?= htmlspecialchars($appVersion, ENT_QUOTES, 'UTF-8'); ?>) is:<br>
                                <strong><?= htmlspecialchars($developerName, ENT_QUOTES, 'UTF-8'); ?></strong><br>
                                E-mail: <a href="mailto:<?= htmlspecialchars($developerEmail, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($developerEmail, ENT_QUOTES, 'UTF-8'); ?></a><br>
                                <a href="<?= htmlspecialchars($developerGithub, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><?= htmlspecialchars($developerGithub, ENT_QUOTES, 'UTF-8'); ?></a>
                            </p>

                            <h3 class="h4">2. General Information</h3>
                            <p class="mb-4">
                                We take the protection of your personal data seriously. This policy explains which data
                                is collected when you use the feedback feature (&ldquo;Report a bug / suggest an
                                improvement&rdquo;), for what purpose it is processed and to whom it is transmitted.
                                The processing is governed by the EU General Data Protection Regulation (GDPR) and the
                                German Federal Data Protection Act (BDSG).
                            </p>

                            <h3 class="h4">3. Feedback Feature (Creation of a Developer Issue)</h3>
                            <p>
                                When you send us a report via the feedback feature, your report is transmitted to the
                                developer and created as an issue in the project management system
                                (OneDev, operated at <?= htmlspecialchars($webhookHost, ENT_QUOTES, 'UTF-8'); ?>).
                                The following data is processed:
                            </p>
                            <div class="table-responsive mb-3">
                                <table class="table table-vcenter">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Required / optional</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Type of report (bug or improvement)</td>
                                            <td>required</td>
                                        </tr>
                                        <tr>
                                            <td>Short summary (title) and detailed description</td>
                                            <td>required</td>
                                        </tr>
                                        <tr>
                                            <td>Name</td>
                                            <td><strong>optional</strong> – used solely for attribution or replies</td>
                                        </tr>
                                        <tr>
                                            <td>E-mail address</td>
                                            <td><strong>optional</strong> – used solely for follow-up questions or replies regarding your report</td>
                                        </tr>
                                        <tr>
                                            <td>Technical metadata: instance URL (referrer), app version, user agent
                                                (browser and operating system), PHP version, server software</td>
                                            <td>collected automatically – required for error analysis</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <p class="mb-4">
                                Transmission takes place encrypted (HTTPS) to the webhook endpoint
                                <code><?= htmlspecialchars($webhookHost, ENT_QUOTES, 'UTF-8'); ?></code>, which creates
                                the issue in the developer's system. Your data is not passed on to any other third
                                parties, not used for advertising purposes and not sold.
                            </p>

                            <h3 class="h4">4. Legal Basis</h3>
                            <p class="mb-4">
                                The processing of your report, including the optional details (name, e-mail address),
                                is based on your consent pursuant to <strong>Art. 6 (1) lit. a GDPR</strong>, which you
                                give by ticking the confirmation checkbox in the feedback form. The processing of
                                technical metadata is additionally based on our legitimate interest in error analysis
                                and software improvement pursuant to <strong>Art. 6 (1) lit. f GDPR</strong>.
                            </p>

                            <h3 class="h4">5. Retention Period</h3>
                            <p class="mb-4">
                                Your report is stored in the developer's issue system until processing is complete and
                                no further questions are expected. Afterwards, the personal details are deleted or the
                                issue is removed, unless statutory retention obligations apply. You may request the
                                deletion of your details at any time (see section 7).
                            </p>

                            <h3 class="h4">6. Withdrawal of Consent</h3>
                            <p class="mb-4">
                                You may withdraw your consent at any time with effect for the future by sending an
                                informal e-mail to
                                <a href="mailto:<?= htmlspecialchars($developerEmail, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($developerEmail, ENT_QUOTES, 'UTF-8'); ?></a>.
                                The lawfulness of the processing carried out until the withdrawal remains unaffected
                                (Art. 7 (3) GDPR).
                            </p>

                            <h3 class="h4">7. Your Rights as a Data Subject</h3>
                            <p>You have the following rights towards the controller:</p>
                            <ul>
                                <li>Right of access to the processed data (Art. 15 GDPR)</li>
                                <li>Right to rectification of inaccurate data (Art. 16 GDPR)</li>
                                <li>Right to erasure (Art. 17 GDPR)</li>
                                <li>Right to restriction of processing (Art. 18 GDPR)</li>
                                <li>Right to data portability (Art. 20 GDPR)</li>
                                <li>Right to object to the processing (Art. 21 GDPR)</li>
                            </ul>
                            <p class="mb-4">
                                To exercise your rights, an informal e-mail to
                                <a href="mailto:<?= htmlspecialchars($developerEmail, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($developerEmail, ENT_QUOTES, 'UTF-8'); ?></a>
                                is sufficient. In addition, you have the right to lodge a complaint with a data
                                protection supervisory authority (Art. 77 GDPR), e.g. the data protection authority
                                responsible for your place of residence.
                            </p>

                            <h3 class="h4">8. Data Security</h3>
                            <p class="mb-4">
                                Your report is transmitted exclusively in encrypted form via HTTPS. Access to the
                                reports stored in the issue system is restricted to the controller and protected by
                                access control.
                            </p>

                            <h3 class="h4">9. Server Log Files</h3>
                            <p class="mb-0">
                                When this page is accessed, the web server technically processes connection data
                                (e.g. IP address, date and time of the request) in log files. This processing is based
                                on Art. 6 (1) lit. f GDPR to ensure proper operation; the log data is not evaluated and
                                is deleted after the technically required retention period.
                            </p>
                        </div>
                    </div>

                    <p class="text-secondary mt-3 mb-0">
                        <small>Stand / Last updated: <?= date('d.m.Y'); ?> &middot; <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8'); ?> <?= htmlspecialchars($appVersion, ENT_QUOTES, 'UTF-8'); ?></small>
                    </p>
                </div>
            </div>
        </div>
    </div>

</body>
<script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>

</html>