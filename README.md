# ⚠️ Deprecated — do not install this repository

This repository is the old development tree of the United Payment gateway for
WooCommerce (version **1.0.0**). It is **no longer maintained** and its `master`
branch is **not installable as-is**.

## Why installing this ZIP breaks a site

`.gitignore` excludes `vendor/`, so this source tree has **no Composer
autoloader**. The plugin cannot load a single one of its own classes, and a site
that installs this ZIP stops with a fatal error on every page, including
wp-admin:

```
Uncaught Error: Class "UnitedPayment\WooCommerce\Sandbox\PaymentPage" not found
in .../wp-content/plugins/united-payment-woocommerce-master/optimisthub-united-payment-for-woocommerce.php:77
```

If you already installed this copy, delete the
`wp-content/plugins/united-payment-woocommerce-master/` folder and install the
released package instead. Your gateway settings are kept, because the gateway ID
(`united_payment`) is unchanged.

## Install the maintained plugin instead

- **WordPress Plugin Directory (recommended):**
  <https://wordpress.org/plugins/optimisthub-united-payment-for-woocommerce/>
  — search for *Optimist Hub Payment Gateway* under **Plugins → Add New**.
- **Current source repository:**
  <https://github.com/optimisthub/optimisthub-united-payment-for-woocommerce>
  — this one ships the built `vendor/` directory, so its ZIP is installable.
- **Support forum:**
  <https://wordpress.org/support/plugin/optimisthub-united-payment-for-woocommerce/>

## Working from this source tree (developers only)

If you have a reason to run this exact 1.0.0 code, build its dependencies
yourself before activating it:

```bash
composer install
```

Then copy the whole folder — including the generated `vendor/` directory — into
`wp-content/plugins/`.

---

Developed by [Optimist Hub](https://optimisthub.com)
