# PROCESS

### Заполнить форму
1. Create model in `orders` table
    - `orderNumber` - генерировать UUID
2. Create model in `jobs` table только если рекуррентный платеж
    - заполняем `orderId`, `frequency` - в месяцах, `amount` в копейках, `currencyCode` (643 - rubles, 051 - dram, 840 - USD Dollars)
3. Отправляем запрос в банк с `returnUrl`


### Data Enrich
in Jobs table
1. успешность платежа
2. bindingId
3. nextPaymentDate = createdAt + frequency
4. status = ENABLED | DISABLED


### Job для рекуррентных платежей
1. Мы берем из `jobs` где `status = ENABLED and isProcessing = 0 and nexPaymentDate < now() and attempts < 2`
2. `isProcessing = 1`
3. Request order from bank
4. Pay order by bindingId
    - **unsuccessful order**
        - attempt++
        - nextPaymentDate + 1
        - attempts >= 2
            - status = DISABLED
            - disableReason
            - disabledDate
    - **successful order**
        - attempts = 0
        - nextPaymentDate += frequency
5. isProcessing = 0

