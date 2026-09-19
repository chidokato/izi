<?php

namespace Tests\Feature;

use App\Models\Post;
use Tests\TestCase;

class CourseManagementTest extends TestCase
{
    public function test_courses_index_page_displays_english_labels_and_courses(): void
    {
        $response = $this->get('/admin/courses');

        $response->assertStatus(200);
        $response->assertSee('Course Management');
        $response->assertSee('Add Course');
        $response->assertSee('Title');
        $response->assertSee('Tuition Fee');
        $response->assertSee('Featured Course');
        $response->assertSee('Status');
        $response->assertSee('Published At');
        $response->assertSee('Actions');
        $response->assertSee('IELTS Academic Mastery');
        $response->assertDontSee('Apartment');
    }

    public function test_courses_create_page_displays_english_labels(): void
    {
        $response = $this->get('/admin/courses/create');

        $response->assertStatus(200);
        $response->assertSee('Add New Course');
        $response->assertSee('Course Title');
        $response->assertSee('Short Description / Overview');
        $response->assertSee('Tuition Fee (Price)');
        $response->assertSee('Instructor / Teacher');
        $response->assertSee('Course Thumbnail');
        $response->assertSee('Featured Course');
        $response->assertSee('Active Course');
    }

    public function test_news_page_still_works(): void
    {
        $response = $this->get('/admin/news');

        $response->assertStatus(200);
        $response->assertSee('Quan ly news', false);
    }

    public function test_products_route_redirects_to_courses(): void
    {
        $response = $this->get('/admin/products');

        $response->assertRedirect('/admin/courses');
    }
}
