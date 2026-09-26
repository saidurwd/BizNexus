<?php

namespace Modules\Finance\Exceptions;

use Modules\Core\Exceptions\InvalidAccountingTransactionException;

/**
 * No account is mapped for an automatic posting purpose. Shown to the user like any refused transaction, so
 * they can set it up under Account Determination.
 */
class MissingAccountMappingException extends InvalidAccountingTransactionException {}
