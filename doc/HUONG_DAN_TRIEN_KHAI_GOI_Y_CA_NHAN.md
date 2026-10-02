# Hướng dẫn triển khai tính năng nhận gợi ý cá nhân hóa (UC-04)

> Cập nhật theo repository và môi trường Docker đang chạy ngày **2026-10-01**.
> Tài liệu này vừa là hướng dẫn vận hành vừa ghi nhận trạng thái triển khai thực tế. UC-04 đã chạy end-to-end với OpenAI/OpenWeather thật, có fallback, queue worker và 50/50 embedding món.

## 0. Trạng thái triển khai ngày 2026-10-01

Đã triển khai trong code:

- contract `GET /api/recommendations` có Sanctum, rate limit, validation `lat/lon/occasion` và response thống nhất;
- `EmbeddingService` gọi `/v1/embeddings`, kiểm tra vector và tính cosine an toàn;
- `WeatherService` hỗ trợ OpenWeather/WeatherAPI, chuẩn hóa condition, cache tọa độ làm tròn theo bucket 30 phút và negative-cache lỗi 5 phút;
- hai embedding job có queue `embeddings`, retry/backoff, chống ghi vector từ source cũ;
- `RecommendationService` lọc theo nhiệt độ, semantic/rule ranking, chỉ gửi top 10 sang Responses API Structured Outputs, trả tối đa 5 món và ghi log;
- queue worker Docker cùng healthcheck và lệnh `recommendations:backfill-embeddings`;
- frontend ban đầu chỉ tải thời gian, thời tiết và một gợi ý chung; top 5 cùng lý do chỉ tải sau khi người dùng bấm nút;
- khách vãng lai xem menu, đặt món bằng tên và nhận gợi ý chung; nút cá nhân hóa yêu cầu đăng nhập;
- tài khoản chưa có sở thích được hỏi thêm sở thích trước hoặc tiếp tục bằng fallback cơ bản;
- test mới dùng `Http::fake()`/mock, không gọi Internet thật.

Trạng thái vận hành hiện tại: script kiểm tra trong container nhận `HTTP 200` từ OpenAI và OpenWeather; weather trả dữ liệu chuẩn hóa và smoke test recommendation trả 5 món với `hybrid_llm`. Backfill hồ sơ người dùng thật chưa chạy vì cần quyết định rõ việc gửi `profile_text` (có thể chứa ghi chú dị ứng) sang OpenAI.

## 1. Mục tiêu

Hoàn thiện luồng gợi ý đồ uống cho khách hàng dựa trên:

- sở thích khai báo, lịch sử mua và đánh giá;
- thời gian hiện tại của máy chủ;
- vị trí do người dùng tự nguyện cung cấp;
- thời tiết và nhiệt độ tại vị trí đó;
- embedding để đo độ tương đồng;
- LLM chỉ dùng để sắp xếp lại tối đa 5 ứng viên và tạo giải thích ngắn.

Kết quả phải vẫn trả về được khi người dùng từ chối vị trí, Weather API lỗi, OpenAI lỗi,
embedding chưa sẵn sàng hoặc queue đang chậm. Dịch vụ ngoài không được biến thành điểm lỗi duy nhất
của trang menu.

## 2. Trạng thái thực tế hiện tại

### 2.1. Source code

| Thành phần | Trạng thái | Bằng chứng |
|---|---|---|
| Schema `description_embedding`, `profile_embedding` | Đã có, cast JSON đã có | `Drink`, `UserPreference`, migrations |
| `recommendation_logs` | Đã có model, migration và báo cáo admin | `RecommendationLog`, `ReportController` |
| Tổng hợp `profile_text` | Đã chạy sau cập nhật preference/rating | `UserProfileTextService` |
| Dispatch embedding job | Đã gọi API qua queue, có retry/backoff và chống ghi source cũ | `UpdateDrinkEmbeddingJob`, `UpdateUserProfileEmbeddingJob` |
| `EmbeddingService` | Đã gọi API, validate vector và tính cosine | `backend/app/Services/EmbeddingService.php` |
| `WeatherService` | Đã hỗ trợ 2 provider, cache/fallback | `backend/app/Services/WeatherService.php` |
| `RecommendationService` | Đã có pre-filter, ranking, rerank, fallback và log | `backend/app/Services/RecommendationService.php` |
| Request/Resource recommendation | Đã có | `GetRecommendationRequest`, `RecommendationResource` |
| Controller/route | Đã có context public và recommendation authenticated, đều throttle | `RecommendationController`, `routes/api.php` |
| Frontend API | Đã có wrapper | `frontend/src/services/api.js` |
| UI gợi ý | Đã tách context ban đầu và top 5 theo yêu cầu; có lý do tổng quát/từng món, popup login/preference, fallback và cart | `RecommendationBanner.vue` |
| Queue worker Docker | Đã có service `queue` + healthcheck | `docker-compose.yml` |
| Cấu hình OpenAI/Weather trong Laravel | Đã map qua `config/services.php` | root/backend env example + Compose |

`DrinkController::update()` hiện đã phát lại embedding job khi đổi `name`, `description`, `ingredients` hoặc `tags`.

### 2.2. Dữ liệu trong database đang chạy

Số liệu đọc ngày 2026-10-01:

| Chỉ số | Giá trị |
|---|---:|
| Tổng số món | 50 |
| Món đang phục vụ | 50 |
| Món có `description_embedding` | 50 |
| Hồ sơ preference | 1 |
| Hồ sơ có `profile_embedding` | 0 |
| Recommendation log | 1 |
| Job đang chờ trong Redis queue `embeddings` | 0 |
| Failed job | 0 |

Recommendation log hiện có là dữ liệu mẫu từ `database/dump.sql`, không phải log do UC-04 sinh ra.

### 2.3. Trạng thái API key

Đã chạy trong container sau khi recreate service `app`:

```bash
docker compose exec -T app php scripts/check_api_keys.php
```

Kết quả:

- OpenAI: **HTTP 200**, credential được chấp nhận.
- OpenWeather (provider đang chọn): **HTTP 200**.
- `WeatherService` live: `cloudy`, `24.43°C` tại tọa độ thử nghiệm TP.HCM.
- API recommendation live: local khoảng **5,4 giây**; qua `https://drinks.hmmh.click` khoảng **4,0 giây**; cả hai đều trả 5 món, `strategy=hybrid_llm`, `fallback=false`.

Sau khi đổi root `.env`, phải tạo lại container để Compose truyền environment mới:

```bash
docker compose up -d --force-recreate app
docker compose exec -T app php scripts/check_api_keys.php --weather-provider=openweather
```

Chỉ tiếp tục khi cả hai dòng đều báo `[OK]`. Không ghi key thật vào tài liệu, log, issue hoặc commit. File `test_api.md` chỉ giữ kết quả đã làm sạch, không giữ toàn bộ hay một phần key.

## 3. Kiến trúc đích

```text
RecommendationBanner
        |
        +--> GET /api/recommendation-context?lat=&lon=  [public, tải khi mở menu]
        |          +--> thời gian + weather + một gợi ý rule-based
        |
        +--> người dùng bấm "Nhận gợi ý của bạn"
                    |
                    v
GET /api/recommendations?lat=&lon=&occasion=          [Sanctum]
        |
        v
RecommendationRequest -> RecommendationController
                              |
                              v
                     RecommendationService
                       |       |       |
                       |       |       +--> RecommendationReranker -> OpenAI Responses API
                       |       +----------> EmbeddingService -> cosine similarity
                       +------------------> WeatherService -> Redis cache -> Weather API
                              |
                              +--> RecommendationLog
                              +--> RecommendationResource

Preference/rating/admin menu
        |
        +--> queued embedding jobs --> OpenAI Embeddings API --> MySQL JSON vectors
```

Controller chỉ điều phối HTTP. Weather, OpenAI, ranking và fallback phải nằm trong Service. Embedding
được tạo trước qua queue; request gợi ý không chờ tạo embedding hàng loạt.

## 4. Hợp đồng API đã triển khai

### 4.1. Context công khai khi mở menu

```http
GET /api/recommendation-context?lat=10.7769&lon=106.7009
Accept: application/json
```

Endpoint không yêu cầu đăng nhập và không gọi LLM. Nó trả giờ/ngày theo
`RECOMMENDATION_TIMEZONE`, weather chuẩn hóa nếu có vị trí, và một câu gợi ý rule-based. Nếu người
dùng từ chối vị trí hoặc Weather API lỗi, response vẫn có thời gian và gợi ý theo khung giờ.

### 4.2. Request top 5 cá nhân hóa

```http
GET /api/recommendations?lat=10.7769&lon=106.7009&occasion=đang học bài
Authorization: Bearer <sanctum-token>
Accept: application/json
```

Route đặt trong group `auth:sanctum`:

```php
Route::get('recommendations', [RecommendationController::class, 'index']);
```

Tạo `App\Http\Requests\Recommendation\GetRecommendationRequest` với quy tắc:

```php
[
    'lat' => ['nullable', 'required_with:lon', 'numeric', 'between:-90,90'],
    'lon' => ['nullable', 'required_with:lat', 'numeric', 'between:-180,180'],
    'occasion' => ['nullable', 'string', 'max:100'],
]
```

Không nhận `user_id` từ client. User luôn lấy từ Sanctum. Không dùng giờ client làm nguồn chính;
dùng `now()` theo timezone ứng dụng để tránh dữ liệu sai hoặc bị sửa.

### 4.3. Response thành công và fallback

Cả đường chạy đầy đủ và fallback phải dùng cùng schema:

```json
{
  "data": [
    {
      "rank": 1,
      "drink": {
        "id": 3,
        "name": "Trà đào cam sả",
        "description": "...",
        "ingredients": "...",
        "category": "trà trái cây",
        "price": "45000.00",
        "calories": 180,
        "temperature_type": "cold",
        "tags": ["trái_cây", "thanh_mát"],
        "image_url": null,
        "is_available": true
      },
      "score": 0.8421,
      "explanation": "Thanh mát, hợp sở thích trái cây và thời tiết nóng."
    }
  ],
  "meta": {
    "strategy": "hybrid_llm",
    "fallback": false,
    "reason_summary": "Các món được ưu tiên vì phù hợp sở thích thanh mát, thời tiết nóng và ngữ cảnh đang học bài.",
    "context": {
      "hour": 14,
      "weather": "clear",
      "temperature": 32.1,
      "occasion": "đang học bài",
      "location_used": true
    },
    "recommendation_log_id": 25
  }
}
```

Giá trị `strategy` nên cố định thành một trong:

- `hybrid_llm`: cosine + LLM re-rank;
- `semantic_fallback`: cosine thành công, LLM lỗi;
- `profile_rule_fallback`: chưa có user embedding;
- `popular_fallback`: không đủ dữ liệu profile/vector;
- `empty_menu`: không có món khả dụng.

Lỗi Weather/OpenAI không trả 500 nếu vẫn còn món để gợi ý. API trả `200` với `fallback=true`.
Chỉ trả `401` khi chưa đăng nhập và `422` khi query không hợp lệ.

## 5. Thuật toán đề xuất

### Bước 1 — Tạo context

```php
[
    'hour' => (int) now()->format('G'),
    'weather' => null,
    'temperature' => null,
    'occasion' => $occasion,
    'location_used' => $lat !== null && $lon !== null,
]
```

Nếu có đủ `lat/lon`, gọi `WeatherService`. Nếu thiếu hoặc provider lỗi, giữ `weather` và
`temperature` là `null` rồi tiếp tục.

Không lưu tọa độ chính xác vào `recommendation_logs`; chỉ lưu context đã rút gọn. Cache key thời tiết
nên dùng tọa độ đã làm tròn, ví dụ 2 chữ số thập phân, để giảm số key và hạn chế lưu vị trí quá chi tiết.

### Bước 2 — Lấy menu khả dụng và pre-filter

Chỉ lấy:

```php
Drink::query()->available()->get();
```

Món soft-deleted hoặc `is_available=false` không được xuất hiện ở bất kỳ chiến lược nào.

Quy tắc nhiệt độ ban đầu:

- `temperature >= 28`: ưu tiên `cold`, `both`;
- `temperature <= 20`: ưu tiên `hot`, `both`;
- còn lại hoặc không có weather: giữ tất cả.

Nếu pre-filter còn dưới 5 món, bổ sung lại từ toàn bộ menu khả dụng. Đây là “soft filter” để tránh
trả danh sách quá ngắn.

`allergy_notes` hiện là text tự do nên không đủ an toàn để cam kết lọc dị ứng tuyệt đối. Có thể lọc
keyword cục bộ trên `ingredients/tags`, nhưng UI phải tránh tuyên bố món “an toàn y tế”. Không gửi
allergy text vào prompt LLM nếu không cần thiết.

### Bước 3 — Xếp hạng cosine

Nếu có cả `profile_embedding` và `description_embedding` hợp lệ:

```text
similarity = dot(A, B) / (norm(A) * norm(B))
```

`cosineSimilarity()` phải trả `0.0` khi:

- một vector rỗng;
- khác số chiều;
- có phần tử không phải số;
- một vector có norm bằng 0.

Sau đó sắp xếp giảm dần, tie-break theo `drink.id`, chỉ giữ **top 10**. Không gọi OpenAI cho toàn menu.

Nếu user chưa có vector, dùng điểm rule-based:

- số tag trùng với `taste_tags`;
- phù hợp nhiệt độ;
- đã từng đánh giá cao;
- tie-break ổn định theo ID hoặc tên.

### Bước 4 — LLM re-rank top 5

Chỉ gửi dữ liệu tối thiểu của tối đa 10 ứng viên: ID, tên, category, tags, temperature type,
cosine score và context. Không gửi email, tên người dùng, tọa độ chính xác, API key hoặc toàn bộ lịch sử.

Model theo SPEC là `gpt-4o-mini`, nhưng phải đọc từ config để có thể thay đổi mà không sửa code.
Nên gọi OpenAI Responses API với `store: false` và Structured Outputs. Theo
[OpenAI Docs về Structured Outputs](https://developers.openai.com/api/docs/guides/structured-outputs),
JSON Schema strict phù hợp cho trường hợp cần danh sách ID và explanation có cấu trúc ổn định.

Schema đầu ra tối thiểu:

```json
{
  "type": "object",
  "properties": {
    "recommendations": {
      "type": "array",
      "items": {
        "type": "object",
        "properties": {
          "drink_id": { "type": "integer" },
          "explanation": { "type": "string" }
        },
        "required": ["drink_id", "explanation"],
        "additionalProperties": false
      }
    }
  },
  "required": ["recommendations"],
  "additionalProperties": false
}
```

Sau khi nhận kết quả vẫn phải kiểm tra:

- mọi ID thuộc candidate set;
- không có ID trùng;
- món vẫn available;
- explanation không rỗng và được giới hạn chiều dài.

Nếu output thiếu món, bổ sung theo thứ tự cosine. Nếu timeout, 429, 5xx, refusal hoặc JSON không hợp
lệ, bỏ toàn bộ output LLM và trả semantic fallback.

### Bước 5 — Ghi log và trả kết quả

Ghi đúng thứ tự vào:

- `context_snapshot`;
- `candidate_drink_ids`: top 10 trước LLM;
- `final_ranked_ids`: tối đa 5 ID sau LLM/fallback;
- `llm_explanation`: JSON map `drink_id -> explanation` hoặc `null` khi không gọi LLM.

Chỉ tạo log sau khi đã có ít nhất một kết quả. Không để lỗi ghi log làm mất toàn bộ response; log lỗi
an toàn và vẫn trả danh sách fallback.

## 6. Tích hợp OpenAI

### 6.1. Embedding

Gọi `POST https://api.openai.com/v1/embeddings` với:

```json
{
  "model": "text-embedding-3-small",
  "input": "...",
  "encoding_format": "float"
}
```

OpenAI Docs xác nhận `text-embedding-3-small` phù hợp cho recommendation và mặc định trả vector
1536 chiều: [Vector embeddings](https://developers.openai.com/api/docs/guides/embeddings).

`EmbeddingService::embed()` cần:

- bỏ qua input rỗng;
- timeout rõ ràng;
- retry có giới hạn chỉ cho timeout/429/5xx;
- gọi `throw()` và bắt exception ở Service/Job;
- kiểm tra `data.0.embedding` là mảng số, đúng chiều cấu hình;
- không log input đầy đủ, profile text hay Authorization header;
- không trả vector giả khi lỗi; throw exception để job retry.

Không cần thêm SDK ở giai đoạn đầu; Laravel `Http` facade đủ cho cả fake test và timeout/retry.

### 6.2. Rerank

Khuyến nghị tạo `RecommendationReranker` riêng thay vì đặt HTTP call trong
`RecommendationService`. Dùng endpoint `POST /v1/responses`, `store: false`, và
`text.format.type=json_schema`. OpenAI Docs ghi rõ Structured Outputs trên Responses API dùng
`text.format`: [Migrate to the Responses API](https://developers.openai.com/api/docs/guides/migrate-to-responses).

Thời gian chờ LLM trong request người dùng phải ngắn, đề xuất 3 giây. Hết thời gian thì fallback ngay,
không retry nhiều lần trong cùng request.

## 7. WeatherService

Chốt một provider trong `.env`; không tự đoán provider theo hình dạng key.

Interface trả về thống nhất:

```php
/** @return array{weather: string, temperature: float, provider: string}|null */
public function getCurrentWeather(float $lat, float $lon): ?array;
```

Chuẩn hóa condition của provider về các giá trị nội bộ đơn giản như:

```text
clear, cloudy, rain, storm, mist, unknown
```

Cache Redis:

```text
weather:{provider}:{rounded_lat}:{rounded_lon}:{30_minute_bucket}
TTL: 1800 giây
```

Timeout đề xuất 3 giây, retry tối đa 1 lần cho lỗi kết nối/5xx, không retry 401/403/422.
Mọi lỗi trả `null` và log provider + HTTP status + latency, không log key hoặc tọa độ đầy đủ.

Test bằng `Http::fake()`; test suite không gọi mạng thật.

## 8. Embedding jobs và queue worker

### 8.1. `UpdateDrinkEmbeddingJob`

Source text ổn định:

```text
Tên: {name}. Mô tả: {description}. Thành phần: {ingredients}. Thẻ: {tags}.
```

Job fresh model, bỏ qua món đã xóa, gọi `EmbeddingService`, chỉ cập nhật khi vector hợp lệ.
Sửa controller để `description` cũng trigger job:

```php
$drink->wasChanged(['name', 'description', 'ingredients', 'tags'])
```

### 8.2. `UpdateUserProfileEmbeddingJob`

Fresh preference; nếu `profile_text` rỗng thì set vector `null` và kết thúc. Nếu có text, embed và cập
nhật `profile_embedding`. Nên dispatch sau commit khi gọi từ transaction.

### 8.3. Chính sách job

Đề xuất chung:

```php
public int $tries = 3;
public int $timeout = 30;
public function backoff(): array { return [10, 60, 300]; }
```

Cân nhắc `ShouldBeUnique` theo model ID để tránh nhiều job giống nhau. Vì job đọc model mới nhất khi
chạy, một job muộn vẫn tạo vector từ source hiện tại.

### 8.4. Worker Docker

Thêm service dùng cùng image/backend volume:

```yaml
queue:
  build:
    context: .
    dockerfile: docker/php/Dockerfile
  command: php artisan queue:work redis --queue=embeddings,default --sleep=1 --tries=3 --timeout=30
  restart: unless-stopped
  depends_on:
    redis:
      condition: service_healthy
    db:
      condition: service_healthy
```

Việc `dispatch()` thành công không chứng minh embedding đã được tạo; phải kiểm tra worker log, failed
jobs và giá trị vector trong DB.

## 9. Cấu hình cần bổ sung

Không gọi `env()` trực tiếp trong Service. Bổ sung vào `config/services.php`:

```php
'openai' => [
    'key' => env('OPENAI_API_KEY'),
    'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
    'embedding_model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
    'embedding_dimensions' => (int) env('OPENAI_EMBEDDING_DIMENSIONS', 1536),
    'rerank_model' => env('OPENAI_RERANK_MODEL', 'gpt-4o-mini'),
    'timeout' => (int) env('OPENAI_TIMEOUT', 10),
    'rerank_timeout' => (int) env('OPENAI_RERANK_TIMEOUT', 15),
],
'weather' => [
    'provider' => env('WEATHER_API_PROVIDER', 'openweather'),
    'key' => env('WEATHER_API_KEY'),
    'timeout' => (int) env('WEATHER_TIMEOUT', 3),
    'cache_ttl' => (int) env('WEATHER_CACHE_TTL', 1800),
],
```

Đồng bộ tên biến vào root `.env.example`, `backend/.env.example`, `docker-compose.yml`; giá trị thật
chỉ đặt trong root `.env`. Sau khi sửa config production:

```bash
docker compose exec -T app php artisan config:clear
```

## 10. Backend files cần tạo/sửa

### Tạo mới

```text
backend/app/Http/Requests/Recommendation/GetRecommendationRequest.php
backend/app/Http/Resources/RecommendationResource.php
backend/app/Services/RecommendationReranker.php
backend/tests/Feature/RecommendationTest.php
backend/tests/Unit/Services/EmbeddingServiceTest.php
backend/tests/Unit/Services/WeatherServiceTest.php
backend/tests/Unit/Services/RecommendationServiceTest.php
backend/tests/Unit/Jobs/UpdateDrinkEmbeddingJobTest.php
backend/tests/Unit/Jobs/UpdateUserProfileEmbeddingJobTest.php
```

Nên thêm Artisan command backfill để vận hành lặp lại an toàn:

```text
backend/app/Console/Commands/BackfillRecommendationEmbeddings.php
php artisan recommendations:backfill-embeddings --drinks --users
```

### Sửa

```text
backend/config/services.php
backend/routes/api.php
backend/app/Http/Controllers/Api/RecommendationController.php
backend/app/Services/EmbeddingService.php
backend/app/Services/WeatherService.php
backend/app/Services/RecommendationService.php
backend/app/Jobs/UpdateDrinkEmbeddingJob.php
backend/app/Jobs/UpdateUserProfileEmbeddingJob.php
backend/app/Http/Controllers/Api/DrinkController.php
docker-compose.yml
.env.example
backend/.env.example
```

Không cần migration mới nếu giữ schema hiện tại. Nếu muốn invalidation chặt chẽ bằng source hash thì
có thể thêm `embedding_source_hash`, nhưng đây là cải tiến tùy chọn, không phải điều kiện tối thiểu.

## 11. Frontend

### 11.1. API wrapper

Thêm vào `frontend/src/services/api.js`:

```js
export const recommendationsApi = {
  context: (params) => apiClient.get('/recommendation-context', { params }),
  list: (params) => apiClient.get('/recommendations', { params }),
}
```

### 11.2. `RecommendationBanner.vue`

Khi mở menu, component chỉ gọi `context()` và hiển thị:

- giờ/ngày hiện tại;
- thời tiết, nhiệt độ nếu lấy được vị trí;
- một câu gợi ý cơ bản theo thời gian/thời tiết;
- nút “Nhận gợi ý của bạn”.

Không tự gọi endpoint top 5. Chỉ sau khi nhấn nút, component mới xử lý:

- khách vãng lai: mở popup yêu cầu đăng nhập/đăng ký, không chặn xem menu hoặc đặt món;
- tài khoản đã có sở thích: gọi top 5 với preference/profile hiện tại;
- tài khoản chưa có sở thích: hỏi “Thêm sở thích trước” hoặc “Bỏ qua và tiếp tục”; lựa chọn bỏ qua dùng fallback cơ bản.

Phần top 5 cần hỗ trợ các trạng thái:

- chưa yêu cầu vị trí;
- đang tải;
- thành công;
- fallback nhưng vẫn có kết quả;
- danh sách rỗng;
- lỗi mạng/API;
- người dùng từ chối vị trí;
- đoạn văn `reason_summary` giải thích chung và `explanation` đầy đủ cho từng món.

Để có thời tiết hiện tại, UI có thể xin quyền vị trí khi mở menu và luôn có nút tải lại vị trí. Nếu từ
chối, gọi context/top 5 không có `lat/lon`; menu, giỏ và checkout vẫn hoạt động bình thường. Chỉ hiển
thị input/select `occasion` cho tài khoản đã đăng nhập.

Mỗi recommendation dùng lại `DrinkCard` hoặc component nhỏ tương thích, có nút thêm vào Pinia cart.
Không tải lại menu cho từng món; response recommendation đã chứa `DrinkResource`.

### 11.3. UX đề xuất

- Hiển thị tối đa 5 món theo chiều ngang/slider hoặc grid responsive.
- Hiển thị một đoạn lý do tổng quát và lý do ngắn cho từng món, không hiển thị raw score cho khách hàng.
- Có nút “Thử lại” khi request lỗi.
- Có nhãn nhẹ “Gợi ý theo sở thích” hoặc “Gợi ý cơ bản” khi fallback.
- Không chặn menu/cart trong lúc recommendation đang tải.

## 12. Test bắt buộc

### 12.1. Unit tests

`EmbeddingServiceTest`:

- cosine của hai vector giống nhau gần `1`;
- vector trực giao bằng `0`;
- vector âm hợp lệ;
- vector rỗng, khác chiều, zero norm và phần tử không phải số;
- embed success, 401, timeout, 429/5xx và malformed response bằng `Http::fake()`.

`WeatherServiceTest`:

- normalize response provider;
- cache hit không gọi HTTP lần hai;
- lat/lon tạo cache key đã làm tròn;
- 401/timeout/5xx trả `null`;
- key không xuất hiện trong log.

`RecommendationServiceTest`:

- chỉ lấy món available và chưa soft-delete;
- weather pre-filter nhưng vẫn đủ candidate;
- cosine sort ổn định;
- chỉ gửi top 10 sang reranker và chỉ trả top 5;
- loại ID lạ/trùng từ LLM;
- fallback khi thiếu weather/vector/key/LLM;
- ghi log đúng thứ tự.

Job tests:

- text source đúng;
- ghi vector khi thành công;
- không ghi vector rỗng;
- deleted/missing model được bỏ qua;
- exception làm job retry/fail đúng chính sách.

### 12.2. Feature tests

- guest lấy context công khai thành công nhưng nhận `401` ở top 5 cá nhân hóa;
- context vẫn trả schema ổn định khi thiếu vị trí hoặc Weather API lỗi;
- query lat/lon/occasion sai nhận `422`;
- user nhận cùng response schema ở success và fallback;
- không trả món unavailable/deleted;
- tạo đúng một `recommendation_logs` cho một response có kết quả;
- không gửi toàn menu tới OpenAI fake;
- không phát sinh N+1 query;
- OpenAI/Weather đều fake, test không gọi Internet.

### 12.3. Frontend tests

Nếu bổ sung Vitest/Vue Test Utils:

- mở trang chỉ tải context, chưa tải top 5;
- guest bấm cá nhân hóa thấy popup đăng nhập;
- tài khoản chưa có preference thấy popup lựa chọn trước khi gọi top 5;
- loading -> result và hiển thị `reason_summary`/explanation;
- từ chối vị trí vẫn gọi API không có tọa độ;
- fallback vẫn render món;
- add recommendation vào cart;
- retry sau lỗi.

## 13. Hiệu năng, bảo mật và quan sát

- Mục tiêu p95 dưới 8 giây khi Weather cache hit; re-rank timeout 15 giây rồi fallback để ưu tiên tính sẵn sàng.
- Không sinh embedding trong request recommendation.
- Không log API key, Authorization header, prompt đầy đủ, profile text, allergy note hoặc tọa độ chính xác.
- Log có cấu trúc: provider, latency, HTTP status, fallback strategy, candidate count, request ID.
- Thêm rate limit cho route recommendation, ví dụ 20 request/phút/user.
- Cache Weather 30 phút; chưa cache recommendation cho tới khi có chiến lược invalidation rõ ràng.
- Validate mọi output LLM; LLM không được tự tạo drink ID hoặc quyết định món có tồn tại.
- Recommendation không phải tư vấn y tế; dị ứng phải xử lý cục bộ và hiển thị cảnh báo phù hợp.

## 14. Trình tự triển khai đề xuất

### Giai đoạn 0 — Gỡ blocker môi trường

- [x] Rotate/thay OpenAI key hợp lệ.
- [x] Chốt OpenWeather làm provider mặc định và đặt đúng key.
- [x] Chạy `check_api_keys.php` đạt `[OK]` cho cả OpenAI và OpenWeather.
- [x] Bổ sung config/env mapping, không lộ secret.

### Giai đoạn 1 — REC-01 và REC-02

- [x] Chốt Request/Resource/route contract.
- [x] Implement `EmbeddingService` + cosine tests.
- [x] Ổn định schema success/fallback trước khi nối UI.

### Giai đoạn 2 — REC-03, REC-04 và OPS-01

- [x] Hoàn thiện hai jobs.
- [x] Sửa invalidation khi đổi `description`.
- [x] Implement WeatherService + Redis cache.
- [x] Thêm queue worker Docker.
- [x] Worker `embeddings,default` healthy; healthcheck xác nhận Redis queue không tồn đọng và không có failed job.
- [x] Backfill menu đã ghi đủ 50/50 vector; Redis queue còn 0 job và failed job bằng 0.
- [ ] Backfill `profile_text` thật sau khi có quyết định đồng ý gửi dữ liệu sở thích/dị ứng sang provider hoặc đã tối thiểu hóa nội dung.

### Giai đoạn 3 — REC-05 và REC-07 backend

- [x] Implement candidate/filter/cosine/fallback.
- [x] Implement structured LLM rerank top 5.
- [x] Ghi log và thêm test backend bằng HTTP fake/mock.
- [x] Smoke test live: một request recommendation gọi một lần re-rank, trả 5 món `hybrid_llm` trong khoảng 5,4 giây.

### Giai đoạn 4 — REC-06 frontend

- [x] Thêm API wrapper.
- [x] Tách context ban đầu khỏi top 5 chỉ tải khi nhấn nút.
- [x] Hoàn thiện banner/result/lý do tổng quát/lý do từng món/loading/error/fallback.
- [x] Popup yêu cầu đăng nhập cho guest và popup lựa chọn thêm sở thích cho tài khoản mới.
- [x] Geolocation tùy chọn.
- [x] Thêm món gợi ý vào cart.
- [x] Cho guest xem menu, quản lý giỏ và checkout bằng tên; lịch sử/rating/cá nhân hóa vẫn yêu cầu tài khoản.
- [x] Frontend lint và production build.
- [ ] Frontend component/E2E tests (repository chưa có Vitest/Playwright).

### Giai đoạn 5 — Vận hành

- [ ] Kiểm tra `recommendation-effectiveness` có log thật.
- [ ] Theo dõi failed jobs/provider latency/fallback rate.
- [x] Cập nhật UI design, task tracker và tài liệu triển khai.
- [ ] Đồng bộ thêm SPEC/database docs sau khi nghiệm thu credential và dữ liệu thật.
- [x] Backend full suite 72 test / 292 assertion, frontend lint và production build pass (2026-10-01).
- [x] Smoke test authenticated qua `https://drinks.hmmh.click`: trả 5 món `hybrid_llm`, `fallback=false` trong khoảng 4,0 giây; tài khoản/log thử nghiệm đã được xóa sau test.

## 15. Lệnh nghiệm thu dự kiến

```bash
# 1. Credential
docker compose exec -T app php scripts/check_api_keys.php --weather-provider=openweather

# 2. Backend
docker compose exec -T app php artisan config:clear
docker compose exec -T app php artisan migrate --force
docker compose exec -T app php artisan recommendations:backfill-embeddings --drinks --users
docker compose exec -T app php artisan test
docker compose exec -T app ./vendor/bin/pint --dirty

# 3. Queue
docker compose ps queue
docker compose logs --tail=200 queue
docker compose exec -T app php artisan queue:failed

# 4. Frontend
docker compose exec -T frontend npm run lint
docker compose exec -T frontend npm run build

# 5. Smoke test public context và authenticated top 5
# GET https://drinks.hmmh.click/api/recommendation-context
# GET https://drinks.hmmh.click/api/recommendations (Bearer token)
```

## 16. Definition of Done riêng cho UC-04

UC-04 chỉ được đánh dấu hoàn thành khi:

- key kiểm tra thành công nhưng test suite vẫn dùng fake, không phụ thuộc Internet;
- tất cả món và hồ sơ cần thiết có embedding hoặc có fallback rõ ràng;
- queue worker tự khởi động lại và thực sự consume job;
- API authenticated/validated, chỉ trả món available;
- weather/location là tùy chọn;
- OpenAI/Weather lỗi vẫn trả cùng response schema;
- LLM chỉ nhận top ứng viên, không nhận toàn menu;
- log recommendation đúng thứ tự và report admin đọc được dữ liệu thật;
- frontend có loading/error/fallback/result và thêm được món vào cart;
- backend tests, frontend lint/build pass;
- không có secret/PII trong source, log, fixture hoặc Markdown;
- `AGENTS.md`, SPEC và tài liệu UI được cập nhật với bằng chứng test mới.

## 17. Quyết định triển khai và phần còn cần chốt

1. Mặc định dùng OpenWeather; vẫn hỗ trợ đổi sang WeatherAPI bằng `WEATHER_API_PROVIDER`.
2. Mặc định dùng `gpt-4o-mini` cho rerank và `text-embedding-3-small` cho vector; cả hai đều cấu hình được qua env.
3. Conversion của báo cáo là mọi order được tạo hay chỉ order `done`.
4. Có gửi `profile_text` chứa allergy note sang embedding provider hay tạo bản text đã tối thiểu hóa dữ liệu.
5. Khi nào cập nhật implicit profile sau order: lúc tạo order hay khi admin chuyển order sang `done`.

Khuyến nghị: chốt provider Weather trước, giữ model theo SPEC dưới dạng config, tính conversion trên order
`done`, không gửi allergy note vào LLM rerank, và cập nhật implicit profile khi order chuyển sang `done`.
