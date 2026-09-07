<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\BreadcrumbService;

class BreadcrumbServiceTest extends TestCase
{
    protected BreadcrumbService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BreadcrumbService();
    }

    public function test_service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(BreadcrumbService::class, $this->service);
    }

    public function test_can_set_custom_label(): void
    {
        $this->service->setCustomLabel('test.route', 'Custom Label');
        $crumbs = $this->service->generate();

        // Just verify no errors
        $this->assertIsIterable($crumbs);
    }
}
