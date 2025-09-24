<?php

namespace App;

enum AppEnum:string
{
    case Debit = 'debit';

    case Credit = 'credit';

    case Withdrawal = 'withdrawal';

    case Opening = 'opening';

    case Additional = 'additional';
    
    case Investment = 'investment';

    case Amount = 'amount';

    case Sale = 'sale';

    case Purchase ='purchase';

    case Expense = 'expense';

}