# Money Contract

Catalog money uses one representation:

```json
{
  "amount": 1250000,
  "currency": "TOMAN"
}
```

`amount` is an integer whole-Toman value sourced from BE-C `*_price_toman` columns. No JSON float is used.

For a valid sale price, `price` is the sale price and `compareAtPrice` is the regular price only when `previous_price` evidence is verified. Otherwise `compareAtPrice` is `null`.

Payment-provider currency conversion belongs to payment execution and is not part of this read contract.
