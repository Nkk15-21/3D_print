<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Новый заказ товара</title>
</head>
<body style="margin:0; padding:0; background:#f3f6fb; font-family:Arial, sans-serif; color:#1f2937;">
<div style="width:100%; background:#f3f6fb; padding:36px 12px;">
    <div style="max-width:720px; margin:0 auto;">

        <div style="text-align:center; margin-bottom:18px;">
            <div style="display:inline-block; padding:10px 18px; border-radius:999px; background:#e8f0ff; color:#1d4ed8; font-size:13px; font-weight:800; letter-spacing:0.02em;">
                3D PRINT SHOP
            </div>
        </div>

        <div style="background:#ffffff; border-radius:28px; overflow:hidden; box-shadow:0 20px 50px rgba(15,23,42,0.10); border:1px solid #e6ebf2;">

            <div style="padding:34px 34px 28px; background:
                    radial-gradient(circle at top right, rgba(255,255,255,0.22), transparent 180px),
                    linear-gradient(135deg, #2563eb, #1d4ed8 58%, #1e40af); color:#ffffff;">
                <div style="font-size:14px; font-weight:700; opacity:0.92; margin-bottom:12px;">
                    Уведомление о новом заказе
                </div>

                <h1 style="margin:0; font-size:32px; line-height:1.15; font-weight:800;">
                    Новый заказ товара
                </h1>

                <div style="margin-top:18px;">
                        <span style="display:inline-block; padding:9px 16px; border-radius:999px; background:rgba(255,255,255,0.16); font-size:14px; font-weight:800;">
                            Заказ #<?= htmlspecialchars((string)$orderId) ?>
                        </span>
                </div>
            </div>

            <div style="padding:32px 34px 34px;">

                <div style="margin-bottom:28px;">
                    <h2 style="margin:0 0 14px; font-size:21px; color:#111827;">
                        Данные клиента
                    </h2>

                    <div style="background:#f8fafc; border:1px solid #e6ebf2; border-radius:20px; padding:18px 20px;">
                        <table style="width:100%; border-collapse:collapse;">
                            <tr>
                                <td style="padding:10px 0; width:190px; color:#64748b; font-weight:700;">Имя клиента</td>
                                <td style="padding:10px 0; color:#111827; font-weight:700;"><?= htmlspecialchars((string)$customerName) ?></td>
                            </tr>
                            <tr>
                                <td style="padding:10px 0; width:190px; color:#64748b; font-weight:700;">Email клиента</td>
                                <td style="padding:10px 0; color:#111827; font-weight:700;">
                                    <a href="mailto:<?= htmlspecialchars((string)$customerEmail) ?>" style="color:#2563eb; text-decoration:none;">
                                        <?= htmlspecialchars((string)$customerEmail) ?>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:10px 0; width:190px; color:#64748b; font-weight:700;">Телефон</td>
                                <td style="padding:10px 0; color:#111827; font-weight:700;"><?= htmlspecialchars((string)($customerPhone ?: '—')) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div style="margin-bottom:28px;">
                    <h2 style="margin:0 0 14px; font-size:21px; color:#111827;">
                        Детали заказа
                    </h2>

                    <div style="background:linear-gradient(135deg, #ffffff, #f8fbff); border:1px solid #dbeafe; border-radius:20px; padding:20px 22px;">
                        <table style="width:100%; border-collapse:collapse;">
                            <tr>
                                <td style="padding:11px 0; width:190px; color:#64748b; font-weight:700;">Товар</td>
                                <td style="padding:11px 0; color:#111827; font-weight:800; font-size:18px;"><?= htmlspecialchars((string)$productName) ?></td>
                            </tr>
                            <tr>
                                <td style="padding:11px 0; color:#64748b; font-weight:700;">Количество</td>
                                <td style="padding:11px 0; color:#111827; font-weight:700;"><?= htmlspecialchars((string)$quantity) ?></td>
                            </tr>
                            <tr>
                                <td style="padding:11px 0; color:#64748b; font-weight:700;">Цена за единицу</td>
                                <td style="padding:11px 0; color:#111827; font-weight:700;">€<?= number_format((float)$unitPrice, 2) ?></td>
                            </tr>
                        </table>

                        <div style="margin-top:18px; padding-top:18px; border-top:1px solid #dbeafe;">
                            <div style="font-size:14px; color:#64748b; font-weight:700; margin-bottom:6px;">
                                Общая сумма
                            </div>
                            <div style="font-size:34px; line-height:1; color:#2563eb; font-weight:900; letter-spacing:-0.03em;">
                                €<?= number_format((float)$totalAmount, 2) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="margin-bottom:28px;">
                    <div style="padding:18px 20px; border-radius:18px; background:#fff7ed; border:1px solid #fed7aa; color:#9a3412;">
                        <div style="font-weight:800; margin-bottom:6px;">Что дальше</div>
                        <div style="line-height:1.6;">
                            Проверь заказ в админке, уточни детали при необходимости и переведи заявку в следующий статус.
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