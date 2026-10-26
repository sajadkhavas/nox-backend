# P4 — Commerce Go-Live Backend Candidate

وضعیت: **PRE-ACTIVATION CANDIDATE**

## Baseline

- Backend P3 merge baseline: `5a874d66b5d031fd1ab739a4b7bd8b7c04d4acf6`
- Accepted API contract: `2026-09-06-p3-storefront-v1`

## Candidate truth

- رزرو موجودی: ۳۰ دقیقه.
- روش‌های رسمی ارسال: `immediate_courier`, `tipax`, `decapost`, `express_post`.
- روش‌های قدیمی `standard` و `pickup` فقط compatibility هستند و در options رسمی P4 نمایش داده نمی‌شوند.
- هر چهار روش رسمی از Filament Admin قابل کنترل‌اند.
- migration فقط schema را اضافه می‌کند و هیچ روش ارسالی را فعال نمی‌کند.
- `CHECKOUT_ENABLED=false` و `PAYMENT_ENABLED=false` پیش‌فرض fail-closed باقی می‌مانند.
- `production_deployed=false` تا استقرار واقعی حفظ می‌شود.

## Activation boundary

- Production/server mutation: **NO**
- Checkout activation: **NO**
- Payment activation: **NO**

این candidate فقط پس از Gate سبز و preflight runtime واقعی مجاز به activation است.
