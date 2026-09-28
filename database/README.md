# Database

The schema targets the MariaDB/MySQL server bundled with XAMPP.

## Import

From PowerShell:

```powershell
C:\xampp1\mysql\bin\mysql.exe -u root < database\schema.sql
```

If your local root account has a password:

```powershell
C:\xampp1\mysql\bin\mysql.exe -u root -p < database\schema.sql
```

## Contents

- Core RBAC tables
- Fleet registry tables
- Driver, trip, inspection, fuel, maintenance, incident, vendor, parts, audit, and ML prediction tables
- Seed data for default roles and baseline permissions
