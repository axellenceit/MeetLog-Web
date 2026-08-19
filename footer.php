<?php
$feedbackStatus = $_GET['feedback_issue'] ?? '';
$feedbackAlerts = [
    'success' => ['type' => 'success', 'message' => 'Vielen Dank. Dein Hinweis wurde als den Entwickler-Issue erstellt.'],
    'validation' => ['type' => 'warning', 'message' => 'Bitte pruefe deine Eingaben im Feedback-Formular.'],
    'privacy' => ['type' => 'warning', 'message' => 'Bitte bestaetige die Datenschutz-Einwilligung, damit wir die Meldung verarbeiten duerfen.'],
    'config' => ['type' => 'danger', 'message' => 'Feedback-Service ist aktuell nicht konfiguriert. Bitte Admin informieren.'],
    'error' => ['type' => 'danger', 'message' => 'Die Meldung konnte nicht an den Entwickler uebertragen werden. Bitte spaeter erneut versuchen.'],
];
?>

<footer class="footer footer-transparent d-print-none mt-auto">
    <div class="container-xl">
        <?php if (isset($feedbackAlerts[$feedbackStatus])): ?>
            <div class="alert alert-<?= htmlspecialchars($feedbackAlerts[$feedbackStatus]['type'], ENT_QUOTES, 'UTF-8'); ?>" role="alert">
                <?= htmlspecialchars($feedbackAlerts[$feedbackStatus]['message'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <div class="row text-center align-items-center flex-row-reverse">
            <div class="col-lg-auto ms-lg-auto">
                <ul class="list-inline list-inline-dots mb-0">
                    <li class="list-inline-item">
                        <a href="./impressum.php" class="link-secondary">Impressum</a>
                    </li>
                    <li class="list-inline-item">
                        <a href="./datenschutz.php" class="link-secondary">Datenschutz</a>
                    </li>
                    <li class="list-inline-item">
                        <a class="link-secondary p-0" data-bs-toggle="modal" data-bs-target="#reportFeedbackModal">
                            Fehler melden / Verbesserung vorschlagen
                        </a>
                    </li>
                </ul>
            </div>
            <div class="col-12 col-lg-auto mt-3 mt-lg-0">
                <ul class="list-inline list-inline-dots mb-0">
                    <li class="list-inline-item">
                        &copy; <?= date('Y'); ?> MeetLog
                    </li>
                    <li class="list-inline-item">
                        Version <?= htmlspecialchars('0.0.35', ENT_QUOTES, 'UTF-8'); ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</footer>

<div class="modal modal-blur fade" id="reportFeedbackModal" tabindex="-1" aria-labelledby="reportFeedbackModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="./actions.php">
                <input type="hidden" name="action" value="createFeedbackIssue">

                <div class="modal-header">
                    <h5 class="modal-title" id="reportFeedbackModalLabel">Feedback an MeetLog senden</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schliessen"></button>
                </div>

                <div class="modal-body">
                    <p class="text-secondary mb-3">
                        Melde hier einen Fehler oder sende einen Verbesserungsvorschlag. Die Meldung wird als Issue für das Projekt erstellt.
                    </p>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="feedbackType" class="form-label">Art der Meldung</label>
                            <select id="feedbackType" name="feedbackType" class="form-select" required>
                                <option value="bug">Fehler</option>
                                <option value="improvement">Verbesserung</option>
                            </select>
                        </div>

                        <div class="col-md-8">
                            <label for="feedbackTitle" class="form-label">Kurzbeschreibung</label>
                            <input id="feedbackTitle" name="feedbackTitle" type="text" class="form-control" maxlength="160" required placeholder="z. B. Export bricht bei grosser Sitzung ab">
                        </div>

                        <div class="col-12">
                            <label for="feedbackDescription" class="form-label">Details</label>
                            <textarea id="feedbackDescription" name="feedbackDescription" class="form-control" rows="6" maxlength="5000" required placeholder="Bitte möglichst genau beschreiben: Was hast du gemacht? Was ist passiert? Was hättest du erwartet?"></textarea>
                        </div>

                        <div class="col-md-6">
                            <label for="feedbackName" class="form-label">Name (optional)</label>
                            <input id="feedbackName" name="feedbackName" type="text" class="form-control" maxlength="120" autocomplete="name" placeholder="Nur wenn du genannt werden moechtest">
                        </div>

                        <div class="col-md-6">
                            <label for="feedbackEmail" class="form-label">E-Mail (optional)</label>
                            <input id="feedbackEmail" name="feedbackEmail" type="email" class="form-control" maxlength="190" autocomplete="email" placeholder="Nur fuer Rueckfragen">
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="feedbackPrivacyConsent" name="feedbackPrivacyConsent" required>
                                <label class="form-check-label" for="feedbackPrivacyConsent">
                                    Ich willige ein, dass meine Angaben zur Bearbeitung meiner Meldung verarbeitet und an den Entwickler übermittelt werden.
                                    Name und E-Mail sind freiwillig und werden nur für Rückfragen genutzt.
                                    Weitere Informationen: <a href="./datenschutz.php" target="_blank" rel="noopener">Datenschutzerklärung</a>.
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">An den Entwickler senden</button>
                </div>
            </form>
        </div>
    </div>
</div>