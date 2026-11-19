<?php

namespace Tests\Feature;

use App\Models\Format;
use App\Models\Movie;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerProfileAndFavoritesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_profile_displays_and_updates_only_the_authenticated_user(): void
    {
        $user = $this->activeUser(['name' => 'Current Customer']);
        $otherUser = $this->activeUser(['name' => 'Other Customer']);
        $updatedEmail = 'updated-'.Str::random(8).'@example.test';
        $response = $this->actingAs($user)->get(route('profile'));

        $response->assertOk()
            ->assertSeeText('Current Customer')
            ->assertDontSeeText('Other Customer')
            ->assertSee('name="_token"', false);

        $this->put(route('profile.update'), [
            'name' => 'Updated Customer',
            'email' => $updatedEmail,
            'id' => $otherUser->id,
        ])->assertRedirect(route('profile'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Customer',
            'email' => $updatedEmail,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $otherUser->id,
            'name' => 'Other Customer',
        ]);
    }

    public function test_profile_rejects_invalid_or_duplicate_email_data(): void
    {
        $user = $this->activeUser();
        $otherUser = $this->activeUser();

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => '',
                'email' => $otherUser->email,
            ])
            ->assertSessionHasErrors(['name', 'email']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => $user->email,
        ]);
    }

    public function test_favorites_can_be_added_removed_and_are_private_to_each_user(): void
    {
        $user = $this->activeUser();
        $otherUser = $this->activeUser();
        $movie = $this->movie('Favorite Movie');
        $otherMovie = $this->movie('Private Movie');
        $otherUser->favoriteMovies()->attach($otherMovie);

        $this->actingAs($user)
            ->from(route('favorites.index'))
            ->post(route('movies.favorite', $movie))
            ->assertRedirect(route('favorites.index'))
            ->assertSessionHas('success', 'Đã thêm phim vào danh sách yêu thích.');

        $this->assertDatabaseHas('movie_favorites', [
            'user_id' => $user->id,
            'movie_id' => $movie->id,
        ]);

        $favorites = $this->get(route('favorites.index'));
        $favorites->assertOk()
            ->assertSeeText('Favorite Movie')
            ->assertDontSeeText('Private Movie')
            ->assertSee('Bỏ thích phim', false)
            ->assertSee('name="_token"', false);
        $this->get(route('movies.show', $movie))
            ->assertOk()
            ->assertSee('Đã yêu thích');

        $this->actingAs($otherUser)
            ->get(route('favorites.index'))
            ->assertOk()
            ->assertSeeText('Private Movie')
            ->assertDontSeeText('Favorite Movie');

        $this->actingAs($user);
        $user->favoriteMovies()->syncWithoutDetaching([$movie->id]);
        $this->assertSame(
            1,
            $user->favoriteMovies()->whereKey($movie->id)->count()
        );

        $this->from(route('favorites.index'))
            ->post(route('movies.favorite', $movie))
            ->assertRedirect(route('favorites.index'))
            ->assertSessionHas('success', 'Đã xóa phim khỏi danh sách yêu thích.');

        $this->assertDatabaseMissing('movie_favorites', [
            'user_id' => $user->id,
            'movie_id' => $movie->id,
        ]);

        $this->get(route('favorites.index'))
            ->assertOk()
            ->assertSeeText('Chưa có phim yêu thích')
            ->assertDontSeeText('Private Movie');
        $this->get(route('movies.show', $movie))
            ->assertOk()
            ->assertSee('Yêu thích')
            ->assertDontSee('Đã yêu thích');
    }

    public function test_guests_are_redirected_to_login_for_profile_and_favorites(): void
    {
        $movie = $this->movie('Guest Favorite Movie');

        $this->get(route('profile'))->assertRedirect(route('login'));
        $this->get(route('favorites.index'))->assertRedirect(route('login'));
        $this->post(route('movies.favorite', $movie))->assertRedirect(route('login'));
    }

    private function activeUser(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->forceFill(['is_active' => true])->save();

        return $user;
    }

    private function movie(string $title): Movie
    {
        $format = Format::where('slug', '2d')->firstOrFail();

        return Movie::create([
            'title' => $title.' '.Str::random(6),
            'genre' => 'Drama',
            'duration' => 90,
            'status' => 'now_showing',
            'age_rating' => 'P',
            'format_id' => $format->id,
        ]);
    }
}
