<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt <?= esc((string) ($receipt['receipt_number'] ?? '')) ?></title>
    <style>
        :root {
            --paper-width: 80mm;
            --text: #111;
            --muted: #555;
            --line: #b7b7b7;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Consolas", "Courier New", monospace;
            background: #ececec;
            color: var(--text);
            font-size: 12px;
            line-height: 1.35;
        }

        .receipt-page {
            width: var(--paper-width);
            margin: 16px auto;
            background: #fff;
            border: 1px solid #dadada;
            padding: 10px 10px 12px;
        }

        .center {
            text-align: center;
        }

        .store-name {
            font-weight: 700;
            font-size: 15px;
            letter-spacing: 0.5px;
            margin: 2px 0 1px;
        }

        .muted {
            color: var(--muted);
            font-size: 11px;
        }

        .sep {
            border-top: 1px dashed var(--line);
            margin: 6px 0;
        }

        .meta-row,
        .sum-row,
        .item-row {
            display: flex;
            justify-content: space-between;
            gap: 8px;
        }

        .meta-row span:first-child,
        .sum-row span:first-child {
            color: var(--muted);
        }

        .item {
            padding: 4px 0;
            border-bottom: 1px dotted #d8d8d8;
        }

        .item:last-child {
            border-bottom: 0;
        }

        .item-name {
            font-weight: 600;
            margin-bottom: 2px;
        }

        .sum-row.total {
            font-weight: 700;
            font-size: 13px;
        }

        .print-actions {
            width: var(--paper-width);
            margin: 10px auto 18px;
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        .btn {
            border: 1px solid #bbb;
            background: #fff;
            padding: 6px 10px;
            font-size: 12px;
            cursor: pointer;
        }

        .btn.primary {
            background: #111;
            color: #fff;
            border-color: #111;
        }

        .btn-link {
            text-decoration: none;
            color: inherit;
        }

        @media print {
            body {
                background: #fff;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print {
                display: none !important;
            }

            .receipt-page {
                border: none;
                margin: 0;
                width: var(--paper-width);
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-page">
        <div class="center">
            <div class="store-name">SHOPPING CENTER</div>
            <div class="muted">Sales Receipt</div>
        </div>

        <div class="sep"></div>

        <div class="meta-row"><span>Receipt No</span><span><?= esc((string) ($receipt['receipt_number'] ?? 'N/A')) ?></span></div>
        <div class="meta-row"><span>Date Printed</span><span><?= esc(date('Y-m-d H:i:s')) ?></span></div>

        <div class="sep"></div>

        <?php if (empty($lines)): ?>
            <div class="center muted" style="padding: 10px 0;">No receipt line items found.</div>
        <?php else: ?>
            <?php foreach ($lines as $line): ?>
                <div class="item">
                    <div class="item-name"><?= esc((string) ($line['product_name'] ?? 'N/A')) ?></div>
                    <div class="item-row">
                        <span class="muted">
                            <?= esc((string) ((int) ($line['quantity'] ?? 0))) ?> x <?= esc(number_format((float) ($line['sales_price'] ?? 0), 2)) ?>
                        </span>
                        <span><?= esc(number_format((float) ($line['line_total'] ?? 0), 2)) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="sep"></div>

        <div class="sum-row"><span>Subtotal</span><span><?= esc(number_format((float) ($computedTotal ?? 0), 2)) ?></span></div>
        <div class="sum-row total"><span>Total</span><span><?= esc(number_format((float) ($receiptTotal ?? 0), 2)) ?></span></div>

        <div class="sep"></div>
        <div class="center muted">Thank you for your purchase.</div>
    </div>

    <div class="print-actions no-print">
        <a href="<?= base_url('stock-out/cashier') ?>" class="btn btn-link">Back to Cashier</a>
        <button type="button" class="btn primary" onclick="window.print()">Print Receipt</button>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="center no-print" style="margin-bottom: 10px; color: #0f5132;">
            <?= esc(session()->getFlashdata('success')) ?>
        </div>
    <?php endif; ?>
</body>
</html>
