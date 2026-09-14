# 06. Thiết kế lớp và triển khai

## 6.1. Biểu đồ lớp miền nghiệp vụ

```mermaid
classDiagram
    class User {
        +bigint id
        +string name
        +string email
        +string password
        +UserRole role
        +isAdmin() bool
    }
    class UserPreference {
        +bigint id
        +bigint user_id
        +array taste_tags
        +SugarLevel sugar_level_default
        +IceLevel ice_level_default
        +string allergy_notes
        +string profile_text
        -array profile_embedding
    }
    class Drink {
        +bigint id
        +string name
        +string description
        +string ingredients
        +string category
        +decimal price
        +int calories
        +TemperatureType temperature_type
        +array tags
        +string image_url
        +bool is_available
        -array description_embedding
        +datetime deleted_at
        +scopeAvailable()
    }
    class Order {
        +bigint id
        +bigint user_id
        +OrderStatus status
        +decimal total_price
        +array context_snapshot
    }
    class OrderItem {
        +bigint id
        +bigint order_id
        +bigint drink_id
        +int quantity
        +SugarLevel sugar_level
        +IceLevel ice_level
        +string note
        +decimal unit_price
        +decimal subtotal
    }
    class Rating {
        +bigint id
        +bigint user_id
        +bigint drink_id
        +bigint order_id
        +int rating
        +string comment
    }
    class RecommendationLog {
        +bigint id
        +bigint user_id
        +array context_snapshot
        +array candidate_drink_ids
        +array final_ranked_ids
        +string llm_explanation
        +datetime created_at
    }

    User "1" --> "0..1" UserPreference : preference
    User "1" --> "0..*" Order : orders
    User "1" --> "0..*" Rating : ratings
    User "1" --> "0..*" RecommendationLog : recommendationLogs
    Order "1" --> "1..*" OrderItem : items
    Order "1" --> "0..*" Rating : ratings
    Drink "1" --> "0..*" OrderItem : orderItems
    Drink "1" --> "0..*" Rating : ratings
```

Vector embedding phải được ẩn khỏi JSON Resource. Model `RecommendationLog` không dùng `updated_at` để phù hợp mô hình append-only.

## 6.2. Các lớp điều khiển và dịch vụ

```mermaid
classDiagram
    class AuthController
    class PreferenceController
    class DrinkController
    class OrderController
    class RatingController
    class ReportController
    class RecommendationController
    class UserProfileTextService {
        +sync(preference, user) UserPreference
        -compose(preference, user) string
    }
    class EmbeddingService {
        +embed(text) array
        +cosineSimilarity(a, b) float
    }
    class WeatherService {
        +getCurrentWeather(lat, lon) arrayOrNull
    }
    class RecommendationService {
        +recommend(userId, lat, lon) array
    }
    class UpdateDrinkEmbeddingJob
    class UpdateUserProfileEmbeddingJob

    PreferenceController --> UserProfileTextService
    RatingController --> UserProfileTextService
    DrinkController --> UpdateDrinkEmbeddingJob
    UserProfileTextService --> UpdateUserProfileEmbeddingJob
    RecommendationService --> EmbeddingService
    RecommendationService --> WeatherService
    RecommendationController --> RecommendationService
```

Các controller chỉ điều phối HTTP. Logic tổng hợp profile, embedding, weather và recommendation phải nằm trong service; hai job gọi `EmbeddingService` qua queue worker.

## 6.3. Biểu đồ triển khai Docker đề xuất

```mermaid
flowchart TB
    User[Máy người dùng / Browser]

    subgraph Host[Máy chạy Docker]
        FE[drink-app-frontend\nNode 22 + Vite\nhost :5174 → container :5173]
        WS[drink-app-webserver\nNginx 1.27\nhost :8080 → container :80]
        APP[drink-app-php\nPHP 8.3-FPM + Laravel 13]
        WORKER[drink-app-queue\nphp artisan queue:work]
        DB[(drink-app-mysql\nMySQL 8\nhost :3307 → :3306)]
        REDIS[(drink-app-redis\nRedis 7\nhost :6379)]
        PMA[drink-app-phpmyadmin\nhost :8082 → :80]
        VOLDB[(drink-app-db-data)]
        VOLREDIS[(drink-app-redis-data)]
    end

    User -->|HTTP| FE
    User -->|REST /api| WS
    User -->|dev only| PMA
    WS -->|FastCGI| APP
    APP --> DB
    APP --> REDIS
    WORKER --> REDIS
    WORKER --> DB
    PMA --> DB
    DB --> VOLDB
    REDIS --> VOLREDIS
```

Các cổng là cấu hình môi trường phát triển đề xuất và có thể đổi qua `.env`. MySQL/Redis chỉ nên mở cổng host trong môi trường dev; production không công khai các cổng dữ liệu.

## 6.4. Thành phần triển khai và trách nhiệm

| Thành phần | Image/build | Trách nhiệm | Phụ thuộc |
|---|---|---|---|
| `frontend` | Dockerfile Node | Chạy Vite dev server, phục vụ Vue SPA. | Backend URL qua `VITE_API_BASE_URL`. |
| `webserver` | `nginx:1.27-alpine` | Nhận HTTP và chuyển PHP request tới PHP-FPM. | `app`. |
| `app` | Dockerfile PHP | Laravel API, migration, import demo, dispatch job. | MySQL và Redis healthy. |
| `queue` | Cùng image PHP | Consume Redis queue, tạo embedding, retry/backoff. | MySQL, Redis và dịch vụ OpenAI. |
| `db` | `mysql:8.0` | Dữ liệu giao dịch bền vững. | Named volume. |
| `redis` | `redis:7-alpine` | Cache/session/queue theo env Docker. | Named volume, AOF bật. |
| `phpmyadmin` | `phpmyadmin:5` | Công cụ dev quản trị MySQL. | `db`. |

## 6.5. Yêu cầu triển khai

- Docker Compose phải có queue worker riêng, `restart: unless-stopped`, cùng code/config với API.
- MySQL và Redis có healthcheck; API, webserver, frontend và worker nên có kiểm tra sẵn sàng phù hợp.
- `config/services.php` ánh xạ rõ OpenAI/Weather từ biến môi trường; `.env.example` chỉ chứa tên biến và giá trị rỗng/mẫu.
- Không commit secret; production dùng secret manager hoặc cơ chế inject biến môi trường.
- Worker cấu hình số lần thử, backoff và `failed_jobs`; log không chứa API key/profile nhạy cảm.
- Dữ liệu MySQL/Redis dùng named volume; có chính sách backup/restore cho MySQL.
- Production dùng TLS, build frontend tĩnh và không công khai phpMyAdmin.
- Mọi tài liệu, Docker image và dependency phải thống nhất Laravel 13.

## 6.6. Luồng triển khai recommendation

```mermaid
flowchart LR
    API[Laravel API] -->|dispatch| Redis[(Redis Queue)]
    Worker[Queue Worker\nrestart unless-stopped] -->|consume| Redis
    Worker --> Embedding[EmbeddingService]
    Embedding --> OpenAI[OpenAI Embeddings]
    Worker --> MySQL[(MySQL vectors)]
    API --> Weather[WeatherService + Redis cache]
    API --> Recommend[RecommendationService]
    Recommend --> OpenAIChat[OpenAI structured re-rank]
```

Worker phải được cấu hình restart policy, retry/backoff và log không chứa secret/input nhạy cảm. API recommendation vẫn cần fallback khi mọi tích hợp ngoài không khả dụng.
