<?php

namespace Tests\Feature;

use App\Models\Post;
use Tests\TestCase;

class CourseEmptyDataTest extends TestCase
{
    public function test_missing_pdf_does_not_resolve_to_another_courses_file(): void
    {
        $this->assertSame('', (new Post)->pdf_url);
        $this->assertSame('', (new Post(['pdf_file' => 'uploads/nonexistent-course-test.pdf']))->pdf_url);
    }
}
