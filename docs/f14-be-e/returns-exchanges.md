# Returns and Exchanges

Return requests are allowed only for owned delivered orders. No invented time-window policy is enforced. Quantities cannot exceed the purchased quantity after considering non-terminal prior requests. Admin transitions are requested -> approved/rejected -> received -> resolved. Restocking is an explicit admin decision and is ledger-backed.

Exchange requests model source order line and destination variant. Destination must belong to the same product and be active/published/available. Approval creates a real destination reservation; completion consumes destination stock and records source stock returning via ledger. Rejection releases an active destination reservation.
