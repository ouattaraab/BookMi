<?php

namespace Tests\Unit\Enums;

use App\Enums\UserRole;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UserRoleEnumTest extends TestCase
{
    #[Test]
    public function it_has_seven_roles(): void
    {
        $this->assertCount(7, UserRole::cases());
    }

    #[Test]
    public function it_has_correct_values(): void
    {
        $this->assertSame('client', UserRole::CLIENT->value);
        $this->assertSame('talent', UserRole::TALENT->value);
        $this->assertSame('manager', UserRole::MANAGER->value);
        $this->assertSame('admin_ceo', UserRole::ADMIN_CEO->value);
        $this->assertSame('admin_comptable', UserRole::ADMIN_COMPTABLE->value);
        $this->assertSame('admin_controleur', UserRole::ADMIN_CONTROLEUR->value);
        $this->assertSame('admin_moderateur', UserRole::ADMIN_MODERATEUR->value);
    }

    #[Test]
    public function registrable_roles_returns_only_client_and_talent(): void
    {
        $registrable = UserRole::registrableRoles();

        $this->assertCount(2, $registrable);
        $this->assertContains('client', $registrable);
        $this->assertContains('talent', $registrable);
        $this->assertNotContains('manager', $registrable);
        $this->assertNotContains('admin_ceo', $registrable);
    }

    #[Test]
    public function labels_return_french_strings(): void
    {
        $this->assertSame('Client', UserRole::CLIENT->label());
        $this->assertSame('Talent', UserRole::TALENT->label());
        $this->assertSame('Manager', UserRole::MANAGER->label());
        $this->assertSame('Administrateur CEO', UserRole::ADMIN_CEO->label());
    }
}
