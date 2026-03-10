<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daily Ledger Summary</title>
</head>
<body style="margin:0; padding:0; background:#f4f6f8; font-family:Arial, Helvetica, sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8; padding:24px 0;">
    <tr>
        <td align="center">
            <table width="650" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:10px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.08);">

                <!-- Header -->
                <tr>
                    <td style="background:#0f766e; padding:18px 24px; color:#ffffff;">
                        <h2 style="margin:0; font-size:20px; font-weight:600;">
                            Daily Ledger Summary
                        </h2>
                        <p style="margin:4px 0 0; font-size:13px; opacity:0.9;">
                            {{ $date }}
                        </p>
                    </td>
                </tr>

                <!-- Body -->
                <tr>
                    <td style="padding:24px;">

                        <p style="margin:0 0 18px; font-size:14px; color:#374151;">
                            Below is the complete breakdown of today’s ledger entries.
                        </p>

                        <!-- ================= EXPENSES ================= -->
                        <h3 style="margin:24px 0 8px; font-size:15px; color:#dc2626;">
                            💸 Expenses
                        </h3>

                        <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; margin-bottom:16px;">
                            <thead>
                                <tr>
                                    <th align="left" style="padding:8px 10px; background:#fee2e2; color:#7f1d1d; font-size:13px; border-bottom:1px solid #fecaca;">
                                        Name
                                    </th>
                                    <th align="left" style="padding:8px 10px; background:#fee2e2; color:#7f1d1d; font-size:13px; border-bottom:1px solid #fecaca;">
                                        Description
                                    </th>
                                    <th align="right" style="padding:8px 10px; background:#fee2e2; color:#7f1d1d; font-size:13px; border-bottom:1px solid #fecaca;">
                                        Amount
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($expenses as $expense)
                                    <tr>
                                        <td style="padding:8px 10px; font-size:13px; color:#374151; border-bottom:1px solid #fef2f2;">
                                            {{ $expense->user->name ?? '—' }}
                                        </td>
                                        <td style="padding:8px 10px; font-size:13px; color:#374151; border-bottom:1px solid #fef2f2;">
                                            {{ $expense->description ?? '—' }}
                                        </td>
                                        <td align="right" style="padding:8px 10px; font-size:13px; font-weight:600; color:#dc2626; border-bottom:1px solid #fef2f2;">
                                            {{ number_format($expense->amount, 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" style="padding:10px; font-size:13px; color:#9ca3af; text-align:center;">
                                            No expenses recorded today.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        <p style="margin:0 0 20px; font-size:13px; font-weight:600; color:#7f1d1d; text-align:right;">
                            = {{ number_format($expensesTotal, 2) }}
                        </p>

                        <!-- ================= SALES ================= -->
                        <h3 style="margin:24px 0 8px; font-size:15px; color:#16a34a;">
                           📈 Sales
                        </h3>

                        <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; margin-bottom:16px;">
                            <thead>
                                <tr>
                                    <th align="left" style="padding:8px 10px; background:#dcfce7; color:#14532d; font-size:13px; border-bottom:1px solid #bbf7d0;">
                                        Name
                                    </th>
                                    <th align="left" style="padding:8px 10px; background:#dcfce7; color:#14532d; font-size:13px; border-bottom:1px solid #bbf7d0;">
                                        Description
                                    </th>
                                    <th align="right" style="padding:8px 10px; background:#dcfce7; color:#14532d; font-size:13px; border-bottom:1px solid #bbf7d0;">
                                        Amount
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($sales as $sale)
                                    <tr>
                                        <td style="padding:8px 10px; font-size:13px; color:#374151; border-bottom:1px solid #ecfdf5;">
                                            {{ $sale->user?->name ?? '—' }}
                                        </td>
                                        <td style="padding:8px 10px; font-size:13px; color:#374151; border-bottom:1px solid #ecfdf5;">
                                            {{ $sale->description ?? '—' }}
                                        </td>
                                        <td align="right" style="padding:8px 10px; font-size:13px; font-weight:600; color:#16a34a; border-bottom:1px solid #ecfdf5;">
                                            {{ number_format($sale->amount, 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" style="padding:10px; font-size:13px; color:#9ca3af; text-align:center;">
                                            No sales recorded today.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        <p style="margin:0 0 20px; font-size:13px; font-weight:600; color:#14532d; text-align:right;">
                            = {{ number_format($salesTotal, 2) }}
                        </p>

                        <!-- ================= PURCHASES ================= -->
                        <h3 style="margin:24px 0 8px; font-size:15px; color:#ca8a04;">
                           🛒 Purchases
                        </h3>

                        <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; margin-bottom:16px;">
                            <thead>
                                <tr>
                                    <th align="left" style="padding:8px 10px; background:#fef3c7; color:#78350f; font-size:13px; border-bottom:1px solid #fde68a;">
                                        Name
                                    </th>
                                    <th align="left" style="padding:8px 10px; background:#fef3c7; color:#78350f; font-size:13px; border-bottom:1px solid #fde68a;">
                                        Description
                                    </th>
                                    <th align="right" style="padding:8px 10px; background:#fef3c7; color:#78350f; font-size:13px; border-bottom:1px solid #fde68a;">
                                        Amount
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($purchases as $purchase)
                                    <tr>
                                        <td style="padding:8px 10px; font-size:13px; color:#374151; border-bottom:1px solid #fffbeb;">
                                            {{ $purchase->user?->name ?? '—' }}
                                        </td>
                                        <td style="padding:8px 10px; font-size:13px; color:#374151; border-bottom:1px solid #fffbeb;">
                                            {{ $purchase->description ?? '—' }}
                                        </td>
                                        <td align="right" style="padding:8px 10px; font-size:13px; font-weight:600; color:#ca8a04; border-bottom:1px solid #fffbeb;">
                                            {{ number_format($purchase->amount, 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" style="padding:10px; font-size:13px; color:#9ca3af; text-align:center;">
                                            No purchases recorded today.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        <p style="margin:0 0 24px; font-size:13px; font-weight:600; color:#78350f; text-align:right;">
                            = {{ number_format($purchasesTotal, 2) }}
                        </p>

                        <!-- Summary Box -->
                        <div style="margin-top:20px; padding:14px 16px; background:#f8fafc; border-left:4px solid #0f766e; border-radius:6px;">
                            <p style="margin:0; font-size:13px; color:#475569;">
                                This report was generated automatically by <strong>ZeeLedgers</strong>.
                            </p>
                        </div>

                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="background:#f9fafb; padding:16px 24px; text-align:center; border-top:1px solid #e5e7eb;">
                        <p style="margin:0; font-size:12px; color:#6b7280;">
                            © {{ date('Y') }} <strong style="color:#0f766e;">ZeeLedgers</strong>. All rights reserved.
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
