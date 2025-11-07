<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Recupera tu contraseña</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        .preheader {
            display: none !important;
            visibility: hidden;
            opacity: 0;
            color: transparent;
            height: 0;
            width: 0;
            overflow: hidden;
        }

        @media only screen and (max-width: 620px) {
            .container {
                width: 100% !important;
            }

            .card {
                border-radius: 0 !important;
            }

            .p-24 {
                padding: 16px !important;
            }

            .btn {
                display: block !important;
                width: 100% !important;
            }
        }
    </style>
</head>

<body style="margin:0; padding:0; background:#f6f7fb; font-family: Arial, Helvetica, sans-serif;">

    <div class="preheader">Usa este enlace para restablecer tu contraseña. Enlace válido por 1 hora.</div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f7fb;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" class="container" width="600" cellpadding="0" cellspacing="0" style="width:600px; max-width:600px; background:#ffffff; border-radius:12px; overflow:hidden;">
                    <tr>
                        <td style="background:#0d6efd; color:#ffffff; padding:18px 24px; font-size:18px; font-weight:bold;">
                            Directorio Telefónico
                        </td>
                    </tr>
                    <tr>
                        <td class="p-24" style="padding:24px; color:#222; font-size:16px; line-height:1.5;">
                            <p style="margin:0 0 12px;">Hola <strong><?= htmlspecialchars($nombre ?? 'usuario') ?></strong>,</p>
                            <p style="margin:0 0 16px;">Recibimos una solicitud para restablecer tu contraseña. Haz clic en el botón para continuar.</p>

                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="<?= htmlspecialchars($resetUrl) ?>"
                                            class="btn"
                                            style="background:#0d6efd; color:#ffffff; text-decoration:none;
                            padding:12px 24px; border-radius:8px; font-weight:bold; display:inline-block;">
                                            Restablecer contraseña
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 8px;">Si el botón no funciona, copia y pega este enlace en tu navegador:</p>
                            <p style="margin:0; word-break:break-all;">
                                <a href="<?= htmlspecialchars($resetUrl) ?>" style="color:#0d6efd; text-decoration:none;">
                                    <?= htmlspecialchars($resetUrl) ?>
                                </a>
                            </p>

                            <p style="margin:16px 0 0; color:#555; font-size:14px;">
                                Este enlace caduca en <strong>10 minutos</strong>. Si tú no solicitaste este cambio, puedes ignorar este mensaje.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f1f3f9; color:#6b7280; padding:12px 24px; font-size:12px; text-align:center;">
                            © <?= date('Y') ?> Directorio Telefónico • Correo automático, no respondas a este mensaje.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>