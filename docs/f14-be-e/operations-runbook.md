# Operations Runbook

Production database target: MySQL/MariaDB. Run migrations before enabling checkout. Keep `CHECKOUT_ENABLED=false` until delivery configuration and operational readiness are verified. Keep `PAYMENT_ENABLED=false` until a real provider and callback URL are configured. Keep `PAYMENT_REFUNDS_ENABLED=false` until a real refund adapter/path is verified.

Scheduler: run `php artisan schedule:run` every minute. It releases expired checkout reservations, expires other commerce reservations and dispatches inherited notification outbox work. Queue workers are required only for jobs configured by the inherited application; BE-E does not assume a fictional worker.

Inspect Inventory, Inventory Ledger, Reservations, Orders, Shipments, Returns, Exchanges and Refunds in Filament. Use Inventory adjustment action instead of direct database edits.
