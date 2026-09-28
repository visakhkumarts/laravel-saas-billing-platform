# Code Review — Usage API

I reviewed the provided `store()` method mainly from the perspective of
correctness, multi-tenancy, reliability, and maintainability.

## What I noticed

### 1. Request data is not validated

The method directly reads values from the request and creates the record.

I would use a Form Request to validate fields such as `merchant_id`,
`customer_id`, `units`, `usage_date`, and `idempotency_key`.

This also keeps the controller cleaner.

### 2. Customer and merchant relationship is not checked

Since this is a multi-tenant application, we should not blindly trust the
`merchant_id` and `customer_id` coming from the request.

Before creating the usage event, we should verify that the customer belongs
to that merchant.

Otherwise, a merchant could potentially create usage against another
merchant's customer.

### 3. Retry can create duplicate usage

Usage APIs can be retried when there is a timeout or network issue.

For example:

- Request is sent successfully.
- Client doesn't receive the response.
- Client sends the same request again.

Without idempotency protection, both requests could create usage records.

I would use an `idempotency_key` and also enforce a unique constraint in the
database.

The database constraint is important because application-level checks alone
can still have race conditions when two requests arrive at the same time.

### 4. Too much responsibility in the controller

The controller is directly finding the customer and creating the usage
event.

I would move the business logic into a service:

`UsageController → UsageService → UsageEvent`

The controller should mainly handle the HTTP request and response, while the
service handles the business rules.

### 5. Error handling could be improved

The current method assumes that the customer exists and that the database
operation will succeed.

We should handle cases such as:

- Invalid request data
- Customer not found
- Customer does not belong to the merchant
- Duplicate idempotency key
- Database errors

This allows the API to return meaningful responses to the client.

## How I would structure it

The implementation I used is roughly:

```text
Request
   ↓
Validation
   ↓
Controller
   ↓
UsageService
   ↓
Tenant validation
   ↓
Idempotency check
   ↓
Database