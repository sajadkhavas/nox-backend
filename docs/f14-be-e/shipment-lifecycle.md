# Shipment Lifecycle

Shipment is separate from payment. A shipment snapshots the order delivery method and moves through pending -> ready -> shipped -> delivered. Pickup uses `not_required` and can become delivered without a carrier shipment. Carrier is nullable and is never guessed. Tracking reference is required only for non-pickup dispatch.
