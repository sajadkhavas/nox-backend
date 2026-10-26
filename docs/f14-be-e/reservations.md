# Reservations

Reservations use explicit states inherited from the accepted domain: active, consumed, released, expired and restocked. BE-E adds purpose and correlation keys so checkout and exchange reservations can coexist and remain idempotent.

Checkout reservations expire with the order payment deadline. Exchange destination reservations use `COMMERCE_EXCHANGE_RESERVATION_MINUTES`. `inventory:release-expired` handles awaiting-payment orders and `commerce:expire-reservations` handles any other stale reservation. Production must run Laravel scheduler every minute.
