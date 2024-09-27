<?php
require_once (dirname(__FILE__) . "/configuration.php");

use PayXpert\Connect2Pay\Connect2PayClient;
use PayXpert\Connect2Pay\containers\constant\PaymentMethod;

// Transaction reference should be passed as the first parameter of this script
if ($argc < 2) {
  echo "Usage: php cli-transaction-info.php transactionId\n";
  exit(1);
}

$transactionId = $argv[1];

$c2pClient = new Connect2PayClient($connect2pay, $originator, $password);
$transaction = $c2pClient->getTransactionInfo($transactionId);

if ($transaction != null && $transaction->getResultCode() != null) {
  echo "Payment ID: " . $transaction->getPaymentID() . "\n";
  echo "Payment Merchant Token: " . $transaction->getPaymentMerchantToken() . "\n";
  echo "Transaction ID: " . $transaction->getTransactionID() . "\n";
  if ($transaction->getRefTransactionID()) {
    echo "Referral transaction ID: " . $transaction->getRefTransactionID() . "\n";
  }
  echo "Provider transaction ID: " . $transaction->getProviderTransactionID() . "\n";
  echo "Order ID: " . $transaction->getOrderID() . "\n";
  echo "Order Description: " . $transaction->getOrderDescription() . "\n";
  echo "Payment method: " . $transaction->getPaymentMethod() . "\n";
  if ($transaction->getPaymentNetwork()) {
    echo "Payment network: " . $transaction->getPaymentNetwork() . "\n";
  }
  echo "Operation: " . $transaction->getOperation() . "\n";
  echo "Amount: " . number_format($transaction->getAmount() / 100, 2) . " " . $transaction->getCurrency() . "\n";
  echo "Refunded amount: " . number_format($transaction->getRefundedAmount() / 100, 2) . " " . $transaction->getCurrency() . "\n";
  echo "Result code: " . $transaction->getResultCode() . "\n";
  echo "Result message: " . $transaction->getResultMessage() . "\n";

  $transactionDate = $transaction->getDateAsDateTime();
  if ($transactionDate !== null) {
    echo "Transaction date: " . $transactionDate->format("Y-m-d H:i:s T") . "\n";
  }

  if ($transaction->getSubscriptionID()) {
    echo "Subscription ID: " . $transaction->getSubscriptionID() . "\n";
  }
  $paymentMeanInfo = $transaction->getPaymentMeanInfo();
  if ($paymentMeanInfo !== null) {
    switch ($transaction->getPaymentMethod()) {
      case PaymentMethod::CREDIT_CARD:
        if ($paymentMeanInfo->getCardNumber() !== null) {
          echo "Payment Mean Information:\n";
          echo "* Card Holder Name: " . $paymentMeanInfo->getCardHolderName() . "\n";
          echo "* Card Number: " . $paymentMeanInfo->getCardNumber() . "\n";
          echo "* Card Expiration: " . $paymentMeanInfo->getCardExpireMonth() . "/" . $paymentMeanInfo->getCardExpireYear() . "\n";
          echo "* Card Brand: " . $paymentMeanInfo->getCardBrand() . "\n";

          if ($paymentMeanInfo->getCardLevel() !== null) {
            echo "* Card Level/subtype: " . $paymentMeanInfo->getCardLevel() . "/" . $paymentMeanInfo->getCardSubType() . "\n";
            echo "* Card country code: " . $paymentMeanInfo->getIinCountry() . "\n";
            echo "* Card bank name: " . $paymentMeanInfo->getIinBankName() . "\n";
          }
        }

        break;
      case PaymentMethod::BANK_TRANSFER:
        $sender = $paymentMeanInfo->getSender();
        $recipient = $paymentMeanInfo->getRecipient();

        if ($sender !== null) {
          echo "Payment Mean Information:\n";
          echo "* Sender Account:\n";
          echo ">> Holder Name: " . $sender->getHolderName() . "\n";
          echo ">> Bank Name: " . $sender->getBankName() . "\n";
          echo ">> IBAN: " . $sender->getIban() . "\n";
          echo ">> BIC: " . $sender->getBic() . "\n";
          echo ">> Country code: " . $sender->getCountryCode() . "\n";
        }

        if ($recipient !== null) {
          echo "* Recipient Account:\n";
          echo ">> Holder Name: " . $recipient->getHolderName() . "\n";
          echo ">> Bank Name: " . $recipient->getBankName() . "\n";
          echo ">> IBAN: " . $recipient->getIban() . "\n";
          echo ">> BIC: " . $recipient->getBic() . "\n";
          echo ">> Country code: " . $recipient->getCountryCode() . "\n";
        }

        break;
      case PaymentMethod::DIRECT_DEBIT:
        $account = $paymentMeanInfo->getBankAccount();

        if ($account !== null) {
          echo "Payment Mean Information:\n";
          echo "* Statement descriptor: " . $paymentMeanInfo->getStatementDescriptor() . "\n";

          $collectedAt = $paymentMeanInfo->getCollectedAtAsDateTime();
          if ($collectedAt != null) {
            echo "* Collected At: " . $collectedAt->format("Y-m-d H:i:s T") . "\n";
          }

          echo "* Bank Account:\n";
          echo ">> Holder Name: " . $account->getHolderName() . "\n";
          echo ">> Bank Name: " . $account->getBankName() . "\n";
          echo ">> IBAN: " . $account->getIban() . "\n";
          echo ">> BIC: " . $account->getBic() . "\n";
          echo ">> Country code: " . $account->getCountryCode() . "\n";

          $sepaMandate = $account->getSepaMandate();

          if ($sepaMandate != null) {
            echo "* SEPA mandate:\n";
            echo ">> Description: " . $sepaMandate->getDescription() . "\n";
            echo ">> Status: " . $sepaMandate->getStatus() . "\n";
            echo ">> Type: " . $sepaMandate->getType() . "\n";
            echo ">> Scheme: " . $sepaMandate->getScheme() . "\n";
            echo ">> Signature type: " . $sepaMandate->getSignatureType() . "\n";
            echo ">> Phone number: " . $sepaMandate->getPhoneNumber() . "\n";

            $signedAt = $sepaMandate->getSignedAtAsDateTime();
            if ($signedAt != null) {
              echo ">> Signed at: " . $signedAt->format("Y-m-d H:i:s T") . "\n";
            }

            $createdAt = $sepaMandate->getSignedAtAsDateTime();
            if ($createdAt != null) {
              echo ">> Created at: " . $createdAt->format("Y-m-d H:i:s T") . "\n";
            }

            $lastUsedAt = $sepaMandate->getLastUsedAtAsDateTime();
            if ($lastUsedAt != null) {
              echo ">> Last used at: " . $lastUsedAt->format("Y-m-d H:i:s T") . "\n";
            }

            echo ">> Download URL: " . $sepaMandate->getDownloadUrl() . "\n";
          }
        }

        break;
      case PaymentMethod::WECHAT:
      case PaymentMethod::ALIPAY:
          echo "Payment Mean Information:\n";
          echo "* Total Fee: " . $paymentMeanInfo->getTotalFee() . "\n";
          echo "* Exchange Rate: " . $paymentMeanInfo->getExchangeRate() . "\n";

          break;
    }
  }

  $shopper = $transaction->getShopper();
  if ($shopper !== null) {
    echo "Shopper info:\n";
    echo "* Name: " . $shopper->getFirstName() . "\n";
    echo "* Address: " . $shopper->getAddress1() . " - " . $shopper->getZipcode() . " " . $shopper->getCity() . " - " .
        $shopper->getCountryCode() . "\n";
    echo "* Email: " . $shopper->getEmail() . "\n";

    if ($shopper->getBirthDate() !== null) {
      echo "* Birth date: " . $shopper->getBirthDate() . "\n";
    }

    if ($shopper->getIdNumber() !== null) {
      echo "* ID Number: " . $shopper->getIdNumber() . "\n";
    }

    if ($shopper->getIpAddress() !== null) {
      echo "* IP Address: " . $shopper->getIpAddress() . "\n";
    }
  }
} else {
  echo "Error: " . $c2pClient->getClientErrorMessage() . "\n";
}
