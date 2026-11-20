# Changelog

## [0.9.1] - 2026-11-19

### Security
- Require an active account with page approval permission to open the secret preview dialog and manage Guest links.
- Require "preview as user" permission when creating links for an actual user.
- Validate the page ID, preview user, and link identifier before managing secret preview links.
- Restrict previewing as the super user to super users and users with the sudo permission.
- Allow authorized dashboard users to delete links associated with missing or unmanageable pages after token validation.
