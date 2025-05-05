# REST API Plan

## 1. Resources

- **Users** (table `users`): Contains user credentials and basic info such as login, hashed password, and base city. Users can have an admin flag which is set manually in the database.
- **Cities** (table `cities`): Represents cities associated with users. Each city has a name and is linked to a user. May include a visited flag and a summary (up to 150 characters).
- **Recommendations** (table `recom`): Contains attraction proposals for cities. Includes title, description, model type (manual or AI-generated), creation/modification timestamps, and status (e.g., accepted, edited, rejected). Duplicate titles per user and city are not allowed.
- **AI Logs** (table `ai_logs`): Logs actions taken by AI regarding recommendations (e.g., accepted, edited, rejected), including timestamps and statuses.
- **AI Inputs** (table `ai_inputs`): Stores inputs sent to the AI for processing recommendations.
- **Error Logs** (table `error_logs`): Records system errors such as login failures, validation errors, AI call errors, and other exceptions.

## 2. Endpoints

### 2.1 Users

#### POST /api/users/register
- **Description**: Register a new user. The request must include a unique login, a password (which will be hashed), and a base city.
- **Request Payload**:
  ```json
  {
    "login": "string",              // must be unique and 2 to 50 characters
    "password": "string",           // plain password to be hashed on the server minimum 8 characters
    "cityBase": "string"            // base city, up to 150 characters
  }
  ```
- **Response**:
  - **Success (201 Created)**: Returns the created user details (excluding the password).
  - **Error (400 Bad Request)**: Validation errors.
  - **Error (409 Conflict)**: If the login is already taken.

#### POST /api/users/login
- **Description**: Authenticate a user and issue a JWT token.
- **Request Payload**:
  ```json
  {
    "login": "string",
    "password": "string"
  }
  ```
- **Response**:
  - **Success (200 OK)**: Returns a JWT token and user information.
  - **Error (401 Unauthorized)**: Invalid login or password.

#### POST /api/users/logout
- **Description**: Wylogowanie użytkownika. Unieważnia aktualny token JWT.
- **Response**:
  - **Success (200 OK)**:
    ```json
    {
      "message": "Wylogowano pomyślnie"
    }
    ```

#### PUT /api/users/me
- **Description**: Aktualizacja danych zalogowanego użytkownika.
- **Request Payload**:
  ```json
  {
    "login": "string",              // opcjonalne, musi być unikalne i 2-50 znaków
    "password": "string",           // opcjonalne, minimum 8 znaków
    "cityBase": "string"            // opcjonalne, do 150 znaków
  }
  ```
- **Response**:
  - **Success (200 OK)**: Zwraca zaktualizowane dane użytkownika (bez hasła)
  - **Error (400 Bad Request)**: Błędy walidacji
  - **Error (409 Conflict)**: Jeśli nowy login jest już zajęty

#### GET /api/users/me
- **Description**: Retrieve the authenticated user's profile.
- **Response (200 OK)**:
  ```json
  {
    "id": number,
    "login": "string",
    "cityBase": "string",
    "isAdmin": boolean     // indicates if the user has admin privileges
  }
  ```

### 2.2 Cities

#### GET /api/cities
- **Description**: List all cities for the authenticated user along with the count of recommendations.
- **Query Parameters**:
  - `page` (optional): Page number for pagination.
  - `per_page` (optional): Number of cities per page.
  - `visited` (optional): Boolean filter to show only visited/unvisited cities.
- **Response (200 OK)**:
  ```json
  [
    {
      "id": number,
      "name": "string",             // corresponds to cit_name
      "recommendationCount": number,
      "visited": boolean
    },
    ...
  ]
  ```

#### POST /api/cities/search
- **Description**: Search for a destination city or request supplementary recommendations.
  - **Standard Search**: Verifies city name via Google Geocoding, calculates route from user's base city, and generates initial AI recommendations.
  - **Supplement Search**: If `supplement: true` is passed along with `cityName` and an array of `existingTitles`, it generates *new*, unique recommendations, avoiding the provided titles.
- **Process Flow (Standard Search)**:
  1. Validate `cityName`.
  2. Verify `cityName` using Google Geocoding API. Return error if not a city. Correct name if needed.
  3. Retrieve user's base city.
  4. Attempt to find driving route via Google Directions API.
  5. Generate initial AI recommendations (summary + up to 10 attractions).
  6. Prepend route information to the recommendations list.
- **Process Flow (Supplement Search)**:
  1. Validate `cityName` and `existingTitles`.
  2. Call AI service to generate supplementary recommendations (e.g., up to 5), avoiding `existingTitles`.
  3. Return only the newly generated recommendations.
- **Request Payload (Standard Search)**:
  ```json
  {
    "cityName": "string"           // name of the city to search for
  }
  ```
- **Request Payload (Supplement Search)**:
  ```json
  {
    "cityName": "string",          // name of the city
    "supplement": true,
    "existingTitles": ["string"]   // array of existing recommendation titles to avoid
  }
  ```
- **Response (Standard Search - 200 OK)**:
  ```json
  {
    "city": {
      "id": null,               // ID is null as city is not saved yet
      "name": "string",         // Corrected city name
      "summary": "string"       // AI-generated summary
    },
    "recommendations": [
      { /* Route Info */ },
      { /* AI Recommendation 1 */ },
      // ... up to 10 AI recommendations
    ]
  }
  ```
- **Response (Supplement Search - 200 OK)**:
  ```json
  {
    "newRecommendations": [
      {
         "id": null,
         "title": "string",
         "description": "string",
         "model": "string",
         "status": "generated"
      }
      // ... new recommendations
    ]
  }
  ```
- **Error Responses**:
  - 400 Bad Request (Missing/invalid `cityName`, `existingTitles` in supplement mode, name not a city)
  - 401 Unauthorized
  - 500 Internal Server Error (Geocoding/Directions/AI service errors)

#### POST /api/recommendations/save
- **Description**: Save AI-generated city and recommendations after a user decides to keep them. This creates a new city record for the user (if not exists) and saves all accepted recommendations.
- **Request Payload**:
  ```json
  {
    "city": {
      "name": "string",
      "summary": "string"
    },
    "recommendations": [
      {
        "title": "string",
        "description": "string",
        "model": "string",
        "status": "string"         // 'accepted', 'edited', or 'rejected'
      },
      // ... recommendations to save
    ]
  }
  ```
- **Response**:
  - **Success (201 Created)**:
    ```json
    {
      "city": {
        "id": number,
        "name": "string",
        "summary": "string"
      },
      "savedRecommendations": number,  // count of recommendations saved
      "recommendations": [ { ... } ]   // array of saved recommendation objects with IDs
    }
    ```
  - **Error (400 Bad Request)**: Validation errors.

#### GET /api/cities/{cityId}
- **Description**: Get detailed information about a city, including a short summary (up to 150 characters) and its recommendations.
- **Response**:
  - **Success (200 OK)**:
    ```json
    {
      "id": number,
      "name": "string",
      "summary": "string",          // derived or provided summary
      "recommendations": [ { ... } ]  // list of recommendation objects
    }
    ```
  - **Error (404 Not Found)**: If the cityId does not exist.

#### PUT /api/cities/{cityId}
- **Description**: Update city information, such as marking it as visited.
- **Request Payload**:
  ```json
  {
    "visited": boolean
  }
  ```
- **Response**:
  - **Success (200 OK)**: Returns the updated city details.
  - **Error (404 Not Found)**: If the cityId does not exist.

#### POST /api/cities/{cityId}/recommendations/accept-all
- **Description**: Accept all recommendations for a city at once.
- **Response**:
  - **Success (200 OK)**:
    ```json
    {
      "message": "All recommendations have been accepted.",
      "acceptedCount": number
    }
    ```
  - **Error (404 Not Found)**: If the cityId does not exist.


#### DELETE /api/cities/{cityId}
- **Description**: Delete a city and all its associated recommendations for the authenticated user. Requires confirmation from the client-side before calling.
- **Response**:
  - **Success (204 No Content)**: The city and its recommendations were successfully deleted.
  - **Error (404 Not Found)**: If the cityId does not exist or does not belong to the authenticated user.
  - **Error (403 Forbidden)**: If the user is not authorized to delete this city (should typically be handled by the 404 check based on user ID).

### 2.3 Recommendations

#### GET /api/cities/{cityId}/recommendations
- **Description**: Retrieve a paginated list of recommendations for a given city. If no recommendations are available, an empty list is returned.
- **Query Parameters**:
  - `page` (optional): Page number.
  - `per_page` (optional, default up to 10): Number of recommendations per page.
- **Response (200 OK)**:
  ```json
  [
    {
      "id": number,
      "title": "string",             // up to 150 characters
      "description": "string",
      "model": "string",             // e.g., 'manual' or AI model identifier
      "dateCreated": "timestamp",
      "dateModified": "timestamp",
      "status": "string"             // e.g., 'accepted', 'edited', 'rejected'
    },
    ...
  ]
  ```

#### POST /api/cities/{cityId}/recommendations
- **Description**: Dodaje nowe rekomendacje do istniejącego miasta. Może być używane do ręcznego dodawania rekomendacji lub zapisywania rekomendacji wygenerowanych przez AI.
- **Request Payload**:
  ```json
  {
    "recommendations": [
      {
        "title": "string",
        "description": "string",
        "model": "string",          // 'manual' lub identyfikator modelu AI
        "status": "string"          // 'accepted', 'edited', lub 'rejected'
      }
    ]
  }
  ```
- **Response**:
  - **Success (201 Created)**:
    ```json
    {
      "savedRecommendations": number,  // liczba zapisanych rekomendacji
      "recommendations": [             // lista zapisanych rekomendacji z ID
        {
          "id": number,
          "title": "string",
          "description": "string",
          "model": "string",
          "status": "string",
          "dateCreated": "timestamp"
        }
      ]
    }
    ```
  - **Error (400 Bad Request)**: Błędy walidacji
  - **Error (404 Not Found)**: Jeśli miasto nie istnieje
  - **Error (409 Conflict)**: W przypadku duplikatu tytułu rekomendacji

#### GET /api/recommendations/{id}
- **Description**: Retrieve details of a specific recommendation.
- **Response (200 OK)**:
  ```json
  {
    "id": number,
    "cityId": number,
    "title": "string",
    "description": "string",
    "model": "string",
    "dateCreated": "timestamp",
    "dateModified": "timestamp",
    "status": "string"
  }
  ```
  - **Error (404 Not Found)**: If the id does not exist.

#### POST /api/recommendations
- **Description**: Create a new recommendation manually with duplicate checks (unique title per user and city).
- **Request Payload**:
  ```json
  {
    "cityId": number,
    "title": "string",
    "description": "string",
    "model": "manual"
  }
  ```
- **Response**:
  - **Success (201 Created)**: Returns the created recommendation.
  - **Error (400 Bad Request)**: Validation errors.
  - **Error (409 Conflict)**: Duplicate recommendation detected.

#### PUT /api/recommendations/{id}
- **Description**: Update an existing recommendation (edit, accept, or reject). Also allows marking a recommendation as visited.
- **Request Payload** (all fields optional):
  ```json
  {
    "title": "string",
    "description": "string",
    "status": "string",     // e.g., 'accepted', 'edited', 'rejected'
    "done": boolean         // whether the recommendation has been visited by the user
  }
  ```
- **Response (200 OK)**: Returns the updated recommendation.
- **Note**: Changes are saved immediately upon updating.

#### PUT /api/recommendations/update-done
- **Description**: Mark multiple recommendations as visited or not visited.
- **Request Payload**:
  ```json
  {
    "recommendationIds": [number],  // array of recommendation IDs to update
    "done": boolean                 // whether the recommendations have been visited
  }
  ```
- **Response (200 OK)**:
  ```json
  {
    "message": "Recommendations updated successfully.",
    "updatedCount": number
  }
  ```

#### DELETE /api/recommendations/{id}
- **Description**: Delete a recommendation. Clients must confirm deletion before calling this endpoint.
- **Response (204 No Content)**: No content on success.
- **Error (404 Not Found)**: If the id does not exist.

### 2.4 AI Logs and AI Inputs

#### GET /api/ai-logs
- **Description**: (Optional, for administrative purposes) Retrieve logs of AI actions related to recommendations. If the log list is empty, a message is returned to the client.
- **Response (200 OK)**:
  ```json
  [
    {
      "id": number,
      "userId": number,
      "recommendationId": number,
      "date": "timestamp",
      "status": "string"
    },
    ...
  ]
  ```
- **Response (204 No Content)**: If the log list is empty, a message "No AI logs found" is returned.

#### POST /api/ai-logs
- **Description**: Create a new AI log entry to track recommendation status changes (accept, edit, reject).
- **Request Payload**:
  ```json
  {
    "recommendationId": number,
    "status": "string"       // e.g., 'accepted', 'edited', 'rejected'
  }
  ```
- **Response (201 Created)**: Returns the created AI log record.
- **Note**: This endpoint is typically called automatically when a recommendation status changes but can also be called directly if needed.

#### POST /api/ai-inputs
- **Description**: Record an input sent to the AI for processing recommendations.
- **Request Payload**:
  ```json
  {
    "content": "string",
    "source": "string"        // e.g., description of the input context
  }
  ```
- **Response (201 Created)**: Returns the created AI input record.

### 2.5 Error Logs

#### POST /api/error-logs
- **Description**: Record a system error. This endpoint is primarily for internal use by the application to log errors that occur during execution.
- **Request Payload**:
  ```json
  {
    "type": "string",          // e.g., 'login_error', 'validation_error', 'ai_fetch_error', 'ai_call_error'
    "message": "string",       // detailed error message
    "url": "string",           // optional: URL or endpoint where the error occurred
    "payload": "string"        // optional: additional data like request payload or stack trace
  }
  ```
- **Response (201 Created)**: Returns the created error log record.

#### GET /api/error-logs
- **Description**: (Admin only) Retrieve error logs for monitoring and debugging purposes.
- **Query Parameters**:
  - `page` (optional): Page number for pagination.
  - `per_page` (optional): Number of error logs per page.
  - `type` (optional): Filter by error type.
  - `from_date` (optional): Filter by error timestamp (start date).
  - `to_date` (optional): Filter by error timestamp (end date).
  - `user_id` (optional): Filter by user ID.
- **Response (200 OK)**:
  ```json
  [
    {
      "id": number,
      "type": "string",
      "message": "string",
      "timestamp": "timestamp",
      "userId": number,        // may be null if error is not associated with a user
      "url": "string",
      "payload": "string"
    },
    ...
  ]
  ```
- **Error (403 Forbidden)**: If the user does not have admin privileges.
- **Note**: Access to this endpoint is restricted to users with admin privileges.

## 3. Authentication and Authorization

- The API uses token-based authentication (JWT). Upon successful login, a JWT is issued and must be included in the `Authorization` header for subsequent requests (format: `Bearer <token>`).
- Endpoints enforce row-level security by filtering data based on the user ID extracted from the token.
- Sensitive operations (such as supplementing recommendations or deleting records) require proper authentication and authorization checks.
- Admin-only endpoints are restricted to users with the admin flag set to true in the database. These include error log retrieval and other administrative functions.

## 4. Validation and Business Logic

- **Data Validation**:
  - **Users**: Ensure that the login is unique and that the password meets security standards before hashing.
  - **Cities**: City names should adhere to length constraints. A summary (if provided) must not exceed 150 characters.
  - **Recommendations**: Titles are limited to 150 characters and must be unique per user and city. Duplicate checks are enforced at the API level before database operations.

- **Business Logic**:
  - **Immediate Saving**: Edits to recommendations are saved immediately upon update without requiring additional confirmations.
  - **Supplementary Recommendations**: The API checks the acceptance rate of recommendations. If it falls below 60%, the endpoint to supplement recommendations is enabled. This action is allowed only once per city per user.
  - **Logging**: AI actions (accept, edit, reject) are automatically logged in the `ai_logs` table for audit and analysis.

- **Performance and Security**:
  - **Pagination, Filtering, and Sorting**: List endpoints support query parameters for pagination and filtering.
  - **Database Indexes**: Queries leverage indexes (e.g., on `cit_usr_id`, `rec_usr_id`, `rec_cit_id`) to optimize performance.
  - **Input Sanitization**: All inputs are validated and sanitized to prevent SQL injection and other common vulnerabilities.
