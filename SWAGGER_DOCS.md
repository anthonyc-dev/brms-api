## Swagger/OpenAPI Documentation Guide (Laravel + L5-Swagger)

This project includes Swagger/OpenAPI support. Use this guide to author complete API docs and publish them to an interactive UI.

### 1) Prerequisites

-   PHP annotations are powered by `swagger-php` via `darkaonline/l5-swagger` (already installed).
-   Default UI route is `/api/documentation`.
-   An `openapi.yaml` file exists at the project root; you may use it alongside annotations or migrate fully to annotations.

### 2) Quick Start

1. Ensure the app can boot.

```bash
php artisan config:clear && php artisan route:clear
```

2. Generate docs from annotations/YAML.

```bash
php artisan l5-swagger:generate
```

3. Serve and open the UI.

```bash
php artisan serve
# Open: http://127.0.0.1:8000/api/documentation
```

If the UI loads but shows no endpoints, add the annotations below and re-run the generate command.

### 3) Where to Put Annotations

-   Place PHPDoc annotations directly above controller actions in `app/Http/Controllers/*`.
-   Define shared schemas in either:
    -   `openapi.yaml` under `components/schemas`, or
    -   A dedicated PHP file (e.g., `app/Docs/Schemas.php`) with `@OA\Schema` annotations.

### 4) Basic Controller Endpoint Annotation

Add above a controller method that lists resources (example: `ResidentsController@index`).

```php
/**
 * @OA\Get(
 *   path="/api/residents",
 *   summary="List residents",
 *   tags={"Residents"},
 *   @OA\Parameter(
 *     name="page", in="query", required=false, description="Page number",
 *     @OA\Schema(type="integer", minimum=1)
 *   ),
 *   @OA\Parameter(
 *     name="per_page", in="query", required=false, description="Items per page",
 *     @OA\Schema(type="integer", minimum=1, maximum=100)
 *   ),
 *   @OA\Response(
 *     response=200, description="OK",
 *     @OA\JsonContent(
 *       type="object",
 *       @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Resident")),
 *       @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     )
 *   ),
 *   security={{"sanctum": {}}}
 * )
 */
```

Create endpoint with body (example: `POST /api/residents`).

```php
/**
 * @OA\Post(
 *   path="/api/residents",
 *   summary="Create resident",
 *   tags={"Residents"},
 *   @OA\RequestBody(
 *     required=true,
 *     @OA\JsonContent(ref="#/components/schemas/ResidentCreateRequest")
 *   ),
 *   @OA\Response(
 *     response=201, description="Created",
 *     @OA\JsonContent(ref="#/components/schemas/Resident")
 *   ),
 *   @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError")),
 *   security={{"sanctum": {}}}
 * )
 */
```

Path with URL parameter (example: `GET /api/residents/{id}`).

```php
/**
 * @OA\Get(
 *   path="/api/residents/{id}",
 *   summary="Get resident by ID",
 *   tags={"Residents"},
 *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *   @OA\Response(response=200, description="OK", @OA\JsonContent(ref="#/components/schemas/Resident")),
 *   @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/Error")),
 *   security={{"sanctum": {}}}
 * )
 */
```

### 5) Schemas (YAML or PHP)

Option A — define in `openapi.yaml` under `components/schemas`:

```yaml
components:
    schemas:
        Resident:
            type: object
            properties:
                id: { type: integer, format: int64 }
                first_name: { type: string }
                last_name: { type: string }
                email: { type: string, format: email }
                created_at: { type: string, format: date-time }
                updated_at: { type: string, format: date-time }
            required: [first_name, last_name]

        ResidentCreateRequest:
            type: object
            properties:
                first_name: { type: string }
                last_name: { type: string }
                email: { type: string, format: email }
            required: [first_name, last_name]

        PaginationMeta:
            type: object
            properties:
                current_page: { type: integer }
                per_page: { type: integer }
                total: { type: integer }

        Error:
            type: object
            properties:
                message: { type: string }

        ValidationError:
            allOf:
                - $ref: "#/components/schemas/Error"
                - type: object
                  properties:
                      errors:
                          type: object
                          additionalProperties:
                              type: array
                              items: { type: string }
```

Option B — define as PHP annotations (place in a scanned file, e.g., `app/Docs/Schemas.php`):

```php
<?php

/**
 * @OA\Schema(
 *   schema="Resident",
 *   type="object",
 *   required={"first_name","last_name"},
 *   @OA\Property(property="id", type="integer", format="int64"),
 *   @OA\Property(property="first_name", type="string"),
 *   @OA\Property(property="last_name", type="string"),
 *   @OA\Property(property="email", type="string", format="email"),
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
```

### 6) Authentication

This project uses Laravel Sanctum. Document a bearer (token) scheme and apply it via `security`.

Add to `openapi.yaml` (recommended):

```yaml
components:
    securitySchemes:
        sanctum:
            type: http
            scheme: bearer
            bearerFormat: JWT

security:
    - sanctum: []
```

Or as PHP annotations in a scanned file:

```php
/**
 * @OA\OpenApi(
 *   security={{"sanctum": {}}}
 * )
 *
 * @OA\SecurityScheme(
 *   securityScheme="sanctum",
 *   type="http",
 *   scheme="bearer",
 *   bearerFormat="JWT"
 * )
 */
```

Then add `security={{"sanctum": {}}}` to each protected operation.

### 7) Validation Mapping Tips

-   Mirror Laravel FormRequest rules in request schemas (`required`, `minLength`, `format`, etc.).
-   For `422` responses, return a consistent structure and reference `#/components/schemas/ValidationError`.

### 8) Organizing Tags

-   Group operations with `tags={"Residents"}` etc. Create one tag per domain (e.g., `Residents`, `Folders`, `Events`, `Admins`).

### 9) Generating Docs

Run whenever annotations change or `openapi.yaml` is updated:

```bash
php artisan l5-swagger:generate
```

Output is typically placed under `storage/api-docs` (UI reads from there). If needed, publish and adjust L5-Swagger config:

```bash
php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider" --tag="config"
```

Key config options (in `config/l5-swagger.php` if published):

-   `paths.annotations`: directories L5-Swagger scans for annotations
-   `paths.docs`: where the generated JSON/YAML is written
-   `routes.api`: UI route prefix (default `/api/documentation`)

### 10) Keeping `openapi.yaml` in Sync

You can use one of these patterns:

-   Single source of truth = annotations. Keep `openapi.yaml` only for high-level info (info/contact/license) or delete it.
-   Single source of truth = `openapi.yaml`. Limit annotations to minimal `@OA\Info` and reference schemas/paths from YAML.

Recommended hybrid: Define global metadata and shared schemas in `openapi.yaml`, while writing endpoint-specific annotations next to controller actions.

### 11) End-to-End Checklist

-   [ ] Add `@OA\Info` (title, version) in a scanned file or inside `openapi.yaml` under `info`.
-   [ ] Define `components/securitySchemes` (Sanctum bearer).
-   [ ] Define shared `components/schemas` (DTOs, errors, meta).
-   [ ] Annotate all endpoints (paths, parameters, requestBody, responses, tags, security).
-   [ ] Generate docs: `php artisan l5-swagger:generate`.
-   [ ] Verify at `http://localhost:8000/api/documentation`.

### 12) Examples for Common Responses

Success with pagination wrapper:

```yaml
responses:
    "200":
        description: OK
        content:
            application/json:
                schema:
                    type: object
                    properties:
                        data:
                            type: array
                            items:
                                $ref: "#/components/schemas/Resident"
                        meta:
                            $ref: "#/components/schemas/PaginationMeta"
```

Standard error:

```yaml
responses:
    "404":
        description: Not found
        content:
            application/json:
                schema:
                    $ref: "#/components/schemas/Error"
```

### 13) Troubleshooting

-   Endpoint not appearing: confirm the file/directory is included in `paths.annotations` and re-run generate.
-   Empty schema refs: ensure the `schema` name matches exactly and the defining file is scanned.
-   Auth not applied: add `security` at operation or global level and regenerate.

---

By following these steps, you will produce complete, accurate, and interactive Swagger docs for all APIs in this project.
