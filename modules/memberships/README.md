# Memberships Module

## Purpose

This module implements paid membership tiers for the platform to provide gated features, billing, and subscription management for students and institutions.

## Goals

- Support multiple membership tiers (Free, Plus, Pro, Campus)
- Allow users to subscribe, change plans, and cancel subscriptions
- Integrate with a payment provider (recommended: Stripe)
- Provide admin UI / API to manage plans, view subscriptions and payments
- Store billing metadata and support webhooks for reliable state sync

## Recommended Tiers (example)

- Free: basic access, daily challenges, limited AI credits — $0/mo
- Plus: more AI credits, advanced quizzes, priority support — $6.99/mo
- Pro: unlimited personal AI tutors, certificates, analytics — $19.99/mo
- Campus: bulk student seats, SSO support, admin dashboard, invoicing — custom pricing

## Data Model (suggested)

- `memberships` (plans catalog)
  - id, name, slug, description, price_cents, interval (month/year), features (json), active
- `user_memberships` (user subscriptions)
  - id, user_id, membership_id, provider_customer_id, provider_subscription_id, status (active/past_due/cancelled), starts_at, ends_at, trial_ends_at, metadata (json)
- `payments` (payment attempts/receipts)
  - id, user_id, provider_payment_id, amount_cents, currency, status, raw_response (json), charged_at

Add indexes on `user_id`, `provider_subscription_id`. Store monetary values as integers (cents).

## API Endpoints (suggested)

- GET  /api/memberships           : list public plans
- GET  /api/memberships/{id}      : details for a plan
- POST /api/memberships/subscribe : subscribe (authenticated)
- POST /api/memberships/change    : change plan or upgrade/downgrade
- POST /api/memberships/cancel    : cancel subscription
- GET  /api/memberships/me        : current subscription details (authenticated)

Admin endpoints (admin middleware):
- POST   /api/admin/memberships    : create/update/delete plans
- GET    /api/admin/subscriptions  : list user subscriptions
- POST   /api/admin/invoice/{id}   : create/invoice/issue refund

Implementation detail: subscription endpoints should return the platform's normalized API response shape: `{ code, message, success, data }`.

## Billing & Payment Provider

- Recommended provider: Stripe (well-documented webhooks and test mode). Alternatives: Paddle, Braintree.
- Use provider Customer and Subscription objects; store `provider_customer_id` and `provider_subscription_id` on `user_memberships`.
- Implement idempotency and webhook signature verification.
- Use background jobs to process heavy webhook tasks (update DB, notify user, send invoice emails).

## Migrations & Fields (example schema excerpt)

`create_memberships_table`
- `id` (ulid/increment)
- `name` string
- `slug` string unique
- `price_cents` integer
- `interval` enum('month','year')
- `features` json nullable
- `active` boolean default true

`create_user_memberships_table`
- `id`
- `user_id` foreign
- `membership_id` foreign
- `provider_customer_id` string nullable
- `provider_subscription_id` string nullable
- `status` string
- `starts_at` timestamp
- `ends_at` timestamp nullable
- `trial_ends_at` timestamp nullable
- `metadata` json

## Webhooks & Security

- Verify webhook signatures using provider SDK. Do not process raw events without verification.
- Use idempotency keys to avoid double-processing.
- Do not expose raw provider payloads in logs in production.

## Auth & Permissions

- All subscription actions (subscribe, change, cancel, view own subscription) require authenticated users.
- Plan management endpoints require `admin` role or dedicated permission.

## Tests

- Unit tests for membership service (plan lookup, price calculations, proration logic)
- Feature tests for API endpoints (subscribe, change, cancel)
- Integration tests for webhook handling using provider test fixtures and signature verification

## Environment Variables

- `STRIPE_SECRET_KEY`
- `STRIPE_WEBHOOK_SECRET`
- `PAYMENT_PROVIDER` (stripe|paddle|none)
- `BILLING_CURRENCY` (e.g., `usd`)

## Acceptance Criteria

- Users can view plans and subscribe using test card keys
- Admin can create/update plans and view active subscriptions
- Webhook events update `user_memberships` consistently
- Tests cover subscription flows and webhook processing

## Rollout & Data Migration

- If adding subscriptions to an existing user base, backfill `user_memberships` for paid users and migrate any legacy billing IDs.
- Run smoke tests in staging using provider test mode before production rollout.

## Next Steps / Implementation Roadmap

1. Create migrations and models (`Membership`, `UserMembership`, `Payment`).
2. Implement service layer: `MembershipService` to handle subscribe/change/cancel logic.
3. Add provider adapter (Stripe) with test coverage.
4. Add admin UI and API endpoints.
5. Add webhooks endpoint and background job processing.

## Notes

- Keep billing logic centralized to make it easy to swap providers.
- Consider adding coupon/discount support and invoices for Campus accounts.

## Owner / Contact

Add team contact or owner who will maintain billing and provider integrations.
