<?php

namespace App;

enum AppEnum:string
{
    case Debit = 'debit';

    case Credit = 'credit';

    case Partial = 'partial';

    case Withdraw = 'withdraw';

    case Opening = 'opening';

    case Additional = 'additional';
    
    case Investment = 'investment';

    case Amount = 'amount';

    case Sale = 'sale';

    case MoistureLoss = 'moisture_loss';

    case AmountReceived = 'amount_received';

    case Purchase ='purchase';

    case Expense = 'expense';

    case Paid = 'paid';
    
    case UnPaid = 'unpaid';

}