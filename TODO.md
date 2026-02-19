# Roadmap for ONIX Book Manager plugin

## Product types

- Create single format book product type, that extends simple product type
- New book products should have the format and forfatter attributes as standard
- remove custom ISBN field and rely on built-in GTIN-field. Make SKU inherit from GTIN. Rename GTIN to ISBN-13.

## ONIX export class

- Add support for product measurement fields

## ONIX admin

- ONIX admin should validate all fields relevant to the exporter and refuse to export, when fields are missing.
- It would be nice to be able to validate resulting xml against xsd

## Code quality

- Form data should always be processed with nonce verification
