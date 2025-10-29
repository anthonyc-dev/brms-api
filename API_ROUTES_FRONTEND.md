# API Routes for React Vite Frontend Implementation

This document lists all available API routes and their HTTP methods that are ready to be implemented in your React Vite frontend application.

## Base Configuration

-   **Base URL**: `http://localhost:8000`
-   **API Prefix**: `/api`
-   **Content-Type**: `application/json`
-   **Authentication**: Bearer Token (where required)

---

## Public Routes (No Authentication Required)

### Authentication Routes

| Method | Endpoint              | Purpose                     | Request Body                                                           | Response Fields                                                                                                                                   |
| ------ | --------------------- | --------------------------- | ---------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------- |
| `POST` | `/api/register`       | Register new user account   | `{ name, email, password, password_confirmation, ...resident_fields }` | `{ response_code: 201, status: "success", message: "Successfully registered", data_user_list: { id, name, email }, resident: { resident_data } }` |
| `POST` | `/api/login`          | User login                  | `{ email, password }`                                                  | `{ response_code: 200, status: "success", message: "Login successful", user_info: { id, name, email }, token: "string", token_type: "Bearer" }`   |
| `POST` | `/api/admin-register` | Register new admin/official | `{ name, username, password, role }`                                   | `{ id, name, username, role, message: "Admin/Official registered successfully." }`                                                                |
| `POST` | `/api/admin-login`    | Admin login                 | `{ username, password }`                                               | `{ id, name, username, role, token: "string", token_type: "Bearer", message: "Login successful as {role}" }`                                      |

### Public Resource Routes

| Method   | Endpoint              | Purpose             | Request Body          | Response Fields                                                                              |
| -------- | --------------------- | ------------------- | --------------------- | -------------------------------------------------------------------------------------------- |
| `GET`    | `/api/residents`      | List all residents  | -                     | `{ status: "success", data: [resident_objects] }`                                            |
| `POST`   | `/api/residents`      | Create new resident | `{ resident_fields }` | `{ success: true, message: "Resident registered successfully", data: { resident_object } }`  |
| `GET`    | `/api/residents/{id}` | Get resident by ID  | -                     | `{ status: "success", data: { resident_object } }`                                           |
| `PUT`    | `/api/residents/{id}` | Update resident     | `{ resident_fields }` | `{ status: "success", message: "Resident updated successfully", data: { resident_object } }` |
| `DELETE` | `/api/residents/{id}` | Delete resident     | -                     | `{ status: "success", message: "Resident deleted successfully" }`                            |

---

## User-Protected Routes (Require `Authorization: Bearer <user_token>`)

### User Management

| Method | Endpoint                    | Purpose                     | Request Body                                            | Response Fields                                                                                                             |
| ------ | --------------------------- | --------------------------- | ------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------- |
| `GET`  | `/api/get-user`             | Get authenticated user info | -                                                       | `{ response_code: 200, status: "success", message: "Fetched user list successfully", data_user_list: { paginated_users } }` |
| `POST` | `/api/logout`               | Logout user (revoke tokens) | -                                                       | `{ response_code: 200, status: "success", message: "Successfully logged out" }`                                             |
| `PUT`  | `/api/update-password/{id}` | Update user password        | `{ current_password, password, password_confirmation }` | `{ response_code: 200, status: "success", message: "Password updated successfully" }`                                       |

### Document Requests

| Method   | Endpoint                     | Purpose                              | Request Body               | Response Fields                                                                                                          |
| -------- | ---------------------------- | ------------------------------------ | -------------------------- | ------------------------------------------------------------------------------------------------------------------------ |
| `POST`   | `/api/request-document`      | Create document request              | `{ document_type, notes }` | `{ response_code: 201, status: "success", message: "Request document created successfully", data: { document_object } }` |
| `GET`    | `/api/get-document/{userId}` | Get all request documents for a user | -                          | `{ response_code: 200, status: "success", message: "Request documents retrieved successfully", data: [ ... ] }`          |
| `PUT`    | `/api/update-document/{id}`  | Update document request              | `{ document_fields }`      | `{ response_code: 200, status: "success", message: "Request document updated successfully", data: { document_object } }` |
| `DELETE` | `/api/delete-document/{id}`  | Delete own document request          | -                          | `{ response_code: 200, status: "success", message: "Request document deleted successfully" }` or 404 if not found        |

### Complaints/Reports

| Method   | Endpoint                        | Purpose                                      | Request Body                                                                                                                                                         | Response Fields                                                                         |
| -------- | ------------------------------- | -------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------- |
| `POST`   | `/api/complainant`              | Create new complaint/report                  | `{ report_type, title, description, location, date_time, complainant_name, contact_number, email, is_anonymous, urgency_level, witnesses, additional_info, status }` | `{ complaint_object }` (201)                                                            |
| `GET`    | `/api/complainant-get/{userId}` | Get all complaints for a user (newest first) | -                                                                                                                                                                    | `[ complaint_object, ... ]` or 404 if none                                              |
| `PUT`    | `/api/complainant-update/{id}`  | Update complaint                             | `{ status, title, description, location, urgency_level }`                                                                                                            | `{ complaint_object }` or `{ message: "Report not found" }` (404)                       |
| `DELETE` | `/api/complainant-delete/{id}`  | Delete complaint                             | -                                                                                                                                                                    | `{ message: "Report deleted successfully" }` or `{ message: "Report not found" }` (404) |
| `GET`    | `/api/complainant-history`      | Get complaint history (all reports)          | -                                                                                                                                                                    | `{ complaint_objects_array }`                                                           |

---

## Admin-Protected Routes (Require `Authorization: Bearer <admin_token>`)

### Admin Management

| Method | Endpoint                 | Purpose                  | Request Body                         | Response Fields                                                             |
| ------ | ------------------------ | ------------------------ | ------------------------------------ | --------------------------------------------------------------------------- |
| `GET`  | `/api/admin-dashboard`   | Get admin dashboard data | -                                    | `{ message: "Welcome to the Admin Dashboard, {name}", role: "admin_role" }` |
| `POST` | `/api/admin-logout`      | Logout admin             | -                                    | `{ message: "{role} logged out successfully." }`                            |
| `PUT`  | `/api/admin-update/{id}` | Update admin profile     | `{ name, username, role, password }` | `{ id, name, username, role, message: "{role} updated successfully." }`     |

### Event Management

| Method   | Endpoint                          | Purpose          | Request Body                   | Response Fields                                                             |
| -------- | --------------------------------- | ---------------- | ------------------------------ | --------------------------------------------------------------------------- |
| `GET`    | `/api/admin-get-event`            | List all events  | -                              | `{ event_objects_array }` or `{ error: "error_message" }` (500)             |
| `GET`    | `/api/admin-get-event-by-id/{id}` | Get event by ID  | -                              | `{ event_object }` or `{ error: "error_message" }` (404)                    |
| `POST`   | `/api/admin-event`                | Create new event | `{ title, description, date }` | `{ message: "Event created successfully.", event: { event_object } }` (201) |
| `PUT`    | `/api/admin-event-update/{id}`    | Update event     | `{ event_fields }`             | `{ message: "Event updated successfully.", event: { event_object } }`       |
| `DELETE` | `/api/admin-event-delete/{id}`    | Delete event     | -                              | `{ message: "Event deleted successfully." }`                                |

### File Storage Management (Admin only)

All routes below require `Authorization: Bearer <admin_token>`.

| Method   | Endpoint                          | Purpose                | Request Body      | Response Fields                                                                                                                  |
| -------- | --------------------------------- | ---------------------- | ----------------- | -------------------------------------------------------------------------------------------------------------------------------- |
| `GET`    | `/api/folders`                    | List all folders       | -                 | `{ folder_objects_array }`                                                                                                       |
| `POST`   | `/api/folders`                    | Create new folder      | `{ folder_data }` | `{ message: "Folder uploaded, zipped, and stored in DB successfully", folder: { folder_object }, download_url: "string" }` (201) |
| `GET`    | `/api/folders/{id}`               | Get folder by ID       | -                 | `{ folder_object }` or `{ error: "Folder not found" }` (404)                                                                     |
| `PUT`    | `/api/folders/{id}`               | Update folder          | `{ folder_data }` | `{ message: "Folder updated successfully", folder: { folder_object } }`                                                          |
| `DELETE` | `/api/folders/{id}`               | Delete folder          | -                 | `{ message: "Folder deleted successfully" }` or `{ error: "Folder not found" }` (404)                                            |
| `GET`    | `/api/folders/download/{zipName}` | Download folder as ZIP | -                 | File download or `{ error: "File not found" }` (404)                                                                             |

---

## Detailed Parameter Information

### Authentication Parameters

#### User Registration (`POST /api/register`)

**Required Fields:**

-   `name` (string): User's full name
-   `email` (string): Valid email address
-   `password` (string): Password (min 8 characters)
-   `password_confirmation` (string): Must match password
-   Additional resident fields as required by ResidentService

#### Admin Registration (`POST /api/admin-register`)

**Required Fields:**

-   `name` (string): Admin's full name
-   `username` (string): Unique username
-   `password` (string): Password
-   `role` (string): Admin role (e.g., "admin", "official")

### Complaint Parameters

#### Create Complaint (`POST /api/complainant`)

**Required Fields:**

-   `report_type` (string): Type of report
-   `title` (string, max 255): Report title
-   `description` (string): Detailed description
-   `location` (string): Incident location
-   `date_time` (date): Date and time of incident
-   `urgency_level` (enum): "low", "medium", "high", "emergency"

**Optional Fields:**

-   `complainant_name` (string, max 255): Name of complainant
-   `contact_number` (string, max 20): Contact number
-   `email` (email): Email address
-   `is_anonymous` (boolean): Whether report is anonymous
-   `witnesses` (string): Witness information
-   `additional_info` (string): Additional details
-   `status` (enum): "pending", "under_investigation", "resolved", "rejected"

### Event Parameters

#### Create Event (`POST /api/admin-event`)

**Required Fields:**

-   `title` (string): Event title
-   `description` (string): Event description
-   `date` (date): Event date

**Auto-populated Fields:**

-   `posted_id`: Admin ID (from authenticated user)
-   `posted_by`: Admin name/username (from authenticated user)

### Document Request Parameters

#### Create Document Request (`POST /api/request-document`)

**Required Fields:**

-   `document_type` (string): Type of document requested
-   `notes` (string): Additional notes or purpose

---

## Error Response Formats

### Validation Errors (422)

```json
{
    "response_code": 422,
    "status": "error",
    "message": "Validation failed",
    "errors": {
        "field_name": ["Error message 1", "Error message 2"]
    }
}
```

### Authentication Errors (401)

```json
{
    "response_code": 401,
    "status": "error",
    "message": "Unauthorized"
}
```

### Not Found Errors (404)

```json
{
    "message": "Resource not found"
}
```

### Server Errors (500)

```json
{
    "response_code": 500,
    "status": "error",
    "message": "An error occurred",
    "error": "Detailed error message"
}
```

---

## Frontend Implementation Notes

### Authentication Flow

1. **User Registration/Login**: Use public routes to register/login
2. **Token Storage**: Store returned tokens in localStorage or secure storage
3. **Request Headers**: Add `Authorization: Bearer <token>` to protected routes
4. **Token Refresh**: Implement token refresh logic if needed

### Error Handling

-   Handle 401 (Unauthorized) responses by redirecting to login
-   Handle 403 (Forbidden) responses for insufficient permissions
-   Handle 422 (Validation Error) responses for form validation

### State Management

Consider using React Context, Redux, or Zustand for:

-   User authentication state
-   API response caching
-   Loading states
-   Error states

### Example API Service Structure

```javascript
// api/auth.js
export const authAPI = {
    login: (credentials) => api.post("/login", credentials),
    register: (userData) => api.post("/register", userData),
    logout: () => api.post("/logout"),
    // ... other auth methods
};

// api/documents.js
export const documentsAPI = {
    createRequest: (data) => api.post("/request-document", data),
    updateRequest: (id, data) => api.put(`/update-document/${id}`, data),
    deleteRequest: (id) => api.delete(`/delete-document/${id}`),
    // ... other document methods
};
```

### React Hook Example

```javascript
// hooks/useAuth.js
export const useAuth = () => {
    const [user, setUser] = useState(null);
    const [token, setToken] = useState(localStorage.getItem("token"));

    const login = async (credentials) => {
        const response = await authAPI.login(credentials);
        setToken(response.data.token);
        localStorage.setItem("token", response.data.token);
        // ... handle success
    };

    // ... other auth methods
};
```

---

## Testing Endpoints

All endpoints can be tested using:

-   **Postman**: Import the API collection
-   **Thunder Client** (VS Code extension)
-   **Insomnia**
-   **Frontend application** (recommended for integration testing)

Remember to set the correct `Content-Type: application/json` header and include authentication tokens where required.
