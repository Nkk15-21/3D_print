<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Новый индивидуальный заказ</title>
</head>
<body style="margin:0; padding:0; background:#f3f6fb; font-family:Arial, sans-serif; color:#1f2937;">
<div style="width:100%; background:#f3f6fb; padding:36px 12px;">
    <div style="max-width:760px; margin:0 auto;">

        <div style="text-align:center; margin-bottom:18px;">
            <div style="display:inline-block; padding:10px 18px; border-radius:999px; background:#dcfce7; color:#166534; font-size:13px; font-weight:800; letter-spacing:0.02em;">
                3D PRINT SHOP
            </div>
        </div>

        <div style="background:#ffffff; border-radius:28px; overflow:hidden; box-shadow:0 20px 50px rgba(15,23,42,0.10); border:1px solid #e6ebf2;">

            <div style="padding:34px 34px 28px; background:
                    radial-gradient(circle at top right, rgba(255,255,255,0.18), transparent 180px),
                    linear-gradient(135deg, #0f766e, #2563eb); color:#ffffff;">
                <div style="font-size:14px; font-weight:700; opacity:0.92; margin-bottom:12px;">
                    Уведомление о новой заявке
                </div>

                <h1 style="margin:0; font-size:32px; line-height:1.15; font-weight:800;">
                    Новый индивидуальный заказ
                </h1>

                <div style="margin-top:18px;">
                        <span style="display:inline-block; padding:9px 16px; border-radius:999px; background:rgba(255,255,255,0.16); font-size:14px; font-weight:800;">
                            Заказ #<?= htmlspecialchars((string)$orderId) ?>
                        </span>
                </div>
            </div>

            <div style="padding:32px 34px 34px;">

                <div style="margin-bottom:28px;">
                    <h2 style="margin:0 0 14px; font-size:21px; color:#111827;">Данные клиента</h2>

                    <div style="background:#f8fafc; border:1px solid #e6ebf2; border-radius:20px; padding:18px 20px;">
                        <table style="width:100%; border-collapse:collapse;">
                            <tr>
                                <td style="padding:10px 0; width:200px; color:#64748b; font-weight:700;">Имя клиента</td>
                                <td style="padding:10px 0; color:#111827; font-weight:700;"><?= htmlspecialchars((string)$customerName) ?></td>
                            </tr>
                            <tr>
                                <td style="padding:10px 0; width:200px; color:#64748b; font-weight:700;">Email клиента</td>
                                <td style="padding:10px 0; color:#111827; font-weight:700;">
                                    <a href="mailto:<?= htmlspecialchars((string)$customerEmail) ?>" style="color:#2563eb; text-decoration:none;">
                                        <?= htmlspecialchars((string)$customerEmail) ?>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:10px 0; width:200px; color:#64748b; font-weight:700;">Телефон</td>
                                <td style="padding:10px 0; color:#111827; font-weight:700;"><?= htmlspecialchars((string)($customerPhone ?: '—')) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div style="margin-bottom:28px;">
                    <h2 style="margin:0 0 14px; font-size:21px; color:#111827;">Параметры печати</h2>

                    <div style="background:linear-gradient(135deg, #ffffff, #f8fbff); border:1px solid #dbeafe; border-radius:20px; padding:20px 22px;">
                        <table style="width:100%; border-collapse:collapse;">
                            <tr>
                                <td style="padding:11px 0; width:210px; color:#64748b; font-weight:700;">Материал</td>
                                <td style="padding:11px 0; color:#111827; font-weight:800;"><?= htmlspecialchars((string)$material) ?></td>
                            </tr>
                            <tr>
                                <td style="padding:11px 0; color:#64748b; font-weight:700;">Цвет</td>
                                <td style="padding:11px 0; color:#111827; font-weight:700;"><?= htmlspecialchars((string)($color ?: '—')) ?></td>
                            </tr>
                            <tr>
                                <td style="padding:11px 0; color:#64748b; font-weight:700;">Высота слоя</td>
                                <td style="padding:11px 0; color:#111827; font-weight:700;"><?= htmlspecialchars((string)$layerHeight) ?> мм</td>
                            </tr>
                            <tr>
                                <td style="padding:11px 0; color:#64748b; font-weight:700;">Заполнение</td>
                                <td style="padding:11px 0; color:#111827; font-weight:700;"><?= htmlspecialchars((string)$infill) ?>%</td>
                            </tr>
                            <tr>
                                <td style="padding:11px 0; color:#64748b; font-weight:700;">Вес</td>
                                <td style="padding:11px 0; color:#111827; font-weight:700;"><?= htmlspecialchars((string)$weight) ?> г</td>
                            </tr>
                            <tr>
                                <td style="padding:11px 0; color:#64748b; font-weight:700;">Файл модели</td>
                                <td style="padding:11px 0; color:#111827; font-weight:700;"><?= htmlspecialchars((string)$modelFile) ?></td>
                            </tr>
                        </table>

                        <div style="margin-top:18px; padding-top:18px; border-top:1px solid #dbeafe;">
                            <div style="font-size:14px; color:#64748b; font-weight:700; margin-bottom:6px;">
                                Ориентировочная цена
                            </div>
                            <div style="font-size:34px; line-height:1; color:#2563eb; font-weight:900; letter-spacing:-0.03em;">
                                €<?= number_format((float)$estimatedPrice, 2) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($comment)): ?>
                    <div style="margin-bottom:28px;">
                        <h2 style="margin:0 0 14px; font-size:21px; color:#111827;">Комментарий клиента</h2>
                        <div style="background:#fff7ed; border:1px solid #fed7aa; border-radius:20px; padding:18px 20px; color:#9a3412; line-height:1.7;">
                            <?= nl2br(htmlspecialchars((string)$comment)) ?>
                        </div>
                    </div>
                <?php endif; ?>

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