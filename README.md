# KIJUMBE — Mchezo Management System Mockup

This package is a frontend-only interactive mockup for the KIJUMBE university IPT project.

## Technology
- HTML
- CSS
- JavaScript

## What is included
- Responsive sidebar navigation
- Dashboard
- Mchezo group management
- Members
- Rounds
- Contributions
- Rotation / Turns
- Payments
- AzamPay Sandbox demonstration screen
- Meetings
- Fines
- Transactions
- Reports
- Settings
- Add/Edit Member modal
- Demo toast notifications
- Responsive mobile sidebar

## How to run
1. Extract the ZIP.
2. Open `index.html` in a browser.

For the actual project, place the frontend under:
`C:\xampp\htdocs\kijumbe\`

Then open:
`http://localhost/kijumbe/`

## Important
This is a MOCKUP, not the final backend implementation.
- No MySQL connection exists yet.
- No PHP authentication exists yet.
- No real payment is processed.
- AzamPay is represented only as a sandbox/demo UI.
- In the real system, AzamPay credentials must stay on the PHP backend and successful provider responses must be verified before creating financial records.

## Recommended implementation order
1. MySQL database/schema
2. PHP database connection
3. Login + sessions
4. Roles/permissions
5. Mchezo group
6. Members
7. Rounds
8. Contributions
9. Rotation/turns
10. Internal payment records
11. AzamPay Sandbox integration
12. Meetings/attendance/fines
13. Transactions
14. Reports
15. Testing and security
