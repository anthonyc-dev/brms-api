## API Routes Guide

This document lists all available API endpoints, their purpose, authentication, and how to test them in Postman.

-   **Base URL (local)**: `http://localhost:8000`
-   **API prefix**: All endpoints below assume the `/api` prefix. Example: `POST http://localhost:8000/api/login`

### Authentication Overview

-   **User auth**: Sanctum (`auth:sanctum`). After successful `POST /api/login`, use the returned token as `Bearer <token>` in requests to protected user routes.
-   **Admin auth**: Custom admin guard (`auth:admin`). After `POST /api/admin-login`, use `Bearer <token>` for admin-only routes.

---

## Public Routes

### POST `/api/register`

-   **Purpose**: Register a new user account and create the associated resident profile.
-   **Body (JSON)**:

```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "secret123",
    "password_confirmation": "secret123",
    "...resident_fields": "as required by your ResidentService"
}
```

-   **Success**: 201 Created. Returns `data_user_list` (user) and `resident`.
-   **Postman**: Body → raw → JSON; no auth.

### POST `/api/login`

-   **Purpose**: Login user and obtain a Sanctum token.
-   **Body (JSON)**:

```json
{ "email": "john@example.com", "password": "secret123" }
```

-   **Success**: 200 OK. Returns `token` and `token_type` (Bearer).
-   **Postman**: Body → raw → JSON; copy `token` for subsequent user-protected endpoints.

### POST `/api/admin-register`

-   **Purpose**: Register a new admin/official.
-   **Body (JSON)**: Depends on `AdminService@register`, typically includes `name`, `username`, `password`, `role`.
-   **Success**: 201 Created. Returns admin info.
-   **Postman**: Body → raw → JSON; no auth.

### POST `/api/admin-login`

-   **Purpose**: Login admin and obtain an admin token.
-   **Body (JSON)**: Depends on `AdminService@login`, typically `username` and `password`.
-   **Success**: 200 OK. Returns `token` and `token_type` (Bearer).
-   **Postman**: Body → raw → JSON; copy `token` for admin-protected endpoints.

---

## User-Protected Routes (require `Authorization: Bearer <user_token>`)

### GET `/api/get-user`

-   **Purpose**: Fetch paginated list of users.
-   **Postman**: Authorization → Bearer Token → paste user token.

### POST `/api/logout`

-   **Purpose**: Revoke all tokens for the authenticated user.
-   **Postman**: Authorization → Bearer Token; no body.

### PUT `/api/update-password/{id}`

-   **Purpose**: Update current authenticated user's password. Path `{id}` is present in the route but the implementation updates the authenticated user.
-   **Body (JSON)**: As expected by `AuthService@updatePassword`, typically:

```json
{
    "current_password": "secret123",
    "password": "NewPass#1",
    "password_confirmation": "NewPass#1"
}
```

-   **Postman**: Authorization → Bearer Token; Body → raw → JSON.

### POST `/api/request-document`

-   **Purpose**: Create a document request for the authenticated user.
-   **Body (JSON)**: Fields as required by `ReqService@store` (e.g., document type, notes). Example:

```json
{ "document_type": "barangay_clearance", "notes": "Need for job application" }
```

-   **Success**: 201 Created with created request data.
-   **Postman**: Authorization → Bearer Token; Body → raw → JSON.

### PUT `/api/update-document/{id}`

-   **Purpose**: Update an existing document request by ID for the authenticated user.
-   **Body (JSON)**: Fields allowed by `ReqService@update`.
-   **Postman**: Authorization → Bearer Token; Body → raw → JSON.

---

## Admin-Protected Routes (require `Authorization: Bearer <admin_token>`)

### GET `/api/admin-dashboard`

-   **Purpose**: Example admin-only endpoint; returns greeting and role.
-   **Postman**: Authorization → Bearer Token (admin token).

### POST `/api/admin-logout`

-   **Purpose**: Logout the authenticated admin.
-   **Postman**: Authorization → Bearer Token (admin token).

### PUT `/api/admin-update/{id}`

-   **Purpose**: Update admin record by ID.
-   **Body (JSON)**: Fields accepted by `AdminService@update` (e.g., `name`, `username`, `role`, `password` if supported).
-   **Postman**: Authorization → Bearer Token (admin token); Body → raw → JSON.

### POST `/api/admin-event`

-   **Purpose**: Create a new event (admin only).
-   **Body (JSON)**:

```json
{
    "title": "Event Title",
    "description": "Event description (optional)",
    "date": "2025-01-20"
}
```

-   **Note**: `posted_id` and `posted_by` are automatically set from the authenticated admin.
-   **Success**: 201 Created with event data.
-   **Postman**: Authorization → Bearer Token (admin token); Body → raw → JSON.

---

## Products (public REST resource)

Base: `/api/products`

-   GET `/api/products` — List products
-   POST `/api/products` — Create product
    -   Body (JSON):
    ```json
    {
        "name": "Notebook",
        "description": "A5 ruled",
        "price": 99.5,
        "stock": 10
    }
    ```
-   GET `/api/products/{id}` — Get product by ID
-   PUT `/api/products/{id}` — Update product
    -   Body (JSON): any of `name`, `description`, `price`, `stock`
-   DELETE `/api/products/{id}` — Delete product

Postman: For create/update, Body → raw → JSON. These routes are not guarded in `routes/api.php`.

---

## Residents (public REST resource)

Base: `/api/residents`

-   GET `/api/residents` — List residents
-   POST `/api/residents` — Create/register resident
    -   Body (JSON): Fields required by `ResidentService@registerUser`
-   GET `/api/residents/{resident}` — Show resident by route-model bound ID
-   PUT `/api/residents/{id}` — Update resident by ID
    -   Body (JSON): Fields accepted by `ResidentService@updateUser`
-   DELETE `/api/residents/{resident}` — Delete resident

Postman: For create/update, Body → raw → JSON. These routes are not guarded in `routes/api.php`.

---

## Folders (file upload and zipped download)

Base: `/api/folders`

### GET `/api/folders`

-   **Purpose**: List all folders.
-   **Postman**: No auth; Send request.

### POST `/api/folders`

-   **Purpose**: Upload a folder's files; server zips and stores metadata.
-   **Body (form-data)**:
    -   One or more `files[]` entries of type File (select multiple files)
    -   Optional metadata fields as supported by `FolderService@createFolder` (e.g., `folder_name`)
-   **Response**: 201 Created with `folder` and `download_url`.
-   **Postman**: Body → form-data → add key `files[]` (type: File), add multiple file rows.

### GET `/api/folders/download/{zipName}`

-   **Purpose**: Download a previously created zip by name.
-   **Postman**: No auth; paste the `zipName` from prior response or storage.

### GET `/api/folders/{id}`

-   **Purpose**: Fetch a folder by numeric ID.
-   **Postman**: No auth.

### PUT `/api/folders/{id}`

-   **Purpose**: Update folder metadata.
-   **Body**: JSON or form fields as supported by `FolderService@updateFolder`.
-   **Postman**: Body → raw → JSON (or form-data if needed).

### DELETE `/api/folders/{id}`

-   **Purpose**: Delete folder by ID.
-   **Postman**: No auth.

---

## Postman Quick Start

1. Create a new Postman Collection named "BRMS API" with an Environment variable `baseUrl = http://localhost:8000`.
2. Public calls (e.g., `POST {{baseUrl}}/api/login`).
3. For user-protected calls, add Authorization → Bearer Token with the token from login.
4. For admin-protected calls, use the token from `POST {{baseUrl}}/api/admin-login`.
5. For file uploads, in Body select `form-data` and add multiple `files[]` keys of type File.

---

## Notes

-   Validation errors return 422 with an `errors` object.
-   Not found resources return 404.
-   On unexpected failures, endpoints generally return 500 with an `error`/`message`.
-   If running under a different host or a subfolder, adjust the Base URL accordingly.

---

## Data Model & Relationships

This section summarizes the key Eloquent models, important attributes, and how they relate.

### User (`App\\Models\\User`)

-   **Table**: `users`
-   **Attributes (fillable)**: `name`, `email`, `password`
-   **Auth**: Uses Sanctum tokens for API authentication.
-   **Relationships**:
    -   `hasOne(Resident)` — One user has one resident profile.
    -   `hasMany(RequestDocument)` — One user can request many documents.

### Resident (`App\\Models\\Resident`)

-   **Table**: `residents`
-   **Attributes (fillable)**: Extensive personal, address, contact, parents, and ID upload fields including `user_id`, `first_name`, `birth_date`, `house_number`, `contact_number`, `valid_id_path`, `upload_date`, etc.
-   **Casts**: `birth_date` as date, `upload_date` as datetime.
-   **Relationships**:
    -   `belongsTo(User)` via `user_id` — Each resident profile belongs to one user.

### RequestDocument (`App\\Models\\RequestDocument`)

-   **Table**: `document_requests`
-   **Attributes (fillable)**: `user_id`, `document_type`, `full_name`, `address`, `contact_number`, `email`, `purpose`, `reference_number`, `status`
-   **Relationships**:
    -   `belongsTo(User)` via `user_id` — The requester/owner of the document request.

### Product (`App\\Models\\Product`)

-   **Table**: `products`
-   **Attributes (fillable)**: `name`, `description`, `price`, `stock`
-   **Relationships**: None defined in code.

### Folder (`App\\Models\\Folder`)

-   **Table**: `folders` (by convention)
-   **Attributes (fillable)**: `folder_name`, `zip_name`, `original_files`, `description`, `date_created`
-   **Casts**: `original_files` as array, `date_created` as date
-   **Relationships**: None defined in code.

### Admin (`App\\Models\\Admin`)

-   **Table**: `admins` (by convention)
-   **Attributes (fillable)**: `name`, `username`, `password`, `role`
-   **Auth**: Uses Sanctum tokens under a separate admin guard (`auth:admin`).
-   **Relationships**: None defined in code.

### Relationship Diagram (high-level)

User 1──1 Resident
User 1──\* RequestDocument

There are no defined relations between `User/Admin` and `Product` or `Folder` in code.
