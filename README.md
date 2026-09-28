# Multi-Tenant Usage & Billing Platform

A Laravel-based multi-tenant SaaS backend for managing merchants, customers,
subscription plans, usage tracking, daily aggregation, billing, and invoices.

## Overview

The application supports:

- Multiple merchants (tenants)
- Merchant-specific subscription plans
- Customer subscriptions
- Usage event recording
- Idempotent usage API
- Daily usage aggregation
- Mid-cycle plan changes
- Prorated subscription charges
- Usage overage billing
- Invoice generation using queued jobs
- Cached plan lookups
- Merchant dashboard APIs
- Automated tests

The implementation uses a modular Laravel monolith with a service-oriented
structure. The goal is to keep the application simple while providing a
clear path for scaling usage-heavy workloads.

---

## Architecture

The main flow is:

```text
Usage API
   |
   v
Validation
   |
   v
Usage Controller
   |
   v
Usage Service
   |
   v
Usage Events
   |
   v
Daily Aggregation Job
   |
   v
Daily Usage Aggregates
   |
   +-------------------+
   |                   |
   v                   v
Dashboard          Billing
                       |
                       v
                 Invoice Service
                       |
                       v
                    Invoice
```

### Main Components

#### Usage API

`POST /api/usage`

Records customer usage.

The API includes:

- Request validation
- Merchant/customer relationship validation
- Idempotency protection
- Database-level uniqueness for retries

The idempotency constraint is:

```text
merchant_id + idempotency_key
```

This prevents duplicate usage events when a client retries the same request.

#### Daily Usage Aggregation

Raw usage events are kept as the source of truth.

A queued job aggregates usage by:

```text
merchant + customer + usage date
```

and stores the result in `daily_usage_aggregates`.

This avoids repeatedly scanning large numbers of raw usage events for
reporting and billing calculations.

#### Billing

Billing is handled through `BillingService`.

It supports:

- Subscription base charges
- Mid-cycle plan changes
- Proration
- Included usage
- Overage charges
- Multiple plan segments within one billing period

A plan change splits the billing period into segments.

For example:

```text
September 1
     |
     | Old Plan
     |
September 15
     |
     | New Plan
     |
October 1
```

Usage before the change is calculated using the old plan and usage after the
change is calculated using the new plan.

#### Invoice Generation

Invoice generation is handled through a queued job.

Invoices are protected against duplicate creation using a unique billing
period constraint:

```text
subscription_id
billing_period_start
billing_period_end
```

Invoice items are created only when the invoice itself is newly created, so
retrying the job does not create duplicate invoice items.

---

## Database Design

The main tables are:

### merchants

Stores tenant information.

### plans

Stores merchant-specific subscription plans including:

- Base price
- Billing cycle
- Included units
- Overage rate

### customers

Stores customers belonging to a merchant.

Customer email is unique within a merchant.

### subscriptions

Stores the customer's active subscription and billing period.

### subscription_changes

Records mid-cycle plan changes and their effective dates.

### usage_events

Stores individual usage events.

Important indexes and constraints include:

```text
merchant_id + idempotency_key → UNIQUE

customer_id + usage_date
merchant_id + usage_date
```

### daily_usage_aggregates

Stores daily usage totals per customer.

Constraint:

```text
customer_id + usage_date → UNIQUE
```

This table is used for high-volume reporting and billing reads.

### invoices

Stores generated invoices and billing totals.

Constraint:

```text
subscription_id
billing_period_start
billing_period_end
```

is unique to prevent duplicate invoices for the same billing period.

### invoice_items

Stores the individual base and overage charges belonging to an invoice.

---

## Scaling for Large Usage Volumes

The raw `usage_events` table is designed to remain the source of truth while
`daily_usage_aggregates` is used for repeated calculations.

For example, instead of billing repeatedly scanning millions of raw usage
records:

```text
Millions of usage events
        |
        v
Daily aggregation
        |
        v
Daily usage records
        |
        v
Billing / Dashboard
```

The `usage_events` table also has indexes for common access patterns.

For a significantly larger production deployment, the usage table could be
partitioned by usage date and older raw usage could be moved to an archive
or retention storage according to business requirements.

---

## Caching

Plan lookups are cached because plan information is read frequently during
billing calculations.

The cache key is:

```text
plan:{plan_id}
```

The cache currently uses a one-hour TTL.

When a plan is updated or deleted, the corresponding cache entry is
invalidated through `PlanObserver`.

This prevents stale plan information from remaining in the cache after a
plan change.

---

## Queues

The application uses Laravel's database queue driver.

The main background jobs are:

```text
AggregateDailyUsageJob
GenerateInvoiceJob
```

### Daily Aggregation

The aggregation command dispatches the daily aggregation job.

```bash
php artisan usage:aggregate
```

The scheduler is configured to trigger the command periodically.

### Invoice Generation

Invoices are generated through:

```bash
php artisan billing:generate-invoices
```

Subscriptions whose billing period has ended are selected and invoice jobs
are dispatched to the queue.

A queue worker processes the jobs:

```bash
php artisan queue:work
```

---

## Dashboard

The merchant dashboard endpoint is:

```text
GET /api/merchants/{merchant}/dashboard
```

It provides:

### Top 5 Customers

The five customers with the highest current-month usage.

### Projected Overage Revenue

Current usage is used to estimate projected usage for the full billing month
and calculate potential overage revenue.

### Usage Drop

Customers whose usage has dropped by more than 50% compared with the
previous month are returned.

Dashboard calculations use the daily usage aggregates rather than scanning
the raw usage events.

---

## API Endpoints

### Record Usage

```http
POST /api/usage
```

Example:

```json
{
    "merchant_id": 1,
    "customer_id": 1,
    "idempotency_key": "usage-001",
    "units": 100,
    "usage_date": "2026-09-27"
}
```

### Change Subscription Plan

```http
POST /api/subscriptions/{subscription}/change-plan
```

Example:

```json
{
    "new_plan_id": 3,
    "effective_at": "2026-09-15 00:00:00"
}
```

### Merchant Dashboard

```http
GET /api/merchants/{merchant}/dashboard
```

---

## Setup

### Requirements

- PHP
- Composer
- MySQL
- Node.js/npm if frontend assets are required

### Installation

Clone the repository and install dependencies:

```bash
composer install
```

Create the environment file:

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

Configure the database connection in `.env`.

Run migrations:

```bash
php artisan migrate
```

Seed demo data:

```bash
php artisan db:seed
```

---

## Running the Application

Start the Laravel development server:

```bash
php artisan serve
```

Start the queue worker:

```bash
php artisan queue:work
```

For local scheduler testing:

```bash
php artisan schedule:work
```

---

## Testing

Run the complete test suite:

```bash
php artisan test
```

The tests cover areas including:

- Usage API
- Usage idempotency
- Daily usage aggregation
- Billing
- Plan-change proration
- Overage calculation
- Invoice generation
- Dashboard calculations

---

## Design Decisions

### Service Layer

Business logic is kept in services rather than placing all logic inside
controllers.

For example:

```text
Controller
    |
    v
Service
    |
    v
Models / Database
```

This keeps controllers small and makes the business logic easier to test.

### Raw Usage as Source of Truth

`usage_events` remains the detailed source of truth.

Aggregated data is derived from it and can be rebuilt if necessary.

### Database Constraints

Important business rules are also enforced at the database level.

For example:

```text
merchant_id + idempotency_key
```

prevents duplicate usage events even when concurrent requests occur.

### Pragmatic Architecture

The project uses a modular Laravel monolith rather than introducing
microservices, CQRS, or additional infrastructure that is not required for
the current scope.

The intention is to keep the codebase straightforward while leaving clear
paths for scaling.

---

## Assumptions

- A customer belongs to one merchant.
- A plan belongs to one merchant.
- A subscription belongs to one customer.
- Usage is recorded in units.
- Billing periods are represented using the subscription's
  `current_period_start` and `current_period_end`.
- Billing calculations use a half-open period:
  `start <= date < end`.
- Usage before and after a mid-cycle plan change is calculated against the
  corresponding plan.
- Raw usage events are retained as the source of truth.

---

## Known Production Improvements

For a larger production deployment, I would consider:

- Using decimal-safe money calculations or integer minor units instead of
  floating-point calculations.
- Partitioning the `usage_events` table by usage date.
- Adding a retention/archive strategy for older raw usage events.
- Moving queues to Redis or another dedicated queue backend.
- Further optimizing dashboard queries to avoid repeated aggregation queries.
- Adding monitoring and alerting around queue failures and billing jobs.
- Adding stronger authentication/authorization around merchant-facing APIs.

These are intentionally kept outside the current implementation to avoid
overengineering the take-home assignment.

---

## Rollout and Monitoring

I would roll this out gradually, starting with a small percentage of traffic
and monitoring usage API errors, latency, queue health, and aggregation
results before increasing traffic. This allows any issues with usage
recording or billing to be detected without affecting all merchants at once.

If usage recording silently started failing in production, the first thing I
would want the on-call engineer to check is the **usage API error rate and
application logs**, especially failures around the `POST /api/usage` endpoint.
This would quickly show whether requests are being rejected, failing
validation, or encountering database problems.

---

## Code Review

The controller review exercise is documented separately in:

```text
CODE_REVIEW.md
```

The review focuses on validation, tenant isolation, idempotency,
separation of business logic, and error handling.

---

## Handing this off

If I were handing this project to another engineer, these are the main things I would flag:

1. **Money calculations**
   
   The current implementation uses decimal database columns and PHP numeric calculations for the take-home exercise. For production billing, I would move the money calculations to integer minor units or BCMath to avoid floating-point precision issues.

2. **Usage table scaling**
   
   `usage_events` is designed as the raw source of truth and is indexed for the expected access patterns. At much larger scale, I would introduce table partitioning and a retention strategy based on the usage date.

3. **Production authentication and tenant context**
   
   The demo API accepts `merchant_id` from the request so the assignment can be tested easily. In production, the merchant/tenant would come from authenticated application context rather than being trusted from the request payload.

### Time-boxed corners

The dashboard and billing flow are intentionally kept simple for the assignment. The dashboard currently uses a demo merchant and some of the system-status information is informational rather than a live health check. Subscription period advancement after invoice generation would also need to be added for a complete recurring billing lifecycle.
