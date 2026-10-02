<?php

namespace Tests\Feature;

use App\Enums\TemperatureType;
use App\Models\Drink;
use App\Models\RecommendationLog;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RecommendationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.openai.key' => null,
            'services.weather.key' => null,
        ]);
        Http::preventStrayRequests();
    }

    public function test_guest_cannot_request_recommendations(): void
    {
        $this->getJson('/api/recommendations')->assertUnauthorized();
        $this->get('/api/recommendations')->assertUnauthorized();
    }

    public function test_guest_can_view_basic_time_and_weather_context(): void
    {
        Cache::flush();
        config([
            'services.weather.provider' => 'openweather',
            'services.weather.key' => 'weather-test-key',
        ]);
        Http::fake([
            'api.openweathermap.org/*' => Http::response([
                'weather' => [['main' => 'Clouds']],
                'main' => ['temp' => 29.4],
            ]),
        ]);

        $this->getJson('/api/recommendation-context?lat=10.77&lon=106.70')
            ->assertOk()
            ->assertJsonPath('data.weather', 'cloudy')
            ->assertJsonPath('data.temperature', 29.4)
            ->assertJsonPath('data.location_used', true)
            ->assertJsonStructure(['data' => [
                'current_time',
                'current_date',
                'hour',
                'weather',
                'temperature',
                'location_used',
                'suggestion',
            ]]);
    }

    public function test_query_coordinates_and_occasion_are_validated(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/recommendations?lat=10.7')->assertUnprocessable();
        $this->getJson('/api/recommendations?lat=91&lon=181')->assertUnprocessable();
        $this->getJson('/api/recommendations?occasion='.str_repeat('a', 101))->assertUnprocessable();
    }

    public function test_rule_fallback_returns_available_drinks_with_stable_schema_and_log(): void
    {
        $user = User::factory()->create();
        UserPreference::query()->create([
            'user_id' => $user->id,
            'taste_tags' => ['coffee'],
        ]);
        Sanctum::actingAs($user);

        Drink::factory()->count(6)->create([
            'tags' => ['coffee'],
            'temperature_type' => TemperatureType::Cold,
        ]);
        $unavailable = Drink::factory()->unavailable()->create(['tags' => ['coffee']]);

        $response = $this->getJson('/api/recommendations?occasion='.urlencode('học bài'))
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.strategy', 'profile_rule_fallback')
            ->assertJsonPath('meta.fallback', true)
            ->assertJsonPath('meta.context.occasion', 'học bài')
            ->assertJsonStructure([
                'data' => [[
                    'rank',
                    'score',
                    'explanation',
                    'drink' => ['id', 'name', 'category', 'price', 'temperature_type', 'tags'],
                ]],
                'meta' => ['strategy', 'fallback', 'context', 'recommendation_log_id', 'reason_summary'],
            ]);

        $responseIds = collect($response->json('data'))->pluck('drink.id')->all();
        $this->assertNotContains($unavailable->id, $responseIds);
        $log = RecommendationLog::query()->sole();
        $this->assertCount(5, $log->final_ranked_ids);
        $this->assertNotContains($unavailable->id, $log->candidate_drink_ids);
    }

    public function test_empty_menu_returns_empty_fallback_without_log(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/recommendations')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.strategy', 'empty_menu')
            ->assertJsonPath('meta.recommendation_log_id', null);

        $this->assertDatabaseCount('recommendation_logs', 0);
    }

    public function test_recommendations_are_rate_limited_per_user(): void
    {
        config(['services.recommendation.rate_limit_per_minute' => 2]);
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/recommendations')->assertOk();
        $this->getJson('/api/recommendations')->assertOk();
        $this->getJson('/api/recommendations')
            ->assertTooManyRequests()
            ->assertJsonPath('message', 'Bạn đã yêu cầu gợi ý quá nhanh. Vui lòng chờ một phút rồi thử lại.');
    }

    public function test_semantic_candidates_are_limited_before_structured_llm_rerank(): void
    {
        config([
            'services.openai.key' => 'test-key',
            'services.openai.base_url' => 'https://api.openai.test/v1',
        ]);

        $user = User::factory()->create();
        UserPreference::query()->create([
            'user_id' => $user->id,
            'taste_tags' => ['coffee'],
            'profile_embedding' => [1, 0, 0],
        ]);
        Sanctum::actingAs($user);

        $drinks = Drink::factory()->count(12)->create([
            'description_embedding' => [1, 0, 0],
        ]);
        $preferredId = $drinks->get(4)->id;

        Http::fake([
            'api.openai.test/v1/responses' => Http::response([
                'output' => [[
                    'content' => [[
                        'type' => 'output_text',
                        'text' => json_encode([
                            'recommendations' => [[
                                'drink_id' => $preferredId,
                                'explanation' => 'Phù hợp nhất với hồ sơ vị giác.',
                            ]],
                        ], JSON_THROW_ON_ERROR),
                    ]],
                ]],
            ]),
        ]);

        $this->getJson('/api/recommendations')
            ->assertOk()
            ->assertJsonPath('meta.strategy', 'hybrid_llm')
            ->assertJsonPath('data.0.drink.id', $preferredId)
            ->assertJsonCount(5, 'data');

        Http::assertSent(function ($request): bool {
            $input = json_decode($request['input'], true, flags: JSON_THROW_ON_ERROR);

            return count($input['candidates']) === 10
                && $request['store'] === false
                && $request['text']['format']['type'] === 'json_schema';
        });
    }
}
