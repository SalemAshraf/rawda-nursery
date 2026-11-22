# Nursery Family

Bilingual family client for the child-scoped Nursery API. See ../../docs/FAMILY-WORKSPACE.md for endpoints, security behavior, qualification, build commands and remaining scope.

Qualified here with Flutter 3.50.1 / Dart 3.14.0: static analysis, five unit/widget tests, a compiled web build, and actual Arabic/English API-connected browser interactions. Native Android/iOS packaging, real-device checks and store publication are not yet qualified.

Default server: https://nursery.mancydigitals.com. Keep this client paired with the family-workspace backend increment. Do not ship builds using local development URLs.

Before a native production release, confirm the owner's application identifiers, signing setup and distribution account. Generated development application identifiers are not approved store identities. Do not publish to a store without separate authority.
