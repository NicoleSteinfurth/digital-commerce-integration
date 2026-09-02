# Security

Do not commit real API keys, database passwords, SMTP credentials, webhook secrets, customer data, invoices or commercial product files.

Use `config/env.example.php` as documentation only and keep the real `config/env.php` local/private.

If a real secret is ever committed, removing it from the current file is not enough: revoke/rotate the credential and remove it from Git history before publishing the repository.
