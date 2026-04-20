<?php

namespace Botble\PostScheduler\Tests\Feature;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Supports\BaseTestCase;
use Botble\Blog\Models\Post;
use Botble\PostScheduler\Providers\HookServiceProvider;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PostSchedulerHookTest extends BaseTestCase
{
    use RefreshDatabase;

    protected HookServiceProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new HookServiceProvider($this->app);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    protected function makePost(array $attributes = []): Post
    {
        return Post::query()->create(array_merge([
            'name' => 'Test Post',
            'description' => 'Test description',
            'content' => 'Test content',
            'status' => BaseStatusEnum::PUBLISHED,
        ], $attributes));
    }

    protected function invokeHook(Post $post, array $input): void
    {
        $request = Request::create('/', 'POST', $input);

        $this->provider->saveSchedulerData('edit.post', $request, $post);
    }

    public function test_it_backdates_post_with_day_first_slash_format(): void
    {
        // This is the original bug: Carbon::parse('05/02/2026') treats it as
        // American m/d/Y and saves May 2 instead of Feb 5.
        config(['core.base.general.date_format.date' => 'd/m/Y']);

        $post = $this->makePost();

        $this->invokeHook($post, [
            'publish_date' => '05/02/2026',
            'publish_time' => '14:30',
        ]);

        $this->assertSame(
            '2026-02-05 14:30:00',
            $post->fresh()->created_at->toDateTimeString()
        );
    }

    public function test_it_backdates_post_with_iso_format(): void
    {
        config(['core.base.general.date_format.date' => 'Y-m-d']);

        $post = $this->makePost();

        $this->invokeHook($post, [
            'publish_date' => '2025-11-20',
            'publish_time' => '09:15',
        ]);

        $this->assertSame(
            '2025-11-20 09:15:00',
            $post->fresh()->created_at->toDateTimeString()
        );
    }

    public function test_it_backdates_post_with_day_first_dash_format(): void
    {
        config(['core.base.general.date_format.date' => 'd-m-Y']);

        $post = $this->makePost();

        $this->invokeHook($post, [
            'publish_date' => '05-02-2026',
            'publish_time' => '00:00',
        ]);

        $this->assertSame(
            '2026-02-05 00:00:00',
            $post->fresh()->created_at->toDateTimeString()
        );
    }

    public function test_it_backdates_post_with_american_slash_format(): void
    {
        config(['core.base.general.date_format.date' => 'm/d/Y']);

        $post = $this->makePost();

        $this->invokeHook($post, [
            'publish_date' => '02/05/2026',
            'publish_time' => '08:00',
        ]);

        $this->assertSame(
            '2026-02-05 08:00:00',
            $post->fresh()->created_at->toDateTimeString()
        );
    }

    public function test_it_strips_seconds_from_publish_time(): void
    {
        config(['core.base.general.date_format.date' => 'Y-m-d']);

        $post = $this->makePost();

        $this->invokeHook($post, [
            'publish_date' => '2026-03-15',
            'publish_time' => '10:45:33',
        ]);

        $this->assertSame(
            '2026-03-15 10:45:00',
            $post->fresh()->created_at->toDateTimeString()
        );
    }

    public function test_it_defaults_publish_time_to_midnight_when_missing(): void
    {
        config(['core.base.general.date_format.date' => 'Y-m-d']);

        $post = $this->makePost();

        $this->invokeHook($post, [
            'publish_date' => '2026-01-10',
        ]);

        $this->assertSame(
            '2026-01-10 00:00:00',
            $post->fresh()->created_at->toDateTimeString()
        );
    }

    public function test_it_is_a_noop_when_publish_date_is_empty(): void
    {
        $post = $this->makePost();
        $before = $post->created_at->toDateTimeString();

        $this->invokeHook($post, [
            'publish_date' => '',
            'publish_time' => '14:00',
        ]);

        $this->assertSame($before, $post->fresh()->created_at->toDateTimeString());
    }

    public function test_update_time_to_current_toggle_overrides_backdate(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-20 10:00:00'));

        $post = $this->makePost();

        $this->invokeHook($post, [
            'publish_date' => '2020-01-01',
            'publish_time' => '00:00',
            'update_time_to_current' => '1',
        ]);

        $this->assertSame(
            '2026-04-20 10:00:00',
            $post->fresh()->created_at->toDateTimeString()
        );
    }

    public function test_it_skips_when_module_is_not_supported(): void
    {
        config(['plugins.post-scheduler.general.supported' => []]);

        $post = $this->makePost();
        $before = $post->created_at->toDateTimeString();

        $this->invokeHook($post, [
            'publish_date' => '2020-01-01',
            'publish_time' => '00:00',
        ]);

        $this->assertSame($before, $post->fresh()->created_at->toDateTimeString());
    }

    public function test_it_accepts_future_dates_for_scheduled_publishing(): void
    {
        config(['core.base.general.date_format.date' => 'Y-m-d']);
        Carbon::setTestNow(Carbon::parse('2026-04-20 10:00:00'));

        $post = $this->makePost();

        $this->invokeHook($post, [
            'publish_date' => '2027-01-01',
            'publish_time' => '12:00',
        ]);

        // Query raw DB because Post::fresh() goes through the
        // model_after_execute_get filter that hides future-dated posts.
        $raw = DB::table($post->getTable())->where('id', $post->id)->first();

        $this->assertSame('2027-01-01 12:00:00', $raw->created_at);
    }

    public function test_frontend_filter_hides_future_dated_posts_and_shows_past_dated(): void
    {
        config(['core.base.general.date_format.date' => 'Y-m-d']);
        Carbon::setTestNow(Carbon::parse('2026-04-20 10:00:00'));

        $future = $this->makePost(['name' => 'Future Post']);
        $past = $this->makePost(['name' => 'Past Post']);

        $this->invokeHook($future, [
            'publish_date' => '2027-01-01',
            'publish_time' => '00:00',
        ]);

        $this->invokeHook($past, [
            'publish_date' => '2020-01-01',
            'publish_time' => '00:00',
        ]);

        $visible = Post::query()
            ->where('created_at', '<=', Carbon::now()->toDateTimeString())
            ->pluck('name')
            ->all();

        $this->assertContains('Past Post', $visible);
        $this->assertNotContains('Future Post', $visible);
    }

    public function test_it_ignores_invalid_date_input(): void
    {
        config(['core.base.general.date_format.date' => 'Y-m-d']);

        $post = $this->makePost();
        $before = $post->created_at->toDateTimeString();

        $this->invokeHook($post, [
            'publish_date' => 'not-a-real-date',
            'publish_time' => '14:00',
        ]);

        $this->assertSame($before, $post->fresh()->created_at->toDateTimeString());
    }

    public function test_check_publish_date_filters_future_posts_from_collection(): void
    {
        // Regression: model_after_execute_get passes a Collection and the
        // previous `$data->where(...)` call was a no-op, so future-dated
        // posts leaked onto the frontend (e.g. WordPress imports).
        Carbon::setTestNow(Carbon::parse('2026-04-20 10:00:00'));

        $future = $this->makePost(['name' => 'Future Post']);
        $future->created_at = Carbon::parse('2027-01-01 00:00:00');
        $future->saveQuietly();

        $past = $this->makePost(['name' => 'Past Post']);
        $past->created_at = Carbon::parse('2020-01-01 00:00:00');
        $past->saveQuietly();

        $collection = Post::query()->get();

        $filtered = $this->provider->checkPublishDateBeforeShow($collection, new Post());

        $names = $filtered->pluck('name')->all();

        $this->assertContains('Past Post', $names);
        $this->assertNotContains('Future Post', $names);
    }

    public function test_check_publish_date_passes_through_unsupported_model(): void
    {
        config(['plugins.post-scheduler.general.supported' => []]);

        $collection = collect([new Post(['name' => 'Any'])]);

        $result = $this->provider->checkPublishDateBeforeShow($collection, new Post());

        $this->assertSame($collection, $result);
    }
}
