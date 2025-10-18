# Spam Filtering Follow-up

## Immediate Monitoring (Week 1)
- [ ] Track error log entries that mention `Postra: spam drop` to gauge volume.
- [ ] Spot-check legitimate submissions to confirm no false positives from the heuristic detector.
- [ ] Capture representative spam payloads that still sneak through for future tuning.

## Heuristic Enhancements
- [ ] Expand keyword list with terms observed in new spam runs; maintain per-project overrides if needed.
- [ ] Weight form field patterns (e.g., identical content in multiple fields, suspicious email domains).
- [ ] Introduce minimal NLP scoring (e.g., ratio of links to plain text, character entropy).

## Request Validation Hardening
- [ ] Enforce the existing `allowed_domain` by matching `Origin`/`Referer`; allow per-form opt-out.
- [ ] Issue short-lived HMAC tokens to clients (hidden input) and reject requests without them.
- [ ] Add rate limiting at the proxy/webserver level (per IP + per ASN throttles).

## User-facing Safeguards
- [ ] Offer optional challenge (hCaptcha, Cloudflare Turnstile) with per-form toggles.
- [ ] Add UI for form owners to manage blocklists (IP ranges, email domains, keywords).
- [ ] Provide a “soft delete” holding queue for suspected spam to allow manual review.

## Observability & Tooling
- [ ] Store spam reasons and counts in a dedicated table for analytics.
- [ ] Build dashboard filters to show dropped submissions alongside live metrics.
- [ ] Emit structured logs/metrics (e.g., to Datadog) for volume alerting.

## Longer-term Ideas
- [ ] Integrate with reputation services (Project Honey Pot, StopForumSpam) for IP scoring.
- [ ] Explore ML-based classification using historical labeled submissions.
- [ ] Share anonymized spam signatures back to the community/internal threat feed.

