# Changelog

All notable changes to `laravel-bsg-channel` will be documented in this file.

## 0.2.0 - 2026-10-02

- Rename the package from `laravel-bsg-sms-channel` to `laravel-bsg-channel`.
- Move BSG API transport, requests, responses, and errors to `andriichuk/bsg-php-sdk`.
- Organize the Laravel integration around `Channels\\SmsChannel` and `Messages\\Sms` for future channel expansion.
- Add injectable SMS status lookup by BSG message ID or external reference.

## 0.1.0 - 2026-09-26

- Initial implementation of the BSG World SMS notification channel for Laravel.
- Add single-recipient SMS sending through the BSG REST API.
- Support per-message sender, reference, validity, tariff, and 2-way SMS options.
- Support Guzzle 7 and 8.
