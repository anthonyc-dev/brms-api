# Testing Profile Upload in Postman/Prisma

## Important: Use POST method, not PUT!

File uploads with multipart/form-data work better with POST. The route now accepts both POST and PUT.

## Postman/Prisma Setup Instructions:

### 1. Login First
```
POST http://localhost:8000/api/login
Content-Type: application/json

Body (raw JSON):
{
  "email": "user@gmail.com",
  "password": "your_password"
}
```

Save the `token` from the response.

### 2. Update Profile with Image Upload

**IMPORTANT:** Use **POST** method (not PUT)

```
POST http://localhost:8000/api/update-profile/1
```

**Headers:**
- Authorization: `Bearer YOUR_TOKEN_HERE`
- DO NOT set Content-Type header (let Postman set it automatically for multipart/form-data)

**Body:**
- Select "form-data" (NOT raw, NOT x-www-form-urlencoded)
- Add these fields:

| Key      | Type | Value                          |
|----------|------|--------------------------------|
| name     | Text | New Name (optional)            |
| email    | Text | newemail@example.com (optional)|
| profile  | File | [Select image file]            |

**For the profile field:**
1. Click the dropdown next to the key name
2. Select "File" (not "Text")
3. Click "Choose Files" and select an image (jpg, png, gif, webp)

### 3. Check Response

You should get:
```json
{
  "response_code": 200,
  "status": "success",
  "message": "Profile updated successfully",
  "user_info": {
    "id": 1,
    "name": "Updated Name",
    "email": "user@gmail.com",
    "profile": "profiles/1730491234_6543f21a.jpg",
    "profile_url": "http://localhost:8000/storage/profiles/1730491234_6543f21a.jpg"
  }
}
```

## Debugging

If profile is still null, check the Laravel logs:

```bash
tail -f storage/logs/laravel.log
```

The logs will show:
- Whether the file is being received
- File validation details
- Storage operation results
- Any errors that occur

## Common Issues:

1. **Using PUT instead of POST** - Use POST!
2. **Wrong body type** - Must be "form-data", not "raw" or "x-www-form-urlencoded"
3. **Profile field as Text** - Must set profile field type to "File"
4. **Missing token** - Must include Bearer token in Authorization header
5. **File too large** - Max 2MB (2048 KB)
6. **Wrong file type** - Must be jpeg, png, jpg, gif, or webp

## Verify Upload

After successful upload, check the file exists:
```bash
ls -la storage/app/public/profiles/
```

Access the image directly in browser:
```
http://localhost:8000/storage/profiles/FILENAME.jpg
```
