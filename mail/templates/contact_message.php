<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Новое сообщение с сайта</title>
</head>
<body style="margin:0; padding:0; background:#f3f6fb; font-family:Arial, sans-serif; color:#1f2937;">
<div style="width:100%; background:#f3f6fb; padding:36px 12px;">
    <div style="max-width:760px; margin:0 auto;">

        <div style="text-align:center; margin-bottom:18px;">
            <div style="display:inline-block; padding:10px 18px; border-radius:999px; background:#ede9fe; color:#6d28d9; font-size:13px; font-weight:800; letter-spacing:0.02em;">
                3D PRINT SHOP
            </div>
        </div>

        <div style="background:#ffffff; border-radius:28px; overflow:hidden; box-shadow:0 20px 50px rgba(15,23,42,0.10); border:1px solid #e6ebf2;">

            <div style="padding:34px 34px 28px; background:
                    radial-gradient(circle at top right, rgba(255,255,255,0.18), transparent 180px),
                    linear-gradient(135deg, #7c3aed, #2563eb); color:#ffffff;">
                <div style="font-size:14px; font-weight:700; opacity:0.92; margin-bottom:12px;">
                    Новое входящее сообщение
                </div>

                <h1 style="margin:0; font-size:32px; line-height:1.15; font-weight:800;">
                    Новое сообщение с сайта
                </h1>
            </div>

            <div style="padding:32px 34px 34px;">

                <div style="margin-bottom:28px;">
                    <h2 style="margin:0 0 14px; font-size:21px; color:#111827;">Отправитель</h2>

                    <div style="background:#f8fafc; border:1px solid #e6ebf2; border-radius:20px; padding:18px 20px;">
                        <table style="width:100%; border-collapse:collapse;">
                            <tr>
                                <td style="padding:10px 0; width:180px; color:#64748b; font-weight:700;">Имя</td>
                                <td style="padding:10px 0; color:#111827; font-weight:700;"><?= htmlspecialchars((string)$name) ?></td>
                            </tr>
                            <tr>
                                <td style="padding:10px 0; width:180px; color:#64748b; font-weight:700;">Email</td>
                                <td style="padding:10px 0; color:#111827; font-weight:700;">
                                    <a href="mailto:<?= htmlspecialchars((string)$email) ?>" style="color:#2563eb; text-decoration:none;">
                                        <?= htmlspecialchars((string)$email) ?>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:10px 0; width:180px; color:#64748b; font-weight:700;">Тема</td>
                                <td style="padding:10px 0; color:#111827; font-weight:700;"><?= htmlspecialchars((string)($subject ?: 'Без темы')) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div style="margin-bottom:28px;">
                    <h2 style="margin:0 0 14px; font-size:21px; color:#111827;">Текст сообщения</h2>

                    <div style="background:linear-gradient(135deg, #ffffff, #faf7ff); border:1px solid #ddd6fe; border-radius:20px; padding:22px; color:#312e81; line-height:1.8; font-size:15px;">
                        <?= nl2br(htmlspecialchars((string)$message)) ?>
                    </div>
                </div>

                <div style="margin-bottom:28px;">
                    <div style="padding:18px 20px; border-radius:18px; background:#eff6ff; border:1px solid #bfdbfe; color:#1e40af;">
                        <div style="font-weight:800; margin-bottom:6px;">Что можно сделать дальше</div>
                        <div style="line-height:1.6;">
                            При необходимости ответь клиенту напрямую на его email или обработай запрос через админку.
                        </div>
                    </div>
                </div>

                <div style="padding-top:18px; border-top:1px solid #e5e7eb; color:#6b7280; font-size:14px;">
                    <strong style="color:#374151;">Дата:</strong>
                    <?= htmlspecialchars((string)$createdAt) ?>
                </div>
            </div>
        </div>

        <div style="text-align:center; color:#94a3b8; font-size:13px; margin-top:14px;">
            Это автоматическое письмо от 3D Print Shop
        </div>
    </div>
</div>
</body>
</html>