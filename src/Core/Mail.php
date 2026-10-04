<?php

declare(strict_types=1);

namespace Src\Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Src\Organization\OrganizationRepository;
use Src\UI\Translator;

require __DIR__ . '/../../vendor/phpmailer/phpmailer/src/Exception.php';
require __DIR__ . '/../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require __DIR__ . '/../../vendor/phpmailer/phpmailer/src/SMTP.php';

final class Mail
{

    private Config $config;
    private Translator $translator;
    private OrganizationRepository $organizationRepository;

    public function __construct(?Config $config = null, ?Translator $translator = null, ?OrganizationRepository $organizationRepository = null)
    {
        if ($config === null) {
            $config = new Config(dirname(__DIR__, 2) . '/meetlog.ini');
        }

        $this->config = $config;
        $this->translator = $translator;
        $this->organizationRepository = $organizationRepository;
    }

    /**
     * @param array<int, array{name: string, content: string}> $attachments
     */
    public function send(string $to, string $subject, string $message, array $attachments = []): bool
    {
        $mail = new PHPMailer(true);

        try {
            $username = trim((string)$this->config->get('mail', 'user', ''));
            $security = strtolower(trim((string)$this->config->get('mail', 'usessl', 'tls')));

            $mail->isSMTP();
            $mail->Host       = $this->config->get('mail', 'server', 'smtp.example.com');
            $mail->SMTPAuth   = $username !== '';
            $mail->Username   = $username;
            $mail->Password   = $this->config->get('mail', 'pass', '');
            $mail->Port       = (int)$this->config->get('mail', 'port', 587);

            if ($security === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($security === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure = false;
            }

            $mail->setFrom($this->config->get('mail', 'from', $username), 'MeetLog');
            $mail->addAddress($to);

            if ($this->config->get('mail', 'toggleEveryCC', 'off') === 'on') {
                $mail->addCC($this->config->get('mail', 'from', $username));
            }

            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body    = $message;
            $mail->CharSet = 'UTF-8';

            foreach ($attachments as $attachment) {
                $mail->addStringAttachment($attachment['content'], $attachment['name'], 'base64', 'application/pdf');
            }

            return $mail->send();
        } catch (Exception $e) {
            error_log('Mail konnte nicht gesendet werden: ' . ($mail->ErrorInfo !== '' ? $mail->ErrorInfo : $e->getMessage()));
            return false;
        }
    }

    public function sendWelcomeEmail(string $to, string $name, string $username, string $password, int $orgId = 1): bool
    {
        $subject = 'Willkommen bei ' . htmlspecialchars($this->organizationRepository->findById($orgId)->name1);
        $message = $this->translator->t('mail.welcome.body_html');
        $message = str_replace('{{organization_name}}', htmlspecialchars($this->organizationRepository->findById($orgId)->name1), $message);
        $message = str_replace('{{name}}', htmlspecialchars($name), $message);
        $message = str_replace('{{username}}', htmlspecialchars($username), $message);
        $message = str_replace('{{password}}', htmlspecialchars($password), $message);

        $message .= $this->imprint($orgId);

        return $this->send($to, $subject, $message);
    }

    public function sendDeletionEmail(string $to, int $orgId = 1): bool
    {
        $subject = 'Benutzerkonto gelöscht bei ' . htmlspecialchars($this->organizationRepository->findById($orgId)->name1);
        $message = $this->translator->t('mail.deletion.body_html');
        $message = str_replace('{{organization_name}}', htmlspecialchars($this->organizationRepository->findById($orgId)->name1), $message);

        $message .= $this->imprint($orgId);

        return $this->send($to, $subject, $message);
    }

    public function imprint(int $orgId): string
    {

        $org = $this->organizationRepository->findById($orgId);

        return "<h4>{$this->translator->t('common.imprint')}</h4>
                <p>{$org->name1}<br/>{$org->name2}</p>
                <p>{$org->street} <br/>{$org->zipCode} {$org->city}</p>
                <p>{$this->translator->t('common.contact.phone')}: {$org->phone}<br/>
                {$this->translator->t('common.contact.mail')}: <a href=\"mailto:{$org->email}\">{$org->email}</a><br/>
                {$this->translator->t('common.contact.web')}: <a href=\"{$org->homepage}\" target=\"_blank\">{$org->homepage}</a></p>
                <p>MeetLog-URL: <a href=\"{$this->config->get('general', 'url', 'http://localhost/meetlog')}\" target=\"_blank\">{$this->config->get('general', 'url', 'http://localhost/meetlog')}</a></p>
                <p>{$this->translator->t('common.localcourt')}: {$org->registryCourt} - {$org->registryEntry}</p>";
    }
}
