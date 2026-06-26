# SWIFT and TARGET2 transfers

If you've ever wanted to make an international transfer, you might have heard of terms such as SWIFT or TARGET2 and wondered how international transfers work.

This guide explains SWIFT and TARGET2 transfers, how they work, and how Solaris handles incoming TARGET2 transfers.

## Quick Comparison: SEPA vs. SWIFT

| Feature | SEPA | SWIFT / TARGET2 |
|  --- | --- | --- |
| **Scope** | EEA Countries Only | **Rest of World (Non-EEA)** |
| **Currency** | EUR Only | Sender: Any / Receiver: EUR (Must convert before arrival) |
| **Speed** | Instant or 1 Business Day | 3–5 Business Days |


## Supported Regions (Scope)

Solaris supports incoming payments from **all global regions**, provided they are **not** part of the [SEPA zone](/guides/digital-banking/sepa-transfers/).

**Rule:** If the sender's bank is located in a country that does not support SEPA transfers (e.g., Australia), they **must** use the SWIFT network to send funds to your account.

## What is SWIFT?

SWIFT, or the Society for Worldwide Interbank Financial Telecommunications, is one of the biggest financial messaging systems in the world. Banks and financial institutions worldwide use the SWIFT network to send messages quickly and securely among all parties involved in a transaction. More than 11,000 financial institutions across more than 200 countries use SWIFT to enable cross-border transactions and payments.

A SWIFT transfer is a message (i.e., a payment order) that contains payment instructions from a sending bank (the payer's bank) to a receiving bank (the recipient's bank). The sending bank sends the payment order via the SWIFT network to the receiving bank, including the transaction details, such as the recipient's bank details, transfer amount, transfer purpose, identification documents (if required), etc. A SWIFT transfer could involve more than two banks, such as intermediary or correspondent banks.

### How do SWIFT transfers work?

SWIFT assigns each participating financial institution a unique ID code, either 8 or 11 characters, known as the **bank identifier code (BIC)** (also called **SWIFT code** or **SWIFT ID**). This code is used to identify the bank's name, country, city, and branch to facilitate cross-border transactions.

A SWIFT/BIC code is formatted as follows:

* **XXXX**: The first 4 characters designate the bank or institution code.
* **XX**: The following 2 characters represent the country code (ISO 3166-1 alpha-2) where the bank is located.
* **XX**: The following 2 characters represent the location code, which can be a city or branch code.
* **XXX**: The last 3 characters (optional) represent the bank's branch code.


The sending bank will send a payment instruction via the SWIFT network to send international transfers. This payment instruction will include the necessary transaction details, such as the amount, recipient bank account details, and the SWIFT code of the receiving bank. Afterwards, SWIFT will route the message to the receiving bank.

However, SWIFT itself is not a financial institution. It does not hold or transfer funds or perform clearing or settlement operations. The SWIFT network serves only as a secure communications channel between banks. After a payment order is sent via the SWIFT network, the actual processing and settlement of the transaction are handled directly via the involved banks.

For processing and settling transactions in euro in the Eurozone, the SWIFT network may use a payment rail, such as SEPA or TARGET2, which handles the execution and settlement of transfers.

## What is TARGET2?

The Trans-European Automated Real-time Gross Settlement Express Transfer System V2 (TARGET2) is the real-time gross settlement (RTGS) system operated by the Eurosystem (the European monetary authority). TARGET2 is mainly used to process and settle large-volume transactions. Central and commercial banks can submit payment orders in euro to TARGET2, where they are processed and settled in central bank money, i.e., money held in an account with a central bank.

Unlike [SEPA](/guides/digital-banking/sepa-transfers/), there's no upper or lower limit to the transaction amount, and the transfers are made using central bank money. For example, originating and sending banks must have an account with their national central bank, a member of the TARGET2 platform.

The TARGET2 system is only open for processing payments on working days (Monday through Friday) from 07:00 to 18:00 (CET). It's also closed on several holidays.

### How do TARGET2 transfers via SWIFT work?

To send a transfer via the TARGET2 system, both sending and receiving banks must have an account with a central bank that's a member of the TARGET2 platform.

For example, if the sending bank is in **Australia** and the receiving bank is in **Germany**:

1. The SWIFT network will route the payment order from the sending bank to the TARGET2 system.
2. The sending bank (Australia) must have an **intermediary bank** that can process the payment on its behalf to the receiving bank (Germany) via the TARGET2 system.


## How Solaris handles TARGET2 transfers

Solaris is a member of the TARGET2 system directly through our BIC `SOBKDEBBXXX` and indirectly through our other DE-BIC. Solaris currently only supports **incoming** TARGET2 transfers in **EUR**.

Incoming TARGET2 transfers are supported from different countries, except banks and countries listed on the general sanctions lists. Additionally, the sending bank must have a correspondent bank in Europe, which can route the payment through the TARGET2 network to Solaris and convert it to EUR if it's in a foreign currency.

The following diagram briefly describes the transfer flow with SWIFT and TARGET2:

![Diagram: TARGET2 transfer flow](/assets/swift-target2-flow.94aa6a9e5e3d386227f3a2f29bcb479e886d6440efbae5e662202ffa1cea1d50.8793f243.svg)

Please note that TARGET2 transfers are intended for intraday high-value transactions, which entail additional AML checks and screening and could result in higher fees and longer processing times, unlike SEPA transfers.

### How can you identify TARGET2 transfers?

TARGET2 transfers are represented on Solaris' system with the booking type `TARGET2_CREDIT_TRANSFER`. It will be returned in the [GET Index account bookings endpoint](/api-reference/digital-banking/account-management/#tag/Accounts/paths/~1v1~1accounts~1%7Baccount_id%7D~1bookings/get) and the [BOOKING webhook](/api-reference/onboarding/webhooks/webhook-events/paths/booking/post).

The field `charge_details` will also include information about the relevant charges applicable to the transfer and who would bear them. Current options include:

- `DEBT`: The debtor is covering the transfer costs.
- `SHAR`: The transfer costs will be shared between the debtor and creditor.
- `CRED`: The creditor is covering the transfer costs.