<?php
/**
 * BRASALLIS HUB — Envio de Link de Redefinição de Senha
 * Gera token seguro + expiração e envia email com PHPMailer.
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/includes/funcoes.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /esqueceu_senha.php');
    exit();
}

$email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: /esqueceu_senha.php?error=E-mail+inválido.');
    exit();
}

$conn = connect_db();

// Sempre retorna a mesma mensagem (evita user enumeration)
$redirect_success = '/esqueceu_senha.php?success=Se+este+e-mail+estiver+cadastrado,+você+receberá+o+link+em+breve.';

try {
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = :email LIMIT 1");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Não revela se o email existe (anti-enumeration)
        header("Location: $redirect_success");
        exit();
    }

    // Gerar token seguro (hex 32 = 64 chars) com expiração de 1 hora
    $token      = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Salvar token no banco (upsert por email)
    $stmt = $conn->prepare("
        INSERT INTO redefinicoes_senha (email, token, expires_at)
        VALUES (:email, :token, :expires_at)
        ON DUPLICATE KEY UPDATE token = :token, expires_at = :expires_at
    ");
    $stmt->execute([':email' => $email, ':token' => $token, ':expires_at' => $expires_at]);

    // Montar link de redefinição
    $app_url    = $_ENV['APP_URL'] ?? getenv('APP_URL') ?? 'https://seusite.com';
    $reset_link = $app_url . '/redefinir_senha.php?token=' . $token;

    // ── Envio do E-mail ──────────────────────────────────────────
    $enviado = enviar_email_redefinicao($email, $reset_link);

    if (!$enviado) {
        // Em dev, loga o link para facilitar testes
        error_log("[DEV] Link de redefinição para $email: $reset_link");
    }

} catch (Exception $e) {
    registrar_erro_sistema("Erro no envio de link de redefinição: " . $e->getMessage(), 'error', 'AuthController', $e->getTraceAsString());
    error_log("Erro envio reset senha: " . $e->getMessage());
}

// Sempre redireciona para a mesma mensagem (anti-enumeration)
header("Location: $redirect_success");
exit();


/**
 * Envia o email de redefinição de senha.
 * Usa PHPMailer se disponível, caso contrário tenta mail() nativo.
 */
function enviar_email_redefinicao(string $email, string $reset_link): bool
{
    $mailer_class = '\\PHPMailer\\PHPMailer\\PHPMailer';

    if (class_exists($mailer_class)) {
        return enviar_via_phpmailer($email, $reset_link);
    }

    // Fallback: mail() nativo (funciona em produção com sendmail configurado)
    return enviar_via_mail_nativo($email, $reset_link);
}

function enviar_via_phpmailer(string $email, string $reset_link): bool
{
    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        // Configuração SMTP (via .env)
        $mail->isSMTP();
        $mail->Host       = $_ENV['MAIL_HOST']       ?? getenv('MAIL_HOST')       ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME']   ?? getenv('MAIL_USERNAME')   ?? '';
        $mail->Password   = $_ENV['MAIL_PASSWORD']   ?? getenv('MAIL_PASSWORD')   ?? '';
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int) ($_ENV['MAIL_PORT'] ?? getenv('MAIL_PORT') ?? 587);
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(
            $_ENV['MAIL_FROM_ADDRESS'] ?? getenv('MAIL_FROM_ADDRESS') ?? 'noreply@brasallis.pro',
            $_ENV['MAIL_FROM_NAME']    ?? getenv('MAIL_FROM_NAME')    ?? 'Brasallis Hub'
        );
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Redefinição de Senha — Brasallis Hub';
        $mail->Body    = template_email_reset($reset_link);
        $mail->AltBody = "Acesse o link para redefinir sua senha (válido por 1 hora):\n$reset_link";

        $mail->send();
        return true;
    } catch (\Exception $e) {
        error_log("PHPMailer Error: " . $e->getMessage());
        return false;
    }
}

function enviar_via_mail_nativo(string $email, string $reset_link): bool
{
    $subject = '=?UTF-8?B?' . base64_encode('Redefinição de Senha — Brasallis Hub') . '?=';
    $headers = implode("\r\n", [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: Brasallis Hub <noreply@brasallis.pro>',
        'X-Mailer: PHP/' . PHP_VERSION,
    ]);
    $body = template_email_reset($reset_link);
    return mail($email, $subject, $body, $headers);
}

function template_email_reset(string $link): string
{
    return <<<HTML
    <!DOCTYPE html>
    <html lang="pt-br">
    <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
    <body style="margin:0;padding:0;background:#f4f6f9;font-family:'Helvetica Neue',Arial,sans-serif;">
        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:40px 0;">
            <tr><td align="center">
                <table width="520" cellpadding="0" cellspacing="0" style="background:#001E3C;border-radius:16px;overflow:hidden;">
                    <!-- Header -->
                    <tr><td style="background:#001E3C;padding:36px 40px 24px;text-align:center;">
                        <p style="color:#0070F2;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:2px;margin:0 0 12px;">Brasallis Enterprise Hub</p>
                        <h1 style="color:#ffffff;font-size:24px;font-weight:700;margin:0;">Redefinição de Senha</h1>
                    </td></tr>
                    <!-- Body -->
                    <tr><td style="background:#0A2647;padding:32px 40px;">
                        <p style="color:rgba(255,255,255,0.7);font-size:15px;line-height:1.6;margin:0 0 24px;">
                            Recebemos uma solicitação para redefinir a senha da sua conta. Clique no botão abaixo para criar uma nova senha.
                        </p>
                        <p style="color:rgba(255,255,255,0.5);font-size:13px;margin:0 0 28px;">
                            Este link é válido por <strong style="color:#fff;">1 hora</strong>. Se você não solicitou a redefinição, ignore este e-mail.
                        </p>
                        <div style="text-align:center;">
                            <a href="{$link}" style="display:inline-block;background:#0070F2;color:#fff;font-weight:700;font-size:14px;text-decoration:none;padding:14px 36px;border-radius:10px;letter-spacing:0.5px;">
                                🔐 Redefinir Minha Senha
                            </a>
                        </div>
                        <p style="color:rgba(255,255,255,0.3);font-size:11px;margin:24px 0 0;word-break:break-all;">
                            Se o botão não funcionar, copie e cole este link no navegador:<br>
                            <span style="color:#0070F2;">{$link}</span>
                        </p>
                    </td></tr>
                    <!-- Footer -->
                    <tr><td style="background:#001E3C;padding:20px 40px;text-align:center;border-top:1px solid rgba(255,255,255,0.06);">
                        <p style="color:rgba(255,255,255,0.2);font-size:11px;margin:0;">&copy; Brasallis Enterprise Hub — Sistema de Gestão Corporativa</p>
                    </td></tr>
                </table>
            </td></tr>
        </table>
    </body>
    </html>
    HTML;
}
