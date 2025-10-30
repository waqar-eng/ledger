<?php

namespace App;

enum AppEnum:string
{
    case Debit = 'debit';

    case Credit = 'credit';

    case Partial = 'partial';

    case Cash = 'cash';

    case Withdraw = 'withdraw';

    case Opening = 'opening';

    case Additional = 'additional';

    case Investment = 'investment';

    case Amount = 'amount';

    case Sale = 'sale';

    case MoistureLoss = 'moisture_loss';

    case Payment = 'payment';

    case Purchase ='purchase';

    case Expense = 'expense';

    case Paid = 'paid';

    case UnPaid = 'unpaid';

    case ReceivePayment= 'receive-payment';
    case Active = 'active';
    case STATUS = 'status';
    case Completed = 'completed';
    case EndDate = 'end_date';
    case Upcoming = 'upcoming';

}
